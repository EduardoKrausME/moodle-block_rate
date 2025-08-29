<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for block_rate.
 *
 * @package    block_rate
 * @copyright  2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rate\privacy;

use context;
use context_module;
use moodle_recordset;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for block_rate.
 *
 * @package   block_rate
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     *
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table("block_rate", [
            "course" => "privacy:metadata:block_rate:course",
            "cmid" => "privacy:metadata:block_rate:cmid",
            "userid" => "privacy:metadata:block_rate:userid",
            "rating" => "privacy:metadata:block_rate:rating",
            "created" => "privacy:metadata:block_rate:created",
        ], "privacy:metadata:block_rate");

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     *
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     * @throws \Exception
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "
            SELECT DISTINCT ctx.id
              FROM {context}  ctx ON ctx.instanceid   = cm.id
                                 AND ctx.contextlevel = :modulelevel
              JOIN {block_rate} r ON r.cmid           = cm.id
             WHERE r.userid = :userid";

        $params = [
            "modulelevel" => CONTEXT_COURSE,
            "userid" => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin
     *                           combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "SELECT r.userid
                  FROM {block_rate} r
                 WHERE cmid = :instanceid";

        $params = [
            "instanceid" => $context->instanceid,
        ];

        $userlist->add_from_sql("userid", $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     *
     * @throws \Exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        $cmids = array_reduce($contextlist->get_contexts(), function ($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        $cmidstocmids = static::get_block_rate_ids_to_cmids_from_cmids($cmids);
        $cmids = array_keys($cmidstocmids);

        // Export the messages.
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ["userid" => $userid]);
        $recordset = $DB->get_recordset_select("block_rate", "cmid $insql AND userid = :userid", $params, "created, id");
        static::recordset_loop_and_export($recordset, "cmid", [], function ($carry, $record) use ($user, $cmidstocmids) {
            $message = $record->message;
            $carry[] = [
                "message" => $message,
                "sent_at" => transform::datetime($record->timestamp),
                "is_system_generated" => transform::yesno($record->issystem),
            ];
            return $carry;

        }, function ($cmid, $data) use ($user, $cmidstocmids) {
            $context = context_module::instance($cmidstocmids[$cmid]);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = (object)array_merge((array)$contextdata, ["messages" => $data]);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     *
     * @throws \Exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $DB->delete_records_select("block_rate", "cmid = :cmid", ["cmid" => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     *
     * @throws \Exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $cmids = array_reduce($contextlist->get_contexts(), function ($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        $cmidstocmids = static::get_block_rate_ids_to_cmids_from_cmids($cmids);
        $cmids = array_keys($cmidstocmids);

        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "cmid {$insql} AND userid = :userid";
        $params = array_merge($inparams, ["userid" => $userid]);

        $DB->delete_records_select("block_rate", $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     *
     * @throws \Exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record("course_modules", ["id" => $context->instanceid]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(["cmid" => $cm->id], $userinparams);

        $sql = "cmid = :cmid AND userid {$userinsql}";
        $DB->delete_records_select("block_rate", $sql, $params);
    }

    /**
     * Return a dict of rate IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     *
     * @return array
     *
     * @throws \Exception
     */
    protected static function get_block_rate_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT id, cmid
              FROM {block_rate}
             WHERE cmid {$insql}";
        $params = array_merge($inparams);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param moodle_recordset $recordset The recordset.
     * @param string $splitkey            The record key to determine when to export.
     * @param mixed $initial              The initial data to reduce from.
     * @param callable $reducer           The function to return the dataset, receives current dataset, and the current
     *                                    record.
     * @param callable $export            The function to export the dataset, receives the last value from $splitkey
     *                                    and the dataset.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected static function recordset_loop_and_export(moodle_recordset $recordset, $splitkey, $initial,
                                                        callable $reducer, callable $export) {
        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }
}
