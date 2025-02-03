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
 * @copyright  2009 Jenny Gray
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $setting = new admin_setting_configtext("block_rate/customtitle",
        get_string("customtitle", "block_rate"),
        null, "", PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtextarea("block_rate/description",
        get_string("description", "core"),
        null, "", PARAM_TEXT
    );
    $settings->add($setting);
}
