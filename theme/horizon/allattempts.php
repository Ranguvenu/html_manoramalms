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
 * This script displays a particular page of a quiz all attempts.
 *
 * @package   theme_horizon
 * @copyright 2024 Moodle India Information Solutions  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
use mod_quiz\quiz_settings;
global $DB, $OUTPUT, $PAGE, $USER, $CFG;
require_once($CFG->dirroot.'/mod/quiz/lib.php');
$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
require_login();
$PAGE->set_title('allattempts', 'quiz');
$PAGE->set_pagelayout('standard');
$cm = $DB->get_record('course_modules', ['id' => $id]);
$coursename = $DB->get_field('course', 'fullname', ['id' => $cm->course]);
$PAGE->set_heading($coursename);
$quizobj = quiz_settings::create_for_cmid($id, $USER->id);
$context = $quizobj->get_context();
$PAGE->set_course(get_course($cm->course));
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/theme/horizon/allattempts.php?id='.$id);
echo $OUTPUT->header();
$quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
$examname = $quiz->name;

$duration = '00:00:00';
if ($quiz->timelimit > 0) {
	$duration = gmdate('H:i:s', $quiz->timelimit);
}
$qmarks = 0;
if ($quiz->sumgrades > 0) {
	$qmarks = round($quiz->sumgrades);
}
$qcount = 0;
$questioncountsql = "SELECT count(id) as qcount
					   FROM {quiz_slots}
					  WHERE quizid = :quizid";
$qcount = $DB->count_records_sql($questioncountsql, ['quizid' => $cm->instance]);
$alluserattempts = [];
$userattempts = quiz_get_user_attempts($cm->instance, $USER->id, 'all');
$allattemptscount = count($userattempts);
$count = 0;
foreach ($userattempts as $k => $userattempt) {
	$object = new stdClass();
	$correctcount = 0;
	$wrongcount = 0;
	$unansweredcount = 0;
	$reviewurl = $CFG->wwwroot.'/mod/quiz/review.php?id='.$id.'&attempt='.$userattempt->id;
	$objattempt = $DB->get_record('quiz_attempts', ['id' => $userattempt->id]);
	if ($objattempt) {
	    $examname = $DB->get_field('quiz', 'name', ['id' => $objattempt->quiz]);
	    if (!empty($userattempt->sumgrades)) {
	    	$sumgrades = round($userattempt->sumgrades);
	    } else {
	    	$sumgrades = 0;
	    }

	    if (!empty($quiz->sumgrades)) {
	    	$qsumgrades = round($quiz->sumgrades);
	    } else {
	    	$qsumgrades = 0;
	    }
	    $object->reviewdata = $sumgrades.' / '.$qsumgrades;
	    $object->examname = $examname;
	    $object->coursename = $coursename;
	    $object->attempt = $objattempt->attempt.' / '.$allattemptscount;
	    $sql = " SELECT count(quesa.questionid) as cquestions ";
	    $sql .= " FROM {question_attempts} quesa
	              JOIN {question_attempt_steps} qas ON qas.questionattemptid = quesa.id ";
	    $cwheresql = " WHERE qas.state LIKE 'gradedright' ";
	    $wwheresql = " WHERE qas.state LIKE 'gradedwrong' ";
	    $uwheresql = " WHERE qas.state LIKE 'gaveup' ";
	    $andsql = " AND quesa.questionusageid = ?
	                AND qas.userid = ? ";
	    $csql = $sql . $cwheresql . $andsql;
	    $wsql = $sql . $wwheresql . $andsql;
	    $usql = $sql . $uwheresql . $andsql;

	    $correctcount = $DB->count_records_sql($csql, [$objattempt->uniqueid, $USER->id]);
	    $wrongcount = $DB->count_records_sql($wsql, [$objattempt->uniqueid, $USER->id]);
	    $unansweredcount = $DB->count_records_sql($usql, [$objattempt->uniqueid, $USER->id]);

		$timetaken = '00:00:00';
		if ($userattempt->timefinish > 0 && $userattempt->timestart > 0) {
			$timetakeninunix = $userattempt->timefinish - $userattempt->timestart;
			$timetaken = gmdate('H:i:s', $timetakeninunix);
		}
		$object->timetaken = $timetaken;
	}

	$object->answeredcorrect = $correctcount;
    $object->answeredwrong = $wrongcount;
    $object->unanswered = $unansweredcount;
    $object->reviewurl = $reviewurl;
	$count ++;
    $object->count = $count;

	$alluserattempts[] = $object;
}
$alluserattempts = array_values($alluserattempts);
$attempts = [
	'alluserattempts' => $alluserattempts,
	'examname' => $examname,
	'duration' => $duration,
	'qcount' => $qcount,
	'qmarks' => $qmarks,
	'attemptscount' => $allattemptscount,
];
echo $OUTPUT->render_from_template('theme_horizon/attemptscontent', $attempts);
echo $OUTPUT->footer();
