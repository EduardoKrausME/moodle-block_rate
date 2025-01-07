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
 * Rate Form file
 *
 * @package    block_rate
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @copyright  2019 Pierre Duverneix <pierre.duverneix@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rate\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class rateform
 *
 * @package block_rate\output
 */
class rateform implements renderable, templatable {

    /** @var int */
    public $courseid;
    /** @var int */
    public $cmid;

    /**
     * rateform constructor.
     *
     * @param int $courseid
     * @param int $cmid
     */
    public function __construct($courseid, $cmid) {
        $this->courseid = $courseid;
        $this->cmid = $cmid;
    }

    /**
     * Function get_my_ratting
     *
     * @param int $courseid
     * @param int $cmid
     *
     * @return string
     */
    private static function get_my_ratting($courseid, $cmid) {
        global $DB, $USER;

        $myrating = $DB->get_record("block_rate",
            ["course" => $courseid, "cmid" => $cmid, "userid" => $USER->id]);
        if ($myrating) {
            return $myrating->rating;
        } else {
            return "";
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $myrating = self::get_my_ratting($this->courseid, $this->cmid);

        return (object)[
            "cmid" => $this->cmid,
            "myrating" => $myrating,
            "courseid" => $this->courseid,
        ];
    }
}
