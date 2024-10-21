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

namespace theme_horizon;

use question_state;

/**
 * @package   theme_horizon
 * @copyright 2016 Ryan Wyllie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_question_usage extends \question_usage_by_activity {

    public function __construct($component, $context) {
        $this->owningcomponent = $component;
        $this->context = $context;
    }

    public function get_total_marks() {
        $mark = 0;
        foreach ($this->questionattempts as $qa) {
            if ($qa->get_max_mark() > 0 && $qa->get_state() == question_state::$needsgrading) {
                return null;
            }
            $mark += $qa->get_mark();
        }
        return $mark;
    }
}
