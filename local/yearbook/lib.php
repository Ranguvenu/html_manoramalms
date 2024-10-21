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
 * Callback implementations for yearbook
 *
 * @package    local_yearbook
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\external\course_summary_exporter;
/**
 * A day equals 86400 seconds.
 */
define('DAYINSEC', 86400);

/**
 * An hour equals 3600 seconds.
 */
define('HOUR', 3600);

/**
 * A day equals 24 hrs.
 */
define('DAY', 24);

/**
 * To get the questionids list.
 * @param int $quizid default value 0.
 * @return associative array of questionids if count greater than 0 else null.
 */
function get_questionids_list(int $quizid = 0, $attemptid) {
    global $DB;
    $sql = "SELECT DISTINCT(qa.questionid) as questionid
              FROM {quiz_attempts} quiza
              JOIN {quiz} q ON q.id = quiza.quiz
              JOIN {question_usages} qu ON qu.id = quiza.uniqueid
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
             WHERE q.id = :quizid ";
    if ($attemptid) {
        $sql .=  " AND quiza.id = :attemptid";
    }
    $quesids = $DB->get_fieldset_sql($sql, ['quizid' => $quizid, 'attemptid' => $attemptid]);
    if (!empty($quesids)) {
        return $quesids;
    } else {
        return null;
    }
}

/**
 * To get the next questionid from the questionids array.
 * @param array $quesids.
 * @param int $currentqkey.
 * @return int next questionid.
 */
function get_next_questionid(array $quesids, int $currentqkey) {
    $currentqkey ++;
    if ($currentqkey < count($quesids)) {
        return $quesids[$currentqkey];
    } else {
        return null;
    }
}

/**
 * To get the quiz attempt id.
 * @param array $quesids.
 * @param int $currentqkid.
 * @return int next questionid.
 */
function get_attemptrec_quiz(int $quizid, int $userid) {
    global $DB;
    $sql = "SELECT quat.*
              FROM {course_modules} cm
              JOIN {context} c ON c.instanceid = cm.id
              JOIN {question_usages} qu ON qu.contextid = c.id
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
              JOIN {quiz_attempts} quat ON quat.uniqueid = qa.questionusageid
             WHERE quat.quiz = :quizid AND quat.userid = :userid 
             ORDER BY quat.id DESC LIMIT 1";
    $list = $DB->get_record_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);
    if (!empty($list)) {
        return $list;
    } else {
        return null;
    }
}

/**
 * To get the tag names in the associative array.
 * @param int $quizid.
 * @return array tag names.
 */
function get_tag_names(int $quizid) {
    global $DB;
    $sql = "SELECT t.id, t.name
              FROM {tag_instance} ti
              JOIN {tag} t ON t.id = ti.tagid
              JOIN {course_modules} cm ON cm.id = ti.itemid
             WHERE cm.instance = :quizid";
    $return = $DB->get_records_sql_menu($sql, ['quizid' => $quizid]);
    if (!empty($return)) {
        return $return;
    } else {
        return null;
    }
}

/**
 * To get quiz by try id and user id.
 * @param int $tryid.
 * @param int $userid.
 * @return array quiz details.
 */
function get_quizby_tryid_userid(int $tryid, int $userid) {
    global $DB;
    $params = [];
    $params['tryid'] = $tryid;
    $params['userid'] = $userid;
    $sql = "SELECT *
              FROM {quiz_attempts}
             WHERE id = :tryid AND userid = :userid";
    $list = $DB->get_record_sql($sql, $params);
    return $list;
}


/**
 * To get quiz last attempt.
 * @param int $attemptid.
 * @param int $quizid.
 * @param int $userid.
 * @return array quiz attempt details.
 */
function get_quizlast_attempt(int $attemptid, int $quizid, int $userid) {
    global $DB;
    $attempted = [];
    // $attemptid = 9;
    // $quizid = 8;
    // $userid = 51;
    $questions = get_questionids_list($quizid, $attemptid);
    foreach ($questions as $qkey => $question) {
        $arrparams = [];
        $arrparams['attemptid'] = $attemptid;
        $arrparams['userid'] = $userid;
        $arrparams['quizid'] = $quizid;
        $arrparams['question'] = $question;
        $sql = "SELECT qa.id as quesattid, qa.*, qas.*
                  FROM {question_attempt_steps} qas
                  JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
                  JOIN {quiz_attempts} quiza ON quiza.userid = qas.userid
                 WHERE quiza.id = :attemptid AND qas.userid = :userid AND quiza.quiz = :quizid
                   AND qa.questionid = :question
                  ORDER BY qas.id DESC LIMIT 1";
        $attempted[] = $DB->get_record_sql($sql, $arrparams);
    }
    if (!empty($attempted)) {
        return $attempted;
    } else {
        return null;
    }
}

