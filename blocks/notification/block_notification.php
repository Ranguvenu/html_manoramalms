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
 * notification block.
 *
 * @package    block_notification
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_notification extends block_base {
    /**
     * [init description]
     */
    function init() {
        $this->title = get_string('pluginname', 'block_notification');
    }

    function hide_header() {
        return true;
    }

    /**
     * [get_content description]
     */
    function get_content() {
          
            global $CFG, $USER, $DB, $PAGE;
            
            
            if ($this->content !== NULL) {
                return $this->content;
            }

            $this->content = new stdClass();
            $renderer = $this->page->get_renderer('block_notification');
            $this->content->text = $renderer->render_notification();
            $this->content->footer = '';

            return $this->content;
    }
}

