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
 * Mycourses block.
 *
 * @package    block_mycourses
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_mycourses extends block_base {
    /**
     * [init description]
     */
    function init() {
        $this->title = get_string('pluginname', 'block_mycourses');
    }

    function hide_header() {
        return false;
    }

    /**
     * [get_content description]
     */
    function get_content() {
        if(!is_siteadmin()){
          
            global $CFG, $USER, $DB, $PAGE;

            if ($this->content !== NULL) {
                return $this->content;
            }
            $this->content = new stdClass();
            if (!\core\session\manager::is_loggedinas()) {
                $firstlogin = get_user_preferences('first_time_user_logsin', true, $USER);
                set_user_preference('first_time_user_logsin', false, $USER);
                $PAGE->requires->js_call_amd('block_mycourses/popop', 'init', ['firstlogin' => $firstlogin]);
            }
            $PAGE->requires->js_call_amd('block_mycourses/courses', 'init');
            $renderer = $this->page->get_renderer('block_mycourses');
            $this->content->text = $renderer->render_mycourses();
            $this->content->footer = '';

            return $this->content;
        }
    }
}