/**
 * To get the answer state value.
 * @param obj $attempt.
 * @param int $userid.
 * @param int $question.
 * @return int $ansstate.
 */
function toget_choosen_answerstate_after_finish($attempt, $question, $userid) {
    global $DB;
    $answerslistsql = "SELECT id FROM {question_answers} WHERE question = :questionid";
    $answerslist = $DB->get_fieldset_sql($answerslistsql, ['questionid' => $question]);
    $stateparams = [];
    $stateparams['name'] = '-finish';
    $stateparams['quid'] = $attempt->uniqueid;
    $stateparams['uid'] = $userid;
    $stateparams['value'] = 1;
    $stateparams['qid'] = $question;
    $statesql = "SELECT qas.state
                  FROM {question_attempt_step_data} qasd
                  JOIN {question_attempt_steps} qas ON qas.id = qasd.attemptstepid
                  JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
                 WHERE qasd.name LIKE :name
                   AND qa.questionusageid = :quid
                   AND qas.userid = :uid
                   AND qasd.value = :value
                   AND qa.questionid = :qid";
    $attmpstatevalue = $DB->get_field_sql($statesql, $stateparams);
    return $attmpstatevalue;
}

/**
 * To get quiz wrong answer count.
 * @param int $quizid.
 * @param int $userid.
 * @return array quiz details.
 */
function get_question_palette($attempt, int $quizid, int $userid, $questionid = 0, $ansoption = 0) {
    global $DB;
    $lastattempted = get_quizlast_attempt($attempt->id, $quizid, $userid);
    if (!empty($lastattempted)) {
        $questionpalette = [];
        $palettequestion = [];
        foreach ($lastattempted as $i => $atmpt) {
            $attobj = new stdClass();
            $attobj->question_no = $atmpt->questionid;
            if (!empty($atmpt->responsesummary)
                && ($atmpt->rightanswer == $atmpt->responsesummary)) {
                $attobj->is_answered = true;
                $attobj->is_correct = true;
            } else if (!empty($atmpt->responsesummary)
                && ($atmpt->rightanswer != $atmpt->responsesummary)) {
                $attobj->is_answered = true;
                $attobj->is_correct = false;
            } else {
                $attobj->is_answered = false;
                $attobj->is_correct = false;
            }
            $questionpalette[] = $attobj;
        }
        if ($ansoption > 0 && $questionid > 0) {
            $completed = get_listof_questions_completed($attempt, $userid);
            foreach ($completed as $k => $question) {
                
                $palette = new stdClass();
                $palette->question_no = $question;
                $palette->is_answered = true;
                if ($questionid == $question) {
                    $qanswer = toget_question_answer_submission_details($attempt->id, $userid, $quizid, $question, $ansoption);
                    $palette->is_correct = false;
                    if ($qanswer && $qanswer->fraction > 0) {
                        $palette->is_correct = true;
                    } 
                } else {
                    $answeroption = toget_choosen_answerid($attempt, $question, $userid, 0);
                    $palette->is_correct = false;
                    if (!is_null($answeroption)) {
                        $qnsanswer = toget_question_answer_submission_details($attempt->id, $userid, $quizid, $question, $answeroption);
                        if ($qnsanswer && $qnsanswer->fraction > 0) {
                            $palette->is_correct = true;
                        }
                    }
                }
                $palettequestion[] = $palette;
            }
        }
        if ($ansoption > 0 && $questionid > 0) {
            return $palettequestion;
        } else {
            return $questionpalette;
        }
    } else {
        return [];
    }
}

/**
 * To get the answer submitted value.
 * @param obj $attempt.
 * @param int $userid.
 * @param int $question.
 * @param int $ansoption.
 * @return int $ansoption.
 */
function toget_choosen_answerid($attempt, $question, $userid, $finishquiz) {
    global $DB;
    $answerslistsql = "SELECT id FROM {question_answers} WHERE question = :questionid";
    $answerslist = $DB->get_fieldset_sql($answerslistsql, ['questionid' => $question]);
    $stepparams = [];
    $stepparams['name'] = 'answer';
    $stepparams['quid'] = $attempt->uniqueid;
    $stepparams['uid'] = $userid;
    $stepparams['state'] = 'complete';
    $stepparams['qid'] = $question;
    $stepsql = "SELECT qasd.value
                  FROM {question_attempt_step_data} qasd
                  JOIN {question_attempt_steps} qas ON qas.id = qasd.attemptstepid
                  JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
                 WHERE qasd.name = :name
                   AND qa.questionusageid = :quid
                   AND qas.userid = :uid
                   AND qas.state = :state
                   AND qa.questionid = :qid";
    $attmpstepsdatavalue = $DB->get_field_sql($stepsql, $stepparams);
    if ($finishquiz > 0) {
        return $attmpstepsdatavalue;
    } else {
        $flipanswers = array_flip($answerslist);
        $answeroption = array_search($attmpstepsdatavalue, $flipanswers);
        return $answeroption;
    }
}

