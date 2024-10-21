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
 * TODO describe file viewquizattempts
 *
 * @package    local_masterdata
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_login();
use mod_quiz\quiz_settings;
global $DB, $CFG, $OUTPUT,$USER, $PAGE;
$quizid= required_param('quizid',PARAM_INT);
$quizmoduleid = $DB->get_field('modules','id',['name'=>'quiz']);
$cminfo = $DB->get_record('course_modules',['module'=>$quizmoduleid,'instance'=>$quizid]);
$quizobj = quiz_settings::create_for_cmid($cminfo->id, $USER->id);
$context = $quizobj->get_context();
$PAGE->set_course(get_course($cminfo->course));
$PAGE->set_context($context);
$url = new moodle_url('/local/masterdata/viewquizattempts.php', ['quizid' =>$quizid]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('quizattemptslist','local_masterdata'));
if($quizid){
    $quizname = $DB->get_field('quiz','name',['id'=>$quizid]);
    $heading =  get_string('viewqattempts','local_masterdata',$quizname);
} else {
    $heading = get_string('quizattemptslist','local_masterdata');
}
$PAGE->set_heading($heading);
$PAGE->set_url('/local/masterdata/viewquizattempts.php', ['quizid' =>$quizid]);
echo $OUTPUT->header();
(new \local_masterdata\questionslib())->view_quiz_attempts($quizid);
echo $OUTPUT->footer();
