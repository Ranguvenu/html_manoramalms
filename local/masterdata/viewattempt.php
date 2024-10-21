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
 * TODO describe file viewattempt
 *
 * @package    local_masterdata
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
use mod_quiz\quiz_settings;
require_login();
global $DB, $CFG, $OUTPUT,$USER, $PAGE;
$attemptid= required_param('attemptid',PARAM_INT);
$cmid= required_param('cmid',PARAM_INT);
$url = new moodle_url('/local/masterdata/viewattempt.php', ['attemptid' =>$attemptid,'cmid' =>$cmid]);
$PAGE->set_url($url);
$courseid = $DB->get_field('course_modules','course',['id'=>$cmid]);
$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);
$context = $quizobj->get_context();
$PAGE->set_course(get_course($courseid));
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$attemptsdata = $DB->get_record('local_question_attempts',['cmid'=>$cmid,'attemptid'=>$attemptid]);
$attemptinfo =($attemptsdata->attemptsinfo != 'No Data') ? json_decode($attemptsdata->attemptsinfo):''; 
$questiondetails = array();
$i=1;
if($attemptinfo){
   $attemptsinfolists = (array)$attemptinfo;
    foreach($attemptsinfolists AS $attemptdata){
        if($attemptdata) {
            $attemptdata->questiondetails->questionindex = $i; 
            $attemptdata->questiondetails->answerlable = ($attemptdata->attemptinfo) ? (($attemptdata->attemptinfo->is_correct) ?'Answered' :'Wrong Answered') :'Not Answered';

           $attemptdata->questiondetails->qustionheadermessage = ($attemptdata->attemptinfo) ? (($attemptdata->attemptinfo->is_correct) ?'answered' :'wronganswered') :'notanswered';

            $attemptdata->questiondetails->answerclass = ($attemptdata->attemptinfo) ? (($attemptdata->attemptinfo->is_correct) ?'correct' :'incorrect') :'';

            $attemptdata->questiondetails->displayclass = ($attemptdata->attemptinfo) ? (($attemptdata->attemptinfo->is_correct) ?'fa-check text-success' :'fa-remove text-danger') :'';
            $attemptdata->questiondetails->displaylable = ($attemptdata->attemptinfo) ? (($attemptdata->attemptinfo->is_correct) ?'Correct' :'Incorrect') :'';
            $attemptdata->questiondetails->question = str_replace('\\', '', html_entity_decode($attemptdata->questiondetails->question));
            foreach($attemptdata->answeroptions  AS $answeroption) {
                $answeroption->answer_option = str_replace('\\', '', html_entity_decode($answeroption->answer_option));
                if($answeroption->id == $attemptdata->attemptinfo->student_answer){
                    $answeroption->studentanswer = true;
                }
            }
            $attemptdata->questiondetails->answeroptions = (array)$attemptdata->answeroptions;
            $attemptdata->questiondetails->solution = str_replace('\\', '', html_entity_decode($attemptdata->questiondetails->solution)) ;
            unset($attemptdata->answeroptions);
            $questiondetails[]= $attemptdata;
            $i++;
        }
    }
}
$quizobj = quiz_settings::create($attemptsdata->quizid, $USER->id);
$context = $quizobj->get_context();
$canattempt = has_capability('mod/quiz:attempt', $context);
if(!is_siteadmin() && $canattempt) {
    $migratedattempts =$DB->count_records('local_question_attempts',['cmid'=>$cmid,'quizid'=>$attemptsdata->quizid,'userid'=>$USER->id]);
    if($migratedattempts == 1) {
        $backtoattemptlist = $CFG->wwwroot.'/mod/quiz/view.php?id='.$cmid;
        $navigationlable = get_string('backtoquiz','local_masterdata');
    } else {
        $backtoattemptlist  = $CFG->wwwroot.'/local/masterdata/viewquizattempts.php?quizid='.$attemptsdata->quizid;
        $navigationlable = get_string('backtoattemptslist','local_masterdata');
    }
} else {
    $backtoattemptlist = $CFG->wwwroot.'/local/masterdata/viewquizattempts.php?quizid='.$attemptsdata->quizid;
    $navigationlable = get_string('backtoattemptslist','local_masterdata');

}
$attenptdata = [
    'testname'=>$quizname = $DB->get_field('quiz','name',['id'=>$attemptsdata->quizid]),
    'scored'=>$attemptsdata->mark,
    'total'=>$attemptsdata->total_mark,
    'timetaken'=>$attemptsdata->timetaken,
    'backtoattemptlist'=>$backtoattemptlist,
    'canviewattemptsinfo'=>($attemptinfo)?true:false,
    'questiondetails'=>$questiondetails,
    'navigationlable'=>$navigationlable,
];

echo $OUTPUT->render_from_template('local_masterdata/viewattempts', $attenptdata);

echo $OUTPUT->footer();
