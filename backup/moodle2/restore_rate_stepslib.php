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
 * Rate course block backup
 *
 * @package    block_rate
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @copyright  2012 Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete structure for the backup, with file and id annotations
 */
class restore_rate_block_structure_step extends restore_structure_step {

    /**
     * Function define_structure
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element("block", "/block/rate/items");
        $paths[] = new restore_path_element("item", "/block/rate/items/item");

        return $paths;
    }

    /**
     * Function process_block
     *
     * @param $data
     */
    public function process_block($data) {
    }

    /**
     * Function process_item
     *
     * @param $item
     */
    public function process_item($item) {
        global $DB;

        $item["course"] = $this->task->get_courseid();
        $item["userid"] = $this->task->get_userid();

        unset($item["id"]);

        $sql = 'SELECT id 
                  FROM {block_rate}
                 WHERE course = :course
                   AND userid = :userid';

        if ($existing = $DB->get_record_sql($sql, $item)) {
            $item["id"] = $existing->id;
            $DB->update_record("block_rate", $item);
        } else {
            $DB->insert_record("block_rate", $item);
        }
    }
}
