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
 * @package    local_packages
 * @copyright  2023 Moodle India Information Solutions.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_packages\task\course_flexsections as course_flexsections;
require_once(dirname(__FILE__) . '/../../config.php');
global $OUTPUT, $CFG, $PAGE, $DB;

$PAGE->set_url($CFG->wwwroot . '/local/packages/test.php');

$data = new course_flexsections();
$tt = $data->course_flexsections();
print_r($tt);