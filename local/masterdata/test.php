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
 * TODO describe file test
 *
 * @package    local_masterdata
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 60000);
set_time_limit(0);
require_once('../../config.php');
global $CFG,$OUTPUT;
require_once($CFG->libdir.'/filelib.php');
$id = optional_param('id',0,PARAM_INT);
$type = optional_param('type','course',PARAM_RAW);
$api = new \local_masterdata\api(['debug' => false]);
echo $OUTPUT->header();
if($type == 'course') {
    $courseid = ($id > 0) ? $id : 83;
    echo $api->get_course_content($courseid, true);//72 stag, 89 prod
} else if($type == 'batch') {
    $batchcourseid = ($id > 0) ? $id : 533;
    echo $api->get_batchcourse_content($batchcourseid, true);//72 stag, 89 prod
}else if($type == 'mocktest' ||  $type == 'test') {
    $examid = ($id > 0) ? $id : 2007;
    echo $api->create_yearbook_course($examid,$type);
}
echo $OUTPUT->footer();
