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
 *
 * @package    block_rate
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @copyright  2019 Pierre Duverneix - Fondation UNIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * Class block_rate_external
 */
class block_rate_external extends external_api {
    /**
     * Describes the parameters for set_status.
     *
     * @return external_function_parameters
     * @since  Moodle 3.4
     */
    public static function set_rating_parameters() {
        return new external_function_parameters(
            [
                "courseid" => new external_value(PARAM_INT, "The course ID"),
                "cmid" => new external_value(PARAM_INT, "The course module ID"),
                "rating" => new external_value(PARAM_INT, "The rating value"),
            ]
        );
    }

    /**
     * Function set_rating
     *
     * @param int $courseid
     * @param int $cmid
     * @param int $rating
     *
     * @return bool
     */
    public static function set_rating($courseid, $cmid, $rating) {
        global $DB, $USER;

        // Parameters validation.
        $params = self::validate_parameters(self::set_rating_parameters(),
            ["courseid" => $courseid, "cmid" => $cmid, "rating" => $rating]);

        $rating = $DB->get_record("block_rate",
            ["userid" => $USER->id, "cmid" => $params["cmid"], "course" => $params["courseid"]]);

        if ($rating) {
            $rating->rating = $params["rating"];
            $DB->update_record("block_rate", $rating);

            return true;
        } else {
            $data = (object)[
                "course" => $params["courseid"],
                "cmid" => $params["cmid"],
                "userid" => $USER->id,
                "rating" => $params["rating"],
                "created" => time(),
            ];
            $DB->insert_record("block_rate", $data);

            return true;
        }
    }

    /**
     * Function set_rating_returns
     *
     * @return external_value
     */
    public static function set_rating_returns() {
        return new external_value(PARAM_BOOL, "The user rating status . ");
    }
}
