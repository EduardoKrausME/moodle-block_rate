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
 * Rating file
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
 * Class rating
 *
 * @package block_rate\output
 */
class rating implements renderable, templatable {

    /** @var int */
    public $courseid;
    /** @var int */
    public $cmid;

    /**
     * rating constructor.
     *
     * @param $courseid
     * @param $cmid
     */
    public function __construct($courseid, $cmid) {
        $this->courseid = $courseid;
        $this->cmid = $cmid;
    }

    /**
     * Checks whether any version of the course already exists.
     *
     * @param int $courseid The ID of the course.
     * @param int $cmid The ID of the course module.
     *
     * @return int  rating.
     * @throws \dml_exception
     */
    protected static function get_rating($courseid, $cmid) {
        global $DB;

        $sql = "SELECT AVG(rating) AS avg
                  FROM {block_rate}
                 WHERE course = :course
                   AND cmid   = :cmid";

        $avg = -1;
        if ($avgrec = $DB->get_record_sql($sql, ["course" => $courseid, "cmid" => $cmid])) {
            $avg = $avgrec->avg * 2;  // Double it for half star scores.
            // Now round it up or down.
            $avg = round($avg);
        }
        return $avg;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     *
     * @return stdClass
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output) {
        $rating = self::get_rating($this->courseid, $this->cmid);
        $starscount = $rating / 2;
        $parts = explode(".", (string)$starscount);
        $count = intval($parts[0]);
        $half = isset($parts[1]) ? true : false;
        $stars = [];
        for ($i = 0; $i < $count; $i++) {
            array_push($stars, $i);
        }

        return (object)[
            "rating" => $starscount,
            "stars" => $stars,
            "half" => $half
        ];
    }
}