/**
 * To get the answer submission details.
 * @param int $attemptid.
 * @param int $userid.
 * @param int $quizid.
 * @param int $question.
 * @param int $ansoption.
 * @return obj answer options records.
 */
function toget_question_answer_submission_details($attemptid, $userid, $quizid, $question, $ansoption) {
    global $DB;
    $params = [];
    $params['attemptid'] = $attemptid;
    $params['userid'] = $userid;
    $params['quizid'] = $quizid;
    $params['questionid'] = $question;
    $params['ansoption'] = $ansoption;
    $sql = "SELECT qans.*
              FROM {question_answers} qans
              JOIN {question_attempts} queatt ON queatt.questionid = qans.question
              JOIN {quiz_attempts} qatt ON qatt.uniqueid = queatt.questionusageid
             WHERE qatt.id = :attemptid
               AND qatt.userid = :userid
               AND qatt.quiz = :quizid
               AND qans.question = :questionid
               AND qans.id = :ansoption";
    $qanswer = $DB->get_record_sql($sql, $params);
    return $qanswer;
}

/**
 * To get time in the 00:00:00 format.
 * @param $timetakeninsec is a timestamp format of time. 
 * @return time in the 00:00:00 format.
 */
function get_time_conversion($timetakensec) {
    $time = gmdate('H:i:s', $timetakensec);
    if ($timetakensec >= DAYINSEC) {
        $floorsectodays = floor($timetakensec / DAYINSEC);
        $daystohrs = $floorsectodays * DAY;
        $remainsecs = $timetakensec - ($floorsectodays * DAYINSEC);
        if ($remainsecs >= HOUR) {
            $floorsectohrs = floor($remainsecs / HOUR);
            $hrstosec = $floorsectohrs * HOUR;
            $secrem = $remainsecs - $hrstosec;
        }
        $noofhrs = $daystohrs + $floorsectohrs;
        $tformat = gmdate('H:i:s', $secrem);
        $tformat = explode(':', $tformat);
        $tformat[0] = $tformat[0] + $noofhrs;
        $time = implode(':', $tformat);
    }
    return $time;
}

/**
 * To get questions count in the quiz.
 * @param $quizid is the quiz id. 
 * @return count of questions.
 */
function count_questions_inquiz($quizid) {
    global $DB;
    $questionsql = "SELECT count(id) as totalquestions
                      FROM {quiz_slots}
                     WHERE quizid = :quizid";
    return $DB->count_records_sql($questionsql, ['quizid' => $quizid]);
}
/**
 * To get more quiz data.
 * @param $cmid is a course module id. 
 * @return more quiz data.
 */
function get_morequiz_data($cmid) {
    global $DB;
    $page = 1;
    $viewrecordstartlimit = ($page * 10) - 10;
    $viewrecordlimit = ($page * 10);
    $allyearbooks = get_all_yearbooks($viewrecordstartlimit, $viewrecordlimit);

    $morequizarray = [];
    foreach ($allyearbooks as $yid => $yearbook) {
        $courseobj = $DB->get_record('course', ['id' => $yearbook->id]);
        $moreqzarray = get_coursemodules_in_course('quiz', $yearbook->id);
        unset($moreqzarray[$cmid]);
        if (count($moreqzarray) > 0) {
            foreach ($moreqzarray as $qid => $moreqz) {
                $morequiz = new stdClass();
                $morequiz->tags = [];
                $morequiztags = get_tag_names($moreqz->instance);
                if (!empty($morequiztags)) {
                    $morequiz->tags = $morequiztags;
                }
                $morequiz->exam_name = '';
                if (!empty($moreqz->name)) {
                    $morequiz->exam_name = $moreqz->name;
                }
                $morequiz->no_of_questions = 0;
                $moreques = count_questions_inquiz($moreqz->instance);
                if (!empty($moreques)) {
                    $morequiz->no_of_questions = $moreques;
                }
                $morequizrecord = $DB->get_record('quiz', ['id' => $moreqz->instance]);
                $morequiz->mark = round($morequizrecord->sumgrades);
                $morequiz->created_on = gmdate('Y-m-d T H:i:s', $morequizrecord->timecreated);
                $morequiz->id = $morequizrecord->id;
                $courseimage = course_summary_exporter::get_course_image($courseobj);
                $morequiz->image = $courseimage;
                $mtimelimit = get_time_conversion($morequizrecord->timelimit);
                $morequiz->time_limit = $mtimelimit;
                $morequizarray[] = $morequiz;
            }
        } else {
            $morequiz = new stdClass();
            $morequiz->tags = [];
            $morequiz->exam_name = '';
            $morequiz->no_of_questions = 0;
            $morequiz->mark = 0;
            $morequiz->created_on = gmdate('Y-m-d T H:i:s', time());
            $morequiz->id = 0;
            $morequiz->image = '';
            $morequiz->time_limit = '00:00:00';
            $morequizarray[] = $morequiz;
        }
    }
    return $morequizarray;
}

