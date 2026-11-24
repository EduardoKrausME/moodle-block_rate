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
 * phpcs:disable PSR2.Methods.FunctionClosingBrace.SpacingBeforeClose
 *
 * Privacy Subsystem implementation for block_rate.
 *
 * @package    block_rate
 * @copyright  2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rate\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * phpcs:disable Universal.OOStructures.AlphabeticExtendsImplements.ImplementsWrongOrder
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
              FROM {block_rate} r
              JOIN {context} ctx
                ON (r.cmid = 0
                    AND ctx.contextlevel = :courselevel
                    AND ctx.instanceid = r.course)
                OR (r.cmid <> 0
                    AND ctx.contextlevel = :modulelevel
                    AND ctx.instanceid = r.cmid)
             WHERE r.userid = :userid";

        $params = [
            "courselevel" => CONTEXT_COURSE,
            "modulelevel" => CONTEXT_MODULE,
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

        if ($context->contextlevel == CONTEXT_COURSE) {
            $sql = "SELECT r.userid
                      FROM {block_rate} r
                     WHERE r.course = :instanceid
                       AND r.cmid = 0";
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            $sql = "SELECT r.userid
                      FROM {block_rate} r
                     WHERE r.cmid = :instanceid";
        } else {
            return;
        }

        $params = ["instanceid" => $context->instanceid];

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

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $conditions = ["course" => $context->instanceid, "cmid" => 0];
            } else if ($context->contextlevel == CONTEXT_MODULE) {
                $conditions = ["cmid" => $context->instanceid];
            } else {
                continue;
            }

            $conditions["userid"] = $userid;
            $records = $DB->get_records("block_rate", $conditions, "created, id");
            if (!$records) {
                continue;
            }

            $ratings = [];
            foreach ($records as $record) {
                $ratings[] = (object) [
                    "course" => (int)$record->course,
                    "cmid" => (int)$record->cmid,
                    "userid" => (int)$record->userid,
                    "rating" => (int)$record->rating,
                    "created" => transform::datetime($record->created),
                ];
            }
            writer::with_context($context)->export_data([], (object) ["ratings" => $ratings]);
        }
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

        if ($context->contextlevel == CONTEXT_COURSE) {
            $conditions = ["course" => $context->instanceid, "cmid" => 0];
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            $conditions = ["cmid" => $context->instanceid];
        } else {
            return;
        }

        $DB->delete_records("block_rate", $conditions);
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
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $conditions = ["course" => $context->instanceid, "cmid" => 0];
            } else if ($context->contextlevel == CONTEXT_MODULE) {
                $conditions = ["cmid" => $context->instanceid];
            } else {
                continue;
            }

            $conditions["userid"] = $userid;
            $DB->delete_records("block_rate", $conditions);
        }
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

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$userinsql, $userinparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        if ($context->contextlevel == CONTEXT_COURSE) {
            $params = array_merge(["course" => $context->instanceid, "cmid" => 0], $userinparams);
            $sql = "course = :course AND cmid = :cmid AND userid {$userinsql}";
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            $params = array_merge(["cmid" => $context->instanceid], $userinparams);
            $sql = "cmid = :cmid AND userid {$userinsql}";
        } else {
            return;
        }

        $DB->delete_records_select("block_rate", $sql, $params);
    }

}
