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
class backup_rate_block_structure_step extends backup_block_structure_step {

    /**
     * Function define_structure
     *
     * @return mixed
     */
    protected function define_structure() {
        // Define each element separated.
        $ratecourse = new backup_nested_element("rate");
        $items = new backup_nested_element("items");
        $ratecourse->add_child($items);

        // Build the tree.
        $item = new backup_nested_element("item", ["id"], [
            "course",
            "userid",
            "rating",
        ]);
        $items->add_child($item);

        $item->set_source_table("block_rate",
            ["course" => backup::VAR_COURSEID]);

        $item->annotate_ids("user", "userid");

        return $this->prepare_block_structure($ratecourse);
    }
}
