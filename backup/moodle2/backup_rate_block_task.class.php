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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/blocks/rate/backup/moodle2/backup_rate_stepslib.php");

/**
 * Class backup_rate_block_task
 */
class backup_rate_block_task extends backup_block_task {

    /**
     * Function define_my_settings
     *
     */
    protected function define_my_settings() {
    }

    /**
     * Function define_my_steps
     *
     */
    protected function define_my_steps() {
        $this->add_step(new backup_rate_block_structure_step("rate_structure",
            "rate.xml"));
    }

    /**
     * Function get_fileareas
     *
     * @return array
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Function get_configdata_encoded_attributes
     *
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * Function encode_content_links
     *
     * @param mixed $content
     *
     * @return mixed
     */
    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}