/**
 * To get yearbooks list.
 * @param int $startlimit.
 * @param int $endlimit.
 * @return array quiz details.
 */
function get_all_yearbooks($startlimit,$endlimit) {
    global $DB;
    $params = ['open_coursetype' => 1, 'open_module' => 'year_book', 'visible' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c 
                    WHERE c.open_coursetype = :open_coursetype AND c.open_module = :open_module AND c.visible = :visible AND c.format = :format";
    return $DB->get_records_sql($yearbookssql,$params,$startlimit,$endlimit);
}

/**
 * To get yearbooks count.
 * @return array total nubmber of yearbook count.
 */
function get_all_yearbooks_count() {
    global $DB;
    $params = ['open_coursetype' => 1, 'open_module' => 'year_book', 'visible' => 1, 'format' => 'singleactivity'];
    $countsql = "SELECT count(c.id)  
                     FROM {course} AS c 
                    WHERE c.open_coursetype = :open_coursetype AND c.open_module = :open_module AND c.visible = :visible AND c.format = :format";
    return $DB->get_field_sql($countsql, $params);
}

/**
 * To get mocktests list.
 * @param int $startlimit.
 * @param int $endlimit.
 * @return array quiz details.
 */
function get_all_mocktests($startlimit,$endlimit) {
    global $DB,$USER;
    $params=['open_coursetype' => 1, 'open_module' => 'year_book_mocktest', 'visible' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c ";
    if(!is_siteadmin()){
        $yearbookssql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $yearbookssql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $yearbookssql .= " WHERE c.open_coursetype = :open_coursetype AND c.open_module = :open_module AND c.format = :format";
    return $DB->get_records_sql($yearbookssql,$params,$startlimit,$endlimit);
}

/**
 * To get featured exam list.
 * @param int $startlimit.
 * @param int $endlimit.
 * @return array quiz details.
 */
function get_all_featured_mocktests() {
    global $DB,$USER;
    $params=['open_coursetype' => 1, 'open_module' => 'year_book_mocktest', 'isfeaturedexam' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c ";
    if(!is_siteadmin()){
        $yearbookssql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $yearbookssql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $yearbookssql .= " WHERE c.open_coursetype = :open_coursetype AND c.open_module = :open_module  AND c.isfeaturedexam = :isfeaturedexam AND c.format = :format ";
    $yearbookssql .= " ORDER BY c.id DESC LIMIT 2 ";
    return $DB->get_records_sql($yearbookssql,$params);
}

/**
 * To get questions count in the quiz.
 * @return  int total number of mocktests.
 */
function get_all_mocktests_count() {
    global $DB,$USER;
    $params=['open_coursetype' => 1, 'open_module' => 'year_book_mocktest', 'visible' => 1, 'format' => 'singleactivity'];
    $countsql = "SELECT count(c.id)  
                     FROM {course} AS c ";
    if(!is_siteadmin()){
        $countsql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $countsql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $countsql .= " WHERE c.open_coursetype = :open_coursetype AND c.open_module = :open_module AND c.visible = :visible AND c.format = :format";
    return $DB->get_field_sql($countsql,$params);
}

/**
 * To get mocktests count.
 * @param int $quizid.
 * @return int total number of questions in quiz.
 */
function numberof_questions_in_quiz($quizid) {
    global $DB;
    $questionsql = "SELECT count(slot.id) AS total_question 
                            FROM {quiz_slots} slot 
                            WHERE slot.quizid = :quizid";
    return $DB->get_field_sql($questionsql,['quizid' => $quizid]);
}

/**
 * To get course by package id.
 * @param int $startlimit.
 * @param int $endlimit.
 * @param int $packageid.
 * @return object course.
 */
function get_tests_by_packageid($startlimit,$endlimit,$packageid) {
    global $DB;
    $params = ['open_coursetype' => 1, 'open_module' => 'online_exams', 'op_mod' => 'year_book', 'opn_module' => 'year_book_mocktest', 'visible' => 1, 'category' => $packageid, 'format' => 'singleactivity'];
    $testssql = "SELECT c.id, c.fullname
                    FROM {course} AS c 
                    WHERE c.open_coursetype = :open_coursetype 
                    AND (c.open_module = :open_module OR c.open_module = :op_mod OR c.open_module = :opn_module)
                    AND c.visible = :visible
                    AND c.category = :category
                    AND c.format = :format";
    return $DB->get_records_sql($testssql,$params,$startlimit,$endlimit);
}

/**
 * To get course by package id.
 * @param int $startlimit.
 * @param int $endlimit.
 * @param int $packageid.
 * @return object course.
 */
function get_featuredtests_by_packageid($startlimit,$endlimit,$packageid) {
    // global $DB;
    global $DB;
    $params = ['open_coursetype' => 1, 'open_module' => 'online_exams', 'op_mod' => 'year_book', 'visible' => 1, 'isfeaturedexam' => 1, 'category' => $packageid, 'format' => 'singleactivity'];
    $testssql = "SELECT c.id, c.fullname
                    FROM {course} AS c 
                    WHERE c.open_coursetype = :open_coursetype 
                    AND (c.open_module = :open_module OR c.open_module = :op_mod)
                    AND c.visible = :visible
                    AND c.isfeaturedexam = :isfeaturedexam
                    AND c.category = :category
                    AND c.format = :format";
    return $DB->get_records_sql($testssql,$params,$startlimit,$endlimit);
}

/**
 * To get count of tests by package id.
 * @return object course.
 */
function get_tests_count_by_packageid() {
    global $DB;
    $params = ['open_coursetype' => 1, 'open_module' => 'online_exams', 'visible' => 1, 'category' => $packageid, 'format' => 'singleactivity'];
    $testssql = "SELECT count(c.id)
                    FROM {course} AS c 
                    WHERE c.open_coursetype = :open_coursetype 
                    AND c.open_module = :open_module 
                    AND c.visible = :visible
                    AND c.category = :category
                    AND c.format = :format";
    return $DB->get_field_sql($testssql,$params);
}

/**
 * To get mocktests list by userid.
 * @return array quiz details.
 */
function get_all_mocktests_by_userid() {
    global $DB, $USER;
    $params=['open_coursetype' => 1, 'open_module' => 'online_exams', 'op_mod' => 'year_book', 'opn_module' => 'year_book_mocktest', 'visible' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c ";
    if(!is_siteadmin()){
        $yearbookssql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $yearbookssql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $yearbookssql .= " WHERE c.open_coursetype = :open_coursetype AND (c.open_module = :open_module OR c.open_module = :op_mod OR c.open_module = :opn_module) AND c.visible = :visible AND c.format = :format";
    return $DB->get_records_sql($yearbookssql, $params);
}
 /**
 * To get enrol id of the user.
 * @param $courseid current courseid.
 * @param $userid loggedin userid.
 * @return enrol id of the user.
 */
function get_enrolid_of_user_incourse($courseid, $userid) {
    global $DB;
    $enrolidsql = "SELECT e.id as enrolid
                     FROM {user_enrolments} uen
                     JOIN {enrol} e ON e.id = uen.enrolid
                    WHERE e.courseid = :cid AND uen.userid = :uid";
    return $DB->get_field_sql($enrolidsql, ['cid' => $courseid, 'uid' => $userid]);
}

/**
 * To get correct,wrong and unanswered counts of the attempt.
 * @param $questionattmptdetails current courseid.
 * @param $issubmitanswer int default 0.
 * @param $attempt loggedin user attempt.
 * @return enrol id of the user.
 */
function get_cwu_count_of_attempt($questionattmptdetails, $issubmitanswer = 0, $attempt, $ansoption = null) {
    global $USER, $DB;
    if ($issubmitanswer == 0) {
        $unansweredcount = 0;
        $correctcount = 0;
        $wrongcount = 0;
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

        $correctcount = $DB->count_records_sql($csql, [$attempt->uniqueid, $USER->id]);
        $wrongcount = $DB->count_records_sql($wsql, [$attempt->uniqueid, $USER->id]);
        $unansweredcount = $DB->count_records_sql($usql, [$attempt->uniqueid, $USER->id]);
        return [$correctcount, $wrongcount, $unansweredcount];
    } else {
        $ccount = 0;
        $wcount = 0;
        $ucount = 0;
        $totalques = numberof_questions_in_quiz($attempt->quiz);
        $completed = get_listof_questions_completed($attempt, $USER->id);
        $compcount = count($completed);
        $ucount = (int)$totalques - (int)$compcount;
        foreach ($completed as $k => $question) {
            $qanswer = toget_question_answer_submission_details($attempt->id, $attempt->userid, $attempt->quiz, $question, $ansoption);

            $answeroption = toget_choosen_answerid($attempt, $question, $attempt->userid, 0);
            if (!is_null($answeroption)) {
                $qnsanswer = toget_question_answer_submission_details($attempt->id, $attempt->userid, $attempt->quiz, $question, $answeroption);
            }
            if (($qanswer && $qanswer->fraction > 0) || ($qnsanswer && $qnsanswer->fraction > 0)) {
                $ccount ++;
            } else if (($qanswer && $qanswer->fraction <= 0) || ($qnsanswer && $qnsanswer->fraction <= 0)) {
                $wcount ++;
            }
        }
        return [$ccount, $wcount, $ucount];
    }
}

/**
 * To get questions details of the attempt.
 * @param $courseid current courseid.
 * @param $userid loggedin userid.
 * @return enrol id of the user.
 */
function get_question_detailsof_attempt($attemptobj) {
    global $DB;

    $lastattempted = get_quizlast_attempt($attemptobj->id, $attemptobj->quiz, $attemptobj->userid);
    $questiondetails = [];
    $count = 0;
    if ($lastattempted) {
        $qlist = get_questionids_list($attemptobj->quiz, $attemptobj->id);
        foreach ($qlist as $qkey => $qid) {
            $count ++;
            $questionrec = $DB->get_record('question', ['id' => $qid]);
            $questionobj = new stdClass();
            $questionobj->position = $count;
            $questionobj->question_id = $qid;
            $questionobj->question = '';
            if (!empty($questionrec->questiontext)) {
                $questionobj->question = $questionrec->questiontext;
            }
            $questionobj->marks = 0;
            if (!empty($questionrec->defaultmark)) {
                $questionobj->marks = (int) $questionrec->defaultmark;
            }
            $hintsql = "SELECT hint
                          FROM {question_hints}
                         WHERE questionid = :questionid
                         ORDER BY id ASC LIMIT 1";
            $hint = $DB->get_field_sql($hintsql, ['questionid' => $qid]);
            $questionobj->hint = '';
            if (!empty($hint)) {
                $questionobj->hint = $hint;
            }
            $solutionsql = "SELECT *
                              FROM {question_answers}
                             WHERE question = :questionid
                               AND fraction > 0";
            $solutionrec = $DB->get_record_sql($solutionsql, ['questionid' => $qid]);
            if (empty($solutionrec->feedback)) {
                $solution = $solutionrec->answer;
            } else {
                $solution = $solutionrec->feedback;
            }
            $questionobj->solution = $solution;
            $questionobj->question_type = 2;
            if ($questionrec->qtype == 'multichoice') {
                $questionobj->question_type = 1;
            }
            $anssubmitstate = toget_choosen_answerstate_after_finish($attemptobj, $qid, $attemptobj->userid);

            $questionrespsql = "SELECT responsesummary
                                  FROM {question_attempts}
                                 WHERE questionid = :qid
                                   AND questionusageid = :quid";
            $questionresp = $DB->get_field_sql($questionrespsql, ['qid' => $qid, 'quid' => $attemptobj->uniqueid]);
            $questionresponse = null;
            if (!empty($questionresp)) {
                $questionresponse = html_to_text($questionresp);
            }
            $answered = true;
            $studentansweriscorrect = false;
            if (!empty($anssubmitstate) && $anssubmitstate == 'gradedright') {
                $questionobj->student_answer = true;
                $questionobj->student_answer_is_correct = true;
                $studentansweriscorrect = true;
            } else if (!empty($anssubmitstate) && $anssubmitstate == 'gradedwrong') {
                $questionobj->student_answer = true;
                $questionobj->student_answer_is_correct = false;
                $parms = [];
                $parms['qid'] = $qid;
                $csql = "SELECT id
                           FROM {question_answers}
                          WHERE question = :qid ";
                if (!empty($questionresponse)) {
                    $parms['ans'] = '%'.$questionresponse.'%';
                    $csql .= " AND answer LIKE :ans";
                }
                $selectedoptionid = $DB->get_field_sql($csql, $parms);
            } else {
                $questionobj->student_answer = false;
                $questionobj->student_answer_is_correct = false;
                $answered = false;
            }

            
            $ansoptionsql = "SELECT * FROM {question_answers} WHERE question = :questionid";
            $answeroptions = $DB->get_records_sql($ansoptionsql, ['questionid' => $qid]);
            $answers = [];
            foreach ($answeroptions as $akey => $answeroption) {
                $answerobj = new stdClass();
                $studentanswer = false;
                $studentansiscorrect = false;
                $iscorrect = false;
                if ($answeroption->fraction > 0) {
                    $iscorrect = true;
                }
                $ans = html_to_text($answeroption->answer);
                if ($answered === true && $studentansweriscorrect === false && (!is_null($questionresponse) == $ans)) {
                    if ($answeroption->fraction < 1 && $selectedoptionid == $answeroption->id) {
                        $studentanswer = true;
                    }
                }
                if ($answered === true && $studentansweriscorrect === true && (!is_null($questionresponse) == $ans)) {
                    if ($answeroption->fraction > 0) {
                        $studentanswer = true;
                        $studentansiscorrect = true;
                    }
                }
                $answerobj->student_answer = $studentanswer;
                $answerobj->student_answer_is_correct = $studentansiscorrect;
                $answerobj->id = $answeroption->id;
                $answerobj->answer_option = $answeroption->answer;
                $answerobj->is_correct = $iscorrect;
                $answerobj->description = $answeroption->feedback;
                $answers[] = $answerobj;
            }
            $questionobj->answer_options = $answers;
            $questiondetails[] = $questionobj;
        }
        return $questiondetails;
    } else {
        throw new yearbook_exception('Not yet attempted');
    }
}

/**
 * To get list of questions which are to do.
 * @param object $attemptobj.
 * @param int $userid.
 * @return object total number of questions in todo state in quiz.
 */
function get_listof_questions_todo($attemptobj, $userid) {
    global $DB;
    $questionsql = "SELECT quesa.questionid as questionid
                      FROM {question_attempts} quesa
                      JOIN {question_attempt_steps} qas ON qas.questionattemptid = quesa.id
                     WHERE qas.state LIKE :todo
                       AND quesa.questionusageid = :quid
                       AND qas.userid = :uid
                       AND qas.sequencenumber = :seqno";
    $questionstodo = $DB->get_fieldset_sql($questionsql, ['todo' => 'todo', 'quid' => $attemptobj->uniqueid, 'uid' => $userid, 'seqno'=> 0]);
    return $questionstodo;
}

/**
 * To get list of questions which are completed.
 * @param object $attemptobj.
 * @param int $userid.
 * @return object total number of questions in state "complete" in quiz.
 */
function get_listof_questions_completed($attemptobj, $userid) {
    global $DB;
    $questionsql = "SELECT quesa.questionid as questionid
                      FROM {question_attempts} quesa
                      JOIN {question_attempt_steps} qas ON qas.questionattemptid = quesa.id
                     WHERE qas.state LIKE :complete
                       AND quesa.questionusageid = :quid
                       AND qas.userid = :uid";
    $questionscompleted = $DB->get_fieldset_sql($questionsql, ['complete' => 'complete', 'quid' => $attemptobj->uniqueid, 'uid' => $userid]);
    return $questionscompleted;
}

/**
 * To get the graph data of the test perfomance.
 * @return graph data of the user performance.
 */
function get_testperformance_user_graph_data($userid) {
    global $DB;

    $graphs = [];
    for ($i = 0; $i <= 5; $i++) {
        $timemonago = strtotime("-$i months");
        $starttime = strtotime(date('1-m-Y', $timemonago));
        $endtime = strtotime(date('t-m-Y',$timemonago));
        $monthname = date('M', strtotime("-$i month"));
        $sql = "SELECT avg(sumgrades) as gradeavg
                  FROM {quiz_attempts}
                 WHERE timefinish BETWEEN $starttime AND $endtime
                AND userid = :uid
                AND state = :finish";
        $lastavggrade = $DB->get_field_sql($sql, ['uid' => $userid, 'finish' => 'finished']);
        if ($lastavggrade) {
            $obj = new stdClass();
            $obj->avg_mark = format_float($lastavggrade);
            $obj->month = $monthname;
            $graphs[] = $obj;
        } else {
            $emptyobj = new stdClass();
            $emptyobj->avg_mark = null;
            $emptyobj->month = $monthname;
            $graphs[] = $emptyobj;
        }
    }
    return $graphs;
}

/**
 * To get the attempt info of the test perfomance.
 * @return attempt info data of the user performance.
 */
function get_testperformance_attempt_info($userid) {
    global $DB;
    $attinfo = [];
    $sql = "SELECT *
              FROM {quiz_attempts}
             WHERE userid = :uid
             ORDER BY id DESC
             LIMIT 10";
    $atmpts = $DB->get_records_sql($sql, ['uid' => $userid]);
    if ($atmpts) {
        foreach ($atmpts as $id => $atmpt) {
            $obj = new stdClass();
            $obj->last_try_date = date('M d, Y', $atmpt->timestart);
            $examname = $DB->get_field('quiz', 'name', ['id' => $atmpt->quiz]);
            $obj->exam_name = $examname;
            if ($atmpt->sumgrades > 0) {
                $obj->mark = format_float($atmpt->sumgrades);
            } else {
                $obj->mark = format_float(0);
            }
            $rank = 0;
            $rsql = "SELECT qa.*
                       FROM {quiz_attempts} as qa
                      WHERE qa.quiz = :quiz and qa.state = :state ORDER BY qa.sumgrades DESC ";
            $attemptrec = $DB->get_records_sql($rsql, ['quiz' => $atmpt->quiz, 'state' => 'finished']);
            foreach ($attemptrec as $attempt) {
                $rank ++;
                if ($attempt->userid == $userid && $grade->attempt == $attempt->attempt)
                break;
            }
            $obj->rank = $rank;
            $attinfo[] = $obj;
        }
    } else {
        $emptyobj = new stdClass();
        $emptyobj->last_try_date = date('M d, Y', time());
        $emptyobj->exam_name = null;
        $emptyobj->mark = format_float(0);;
        $emptyobj->rank = 0;
        $attinfo[] = $emptyobj;
    }
    return $attinfo;
}

/**
 * To get all test list by userid.
 * @return array quiz details.
 */
function get_all_testslist_by_userid() {
    global $DB,$USER;
    $params=['open_coursetype' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c ";
    if(!is_siteadmin()){
        $yearbookssql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $yearbookssql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $yearbookssql .= " WHERE c.open_coursetype = :open_coursetype AND c.format = :format";
    return $DB->get_records_sql($yearbookssql, $params);
}

/**
 * To get mocktests list by userid.
 * @return array quiz details.
 */
function get_all_tests_by_userid() {
    global $DB, $USER;
    $params = ['open_coursetype' => 1, 'open_module' => 'online_exams', 'op_mod' => 'year_book', 'visible' => 1, 'format' => 'singleactivity'];
    $yearbookssql = "SELECT c.id, c.fullname
                    FROM {course} AS c ";
    if(!is_siteadmin()){
        $yearbookssql .= " JOIN {enrol} AS e ON  e.courseid = c.id ";
        $yearbookssql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
    }
    $yearbookssql .= " WHERE c.open_coursetype = :open_coursetype AND (c.open_module = :open_module OR c.open_module = :op_mod) AND c.format = :format";
    return $DB->get_records_sql($yearbookssql, $params);
}

/**
 * To get rank.
 * @param object $attemptobj.
 * @return rank of the user.
 */
function get_rank_of_the_user($userid) {
    global $DB;
    $totaltests = [];
    $alltests = get_all_tests_by_userid();
    // Quizzes.
    foreach($alltests as $mocktest ) {
        $quiz[] = $DB->get_field('quiz', 'id', ['course' => $mocktest->id]);
    }
    if (!is_siteadmin() && !empty($quiz)) {
        $attempts = quiz_get_user_attempts($quiz, $userid);
        
        foreach ($attempts as $grade) {
            $quizinstance = $DB->get_record('quiz',['id' => $grade->quiz]);
            if ($grade) {
                $rank = 0;
                $attemptrec = $DB->get_records_sql('SELECT qa.* FROM {quiz_attempts} as qa WHERE qa.quiz = :quiz and qa.state = :state ORDER BY qa.sumgrades DESC ', ['quiz' => $grade->quiz, 'state' => 'finished']);
                foreach ($attemptrec as $attempt) {
                    $rank++;
                    if($attempt->userid == $userid && $grade->attempt == $attempt->attempt)
                    break;
                }
                // $gradereceived = quiz_rescale_grade($grade->sumgrades, $quizinstance, false);
                $testobj = new stdClass();
                $testobj->rank = $rank;
                $totaltests[] = $testobj;
            }
        }
    }
    return $totaltests;
}

/**
 * Class extended for throwing yearbook exceptions.
 */
class yearbook_exception extends \moodle_exception {
}
