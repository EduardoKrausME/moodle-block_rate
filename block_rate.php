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
 * This block allows the user to give the course a rating, which
 * is displayed in a custom table (<prefix>_block_rate).
 *
 * @package    block_rate
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @copyright  2009 Jenny Gray
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_rate extends block_list {
    /**
     * Function init
     */
    public function init() {
        $this->title = get_string("defaulttitle_course", "block_rate");
        $config = get_config("block_rate");
        if ($config && $config->customtitle) {
            $this->title = $config->customtitle;
        }
    }

    /**
     * Function applicable_formats
     *
     * @return array
     */
    public function applicable_formats() {
        return ["all" => true, "mod" => true, "tag" => false, "my" => false];
    }

    /**
     * Function has_config
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Function get_content
     *
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_content() {
        global $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config("block_rate");

        $this->content = new stdClass;
        $this->content->items = [];
        $this->content->icons = [];

        $cmid = 0;
        if (isset($this->page->cm->id)) {
            $cmid = $this->page->cm->id;
        }

        if ($cmid) {
            $this->title = get_string("defaulttitle_module", "block_rate");
            $config = get_config("block_rate");
            if ($config && $config->customtitle) {
                $this->title = $config->customtitle;
            }
        }

        if ($config && $config->description) {
            $this->content->items[] = $OUTPUT->render_from_template("block_rate/description",
                ["description" => $config->description]);
        }

        $form = new \block_rate\output\rateform($COURSE->id, $cmid);
        $renderer = $this->page->get_renderer("block_rate");
        $this->content->items[] = $renderer->render($form);

        $rating = new \block_rate\output\rating($COURSE->id, $cmid);
        $renderer = $this->page->get_renderer("block_rate");

        // Output current rating.
        $this->content->footer = $OUTPUT->render_from_template("block_rate/description",
            ["text" => $renderer->render($rating)]);

        return $this->content;
    }
}
