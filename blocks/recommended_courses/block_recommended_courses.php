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
 * recommended_courses block.
 *
 * @package    block_recommended_courses
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_recommended_courses extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_recommended_courses');
    }

    function get_content() {
        global $CFG, $USER, $DB;

        $this->content = new stdClass();

        $renderer = $this->page->get_renderer('block_recommended_courses');
        $this->content->text = $renderer->render_recommended_courses();
        $this->content->footer = '';

        return $this->content;
    }
}

