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
 * units hierarchy
 *
 * This file defines the current version of the local_yearbook Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_yearbook
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\external\helper_for_get_mods_by_courses;
use core_external\external_api;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_external\util;
use mod_quiz\access_manager;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use core_course\external\course_summary_exporter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/local/yearbook/lib.php');
require_once($CFG->dirroot.'/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
class local_yearbook_external extends external_api {
    /**
     * Utility function for validating a quiz.
     *
     * @param int $quizid quiz instance id
     * @return array array containing the quiz, course, context and course module objects
     * @since  Moodle 3.1
     */
    protected static function validationof_quiz($quizid) {
        global $DB;

        // Request and permission validation.
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        return [$quiz, $course, $cm, $context];
    }

    /**
     * Describes the parameters for quiz list.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function quiz_list_parameters() {
        return new external_function_parameters (
            [
                'page' => new external_value(PARAM_INT, 'page id'),
            ]
        );
    }

    /**
     * get list of the quiz.
     *
     * @param int $page try id.
     * @return object of the quiz data.
     * @since Moodle 3.1
     */
    public static function quiz_list(int $page) {
        global $DB, $USER;
        $params = [
            'page' => $page,
        ];
        $currenttime = time();
        $total_quiz = [];
        $quizids = [];
        $sumofgrades = [];
        $totalfeaturedtests = [];
        // Set limits.
        $viewrecordstartlimit = ($page*10) - 10;
        $viewrecordlimit = ($page*10);
        // Get yearbooks.
        $yearbooks = get_all_yearbooks($viewrecordstartlimit,$viewrecordlimit);

        // Get Total yearbook quiz count.
        $quizcount = get_all_yearbooks_count();
        $featuredmocktests = get_all_featured_mocktests();
        // $featuredmocktestscount = count($featuredmocktests);

        // Count total number of response pages.
        $total_pages=(int)($quizcount/10);
        if ( ( ( $quizcount % 10) > 0 ) || ( $quizcount < 10 ) ){
            $total_pages = $total_pages + 1;
        }

        // Quizzes for yearbooks.
        foreach( $yearbooks as $yearbook ) {
            $activequizsql = "SELECT q.*
                                FROM {quiz} q
                               WHERE q.course = :course
                               AND (
                                (q.timeopen = 0 AND q.timeclose = 0)
                                OR ($currenttime BETWEEN q.timeopen AND q.timeclose)
                                OR ((q.timeopen != 0 AND q.timeopen < $currenttime) AND q.timeclose = 0)
                                OR (
                                    (q.timeopen = 0 AND (q.timeclose != 0 AND q.timeclose > $currenttime)
                                        )
                                    )
                                )";
            $quiz = $DB->get_record_sql($activequizsql, ['course' => $yearbook->id]);
            if (!empty($quiz)) {
                $quizids[] = $quiz->id;
                $questioncount = 0;
                $questionsql = "SELECT count(slot.id) AS total_question
                                FROM {quiz_slots} slot
                                WHERE slot.quizid = :quizid";
                $questioncount = $DB->get_field_sql($questionsql,['quizid' => $quiz->id]);
                $courseid =$DB->get_field('quiz','course', ['id' => $quiz->id]);
                $course =$DB->get_record('course', ['id' => $courseid]);
                // Course image.
                $courseimage = course_summary_exporter::get_course_image($course);
                // if (!$courseimage) {
                //     $courseimage = $OUTPUT->get_generated_image_for_id($course->id);
                // }
                if ($quiz->sumgrades > 0) {
                    $mark = format_float($quiz->sumgrades);
                } else {
                    $mark = format_float($quiz->grade);
                }

                $tags=get_tag_names($quiz->id);
                $quizobj = new stdClass();
                $quizobj->tags = !empty($tags) ? $tags : [];
                $quizobj->exam_name = $yearbook->fullname;
                $quizobj->details = $quiz->intro;
                $quizobj->no_of_questions = $questioncount;
                $quizobj->mark = $mark;
                $quizobj->id = $quiz->id;
                $quizobj->image = $courseimage;
                $quizobj->time_limit = gmdate('H:i:s', $quiz->timelimit);
                $quizobj->created_on = gmdate('Y-m-d T H:i:s', $quiz->timecreated);
                $quizobj->time_limit_sec = $quiz->timelimit;
                $total_quiz[]=$quizobj;
            }
        }
        $attemptcount = 0;
        $totalscore = 0;
        if (!empty($quizids)) {
            $allattempts = quiz_get_user_attempts($quizids, $USER->id, 'all');
            foreach ($allattempts as $a => $atmt) {
                $sumofgrades[] = $atmt->sumgrades;
            }
            $attemptcount = count($allattempts);
            if (!empty($sumofgrades)) {
                $totalscore = (int) array_sum($sumofgrades);
            }
        }

        foreach ($featuredmocktests as $featuredmocktest) {
            $quiz = $DB->get_record('quiz', ['course' => $featuredmocktest->id]);
            $courseid = $DB->get_field('quiz', 'course', ['id' => $quiz->id]);
            $course = $DB->get_record('course', ['id' => $courseid]);
            // Course image.
            $courseimage = course_summary_exporter::get_course_image($course);
            if ($quiz->sumgrades > 0) {
                $marks = format_float($quiz->sumgrades);
            } else {
                $marks = format_float($quiz->grade);
            }
            $questioncount = 0;
            // Number of questions in quiz.
            $questioncount = numberof_questions_in_quiz($quiz->id);
            $tags = get_tag_names($quiz->id);
            $featuredtestobj = new stdClass();
            $featuredtestobj->tags = !empty($tags) ? $tags : [];
            // $featuredtestobj->name = $featuredmocktest->fullname;
            // $featuredtestobj->description = $quiz->intro;
            $featuredtestobj->exam_name = $featuredmocktest->fullname;
            $featuredtestobj->details = $quiz->intro;
            $featuredtestobj->no_of_questions = $questioncount;
            $featuredtestobj->mark = $marks;
            $featuredtestobj->created_on = gmdate('Y-m-d T H:i:s', $quiz->timecreated);
            $featuredtestobj->id = $quiz->id;
            $featuredtestobj->image = $courseimage;
            // $featuredtestobj->exam_count = 1;
            $featuredtestobj->time_limit = gmdate('H:i:s', $quiz->timelimit);
            $featuredtestobj->time_limit_sec = $quiz->timelimit;
            $totalfeaturedtests[] = $featuredtestobj;
        }

        return [
            'status' => 'success',
            'exam_list' => $total_quiz,
            'featured_exam_list' => $totalfeaturedtests,
            'page' => $page,
            'code' => 1,
            'total_pages' => $total_pages,
            'total_score' => $totalscore,
            'attempt_count' => $attemptcount,
        ];

    }

    /**
     * Describes the quiz list return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function quiz_list_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'exam_list' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'details' => new external_value(PARAM_RAW, 'details'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                    'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec'),
                ]),
            ),
            'featured_exam_list' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'details' => new external_value(PARAM_RAW, 'details'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                    'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec'),
                ]),
            ),
            'page' => new external_value(PARAM_INT, 'page'),
            'total_pages' => new external_value(PARAM_INT, 'total_pages'),
            'code' => new external_value(PARAM_INT, 'code'),
            'total_score' => new external_value(PARAM_INT, 'total_score'),
            'attempt_count' => new external_value(PARAM_INT, 'attempt_count'),
        ]);
    }

    /**
     * Describes the parameters for begin_quiz.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function begin_quiz_parameters() {
        return new external_function_parameters (
            [
                'quiz_id' => new external_value(PARAM_INT, 'quiz instance id'),
                'preflightdata' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            // 'name' => new external_value(PARAM_ALPHANUMEXT, 'data name'),
                            // 'value' => new external_value(PARAM_RAW, 'data value'),
                        ]
                    ), 'Preflight required data (like passwords)', VALUE_DEFAULT, []
                ),
                'forcenew' => new external_value(PARAM_BOOL, 'Whether to force a new attempt or not.', VALUE_DEFAULT, true),
            ]
        );
    }

    /**
     * Starts a new attempt at a quiz.
     *
     * @param int $quizid quiz instance id
     * @param array $preflightdata preflight required data (like passwords)
     * @param bool $forcenew Whether to force a new attempt or not.
     * @return object of the attempt basic data
     * @since Moodle 3.1
     */
    public static function begin_quiz(int $quizid, $preflightdata = [], $forcenew = true) {
        global $DB, $USER;
        $objattempt = new stdClass();
        $params = [
            'quiz_id' => $quizid,
            'preflightdata' => $preflightdata,
            'forcenew' => $forcenew,
        ];

        // Validating parameters.
        $params = self::validate_parameters(self::begin_quiz_parameters(), $params);

        list($quiz, $course, $cm, $context) = self::validationof_quiz($params['quiz_id']);

        $quizobj = quiz_settings::create($cm->instance, $USER->id);
        $cmid = $quizobj->get_cmid();

        // Check questions.
        if (!$quizobj->has_questions()) {
            throw new yearbook_exception('noquestionsfound', 'quiz', $quizobj->view_url());
        }

        // Create an object to manage all the other (non-roles) access rules.
        $timenow = time();
        $accessmanager = $quizobj->get_access_manager($timenow);

        // Validate permissions for creating a new attempt and start a new preview attempt if required.
        list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
            quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, -1, false);
        if ($lastattempt->attempt) {
            $attemptnumber = $lastattempt->attempt + 1;
        }

        // Check access.
        if (!$quizobj->is_preview_user() && $messages) {
            // Create warnings with the exact messages.
            foreach ($messages as $message) {
                $warnings[] = [
                    'item' => 'quiz',
                    'itemid' => $quiz->id,
                    'warningcode' => '1',
                    'message' => clean_text($message, PARAM_TEXT)
                ];
            }
        } else {
            if ($accessmanager->is_preflight_check_required($currentattemptid)) {
                // Need to do some checks before allowing the user to continue.

                $provideddata = [];
                foreach ($params['preflightdata'] as $data) {
                    $provideddata[$data['name']] = $data['value'];
                }

                $errors = $accessmanager->validate_preflight_check($provideddata, [], $currentattemptid);

                if (!empty($errors)) {
                    throw new yearbook_exception(array_shift($errors), 'quiz', $quizobj->view_url());
                }

                // Pre-flight check passed.
                $accessmanager->notify_preflight_check_passed($currentattemptid);
            }
            if ($currentattemptid) {
                if ($lastattempt->state == quiz_attempt::OVERDUE) {
                    throw new yearbook_exception('stateoverdue', 'quiz', $quizobj->view_url());
                }
            }
            $offlineattempt = WS_SERVER ? true : false;
            $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt, $offlineattempt);
        }

        if (!empty($quiz)) {
            if (empty($attempt->id)) {
                throw new yearbook_exception('contactadmin', 'local_yearbook');
            } else {
                // To get question ids from the quiz in the array format.
                $questionids = get_questionids_list($params['quiz_id'], $attempt->id);
            }

            if (!empty($questionids)) {
                $nextids = [];

                // To get first question id from the array.
                $firstquestionid = current($questionids);
                foreach ($questionids as $qkey => $value) {
                    if (!empty(get_next_questionid($questionids, $qkey))) {
                        $nextids[] = get_next_questionid($questionids, $qkey);
                    }
                }
                $questionrec = $DB->get_record('question', ['id' => $firstquestionid]);
                $questiontype = 2;
                if ($questionrec->qtype == 'multichoice') {
                    $questiontype = 1;
                }

                $objattempt->question_type = $questiontype;
                $objattempt->position = 1;
                $objattempt->status = "success";

                // To get next question id from the array.
                $nextquestionid = current($nextids);

                $objattempt->next_question_id = 0;
                if (!empty($nextquestionid)) {
                    $objattempt->next_question_id = (int) $nextquestionid;
                }

                // To get answerslist of the first question in the array.
                $answerslistsql = "SELECT id, answer as answer_option FROM {question_answers} WHERE question = :questionid";
                $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $firstquestionid]);
                $objattempt->answer_list = $answers;

                $recordattempt = get_attemptrec_quiz($params['quiz_id'], $USER->id);

                $objattempt->question_id = $firstquestionid;
                $objattempt->question_text = $questionrec->questiontext;
                $objattempt->exam_name = $quiz->name;

                $objattempt->questions_list = $questionids;
                // To get more quiz data.
                $morequizdata = get_morequiz_data($cmid);
                $objattempt->more_quiz = $morequizdata;

                $timetakenseconds = 0;
                $timetaken = '00:00:00';
                // $quizattrecords = quiz_get_user_attempts($params['quiz_id'], $USER->id, 'finished');
                // $attemptrec = end($quizattrecords);
                // if (!empty($attemptrec)) {
                if ($attempt->state == 'finished') {
                    $timetakenseconds = ($attempt->timefinish - $attempt->timestart);
                    // To get time taken by the user of the attempt.
                    if ($timetakenseconds > 0) {
                        $timetaken = get_time_conversion($timetakenseconds);
                    }
                } else {
                    $timetakenseconds = (time() - $attempt->timestart);
                    // To get time taken by the user of the attempt.
                    if ($timetakenseconds > 0) {
                        $timetaken = get_time_conversion($timetakenseconds);
                    }
                }

                $objattempt->try_id = $recordattempt->id;
                $objattempt->code = 1;

                $tags = get_tag_names($params['quiz_id']);

                $objattempt->tags = [];
                if (!empty($tags)) {
                    $objattempt->tags = $tags;
                }

                $timelimit = gmdate('H:i:s', $quiz->timelimit);
                $objattempt->time_limit = $timelimit;

                $objattempt->time_taken = $timetaken;

                $timelimitsec = 0;
                if (!empty($quiz->timelimit)) {
                    $timelimitsec = $quiz->timelimit;
                }
                
                $objattempt->instructions = $quiz->intro;
                $objattempt->time_taken_sec = $timetakenseconds;
                $objattempt->time_limit_sec = $timelimitsec;
                $objattempt->details = $quiz->intro;

                return $objattempt;
            } else {
                throw new yearbook_exception('errorprocessing', 'local_yearbook');
            }
        }
         else {
            throw new yearbook_exception('quiznotfound', 'local_yearbook');
        }
    }

    /**
     * Describes the begin_quiz return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function begin_quiz_returns() {
        return new external_single_structure([
            'question_type' => new external_value(PARAM_INT, 'question_type'),
            'position' => new external_value(PARAM_INT, 'position'),
            'status' => new external_value(PARAM_TEXT, 'status'),
            'next_question_id' => new external_value(PARAM_INT, 'next_question_id'),
            'answer_list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'id'),
                    'answer_option' => new external_value(PARAM_RAW, 'answer_option'),
                ]),
            ),
            'question_id' => new external_value(PARAM_INT, 'question_id'),
            'question_text' => new external_value(PARAM_RAW, 'question_text'),
            'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
            'questions_list' => new external_multiple_structure(
                new external_value(PARAM_INT, 'question ids'),
            ),

           'more_quiz' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                ]),
            ),
            'try_id' => new external_value(PARAM_INT, 'try_id'),
            'code' => new external_value(PARAM_INT, 'code'),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'tags'),
            ),
            'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
            'time_taken' => new external_value(PARAM_RAW, 'time_taken'),
            'instructions' => new external_value(PARAM_RAW, 'instructions', VALUE_OPTIONAL),
            'time_taken_sec' => new external_value(PARAM_INT, 'time_taken_sec'),
            'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec'),
            'details' => new external_value(PARAM_RAW, 'details'),
        ]);
    }

    /**
     * Utility function for validating a given attempt
     *
     * @param  array $params array of parameters including the attemptid and preflight data
     * @param  bool $checkaccessrules whether to check the quiz access rules or not
     * @param  bool $failifoverdue whether to return error if the attempt is overdue
     * @return  array containing the attempt object and access messages
     * @since  Moodle 3.1
     */
    protected static function validationof_attempt($params, $checkaccessrules = true, $failifoverdue = true) {
        global $USER;
        $attemptobj = quiz_attempt::create($params['try_id']);

        $context = context_module::instance($attemptobj->get_cm()->id);
        self::validate_context($context);

        // Check that this attempt belongs to this user.
        if ($attemptobj->get_userid() != $USER->id) {
            throw new yearbook_exception('notyourattempt', 'quiz', $attemptobj->view_url());
        }

        // General capabilities check.
        $ispreviewuser = $attemptobj->is_preview_user();
        if (!$ispreviewuser) {
            $attemptobj->require_capability('mod/quiz:attempt');
        }

        // Check the access rules.
        $accessmanager = $attemptobj->get_access_manager(time());
        $messages = [];
        if ($checkaccessrules) {
            // If the attempt is now overdue, or abandoned, deal with that.
            $attemptobj->handle_if_time_expired(time(), true);

            $messages = $accessmanager->prevent_access();
            if (!$ispreviewuser && $messages) {
                throw new yearbook_exception('attempterror', 'quiz', $attemptobj->view_url());
            }
        }

        // Attempt closed?.
        // $notskipquestion = true;
        // if (count($params) <= 2) {
        //     $notskipquestion = false;
        // }
        if ($attemptobj->is_finished()/* && $notskipquestion*/) {
            throw new yearbook_exception('attemptalreadyclosed', 'quiz', $attemptobj->view_url());
        } else if ($failifoverdue && $attemptobj->get_state() == quiz_attempt::OVERDUE) {
            throw new yearbook_exception('stateoverdue', 'quiz', $attemptobj->view_url());
        }

        // User submitted data (like the quiz password).
        if ($accessmanager->is_preflight_check_required($attemptobj->get_attemptid())) {
            $provideddata = [];
            foreach ($params['preflightdata'] as $data) {
                $provideddata[$data['name']] = $data['value'];
            }

            $errors = $accessmanager->validate_preflight_check($provideddata, [], $params['try_id']);
            if (!empty($errors)) {
                throw new yearbook_exception(array_shift($errors), 'quiz', $attemptobj->view_url());
            }
            // Pre-flight check passed.
            $accessmanager->notify_preflight_check_passed($params['try_id']);
        }

        if (isset($params['page'])) {
            // Check if the page is out of range.
            if ($params['page'] != $attemptobj->force_page_number_into_range($params['page'])) {
                throw new yearbook_exception('Invalid page number', 'quiz', $attemptobj->view_url());
            }

            // Prevent out of sequence access.
            if (!$attemptobj->check_page_access($params['page'])) {
                throw new yearbook_exception('Out of sequence access', 'quiz', $attemptobj->view_url());
            }

            // Check slots.
            $slots = $attemptobj->get_slots($params['page']);

            if (empty($slots)) {
                throw new yearbook_exception('noquestionsfound', 'quiz', $attemptobj->view_url());
            }
        }

        return [$attemptobj, $messages];
    }

    /**
     * Describes the parameters for submit answer.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function submit_answer_parameters() {
        return new external_function_parameters (
            [
                'try_id' => new external_value(PARAM_INT, 'Try id'),
                'question_id' => new external_value(PARAM_INT, 'Question id'),
                'answer_option' => new external_value(PARAM_INT, 'Selected answer option'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'name' => new external_value(PARAM_ALPHANUMEXT, 'data name'),
                            'value' => new external_value(PARAM_RAW, 'data value'),
                        ]
                    ), 'Data', VALUE_DEFAULT, []
                ),
            ]
        );
    }

    /**
     * Submits answer in the quiz.
     *
     * @param int $tryid try id.
     * @param int $questionid question id which is to be submitted.
     * @param int $ansoption selected option id from the options.
     * @return object of the submitted quiz data.
     * @since Moodle 3.1
     */
    public static function submit_answer(int $tryid, int $questionid, int $ansoption, $data = []) {
        global $DB, $USER;
        $submission = new stdClass();
        $params = [
            'try_id' => $tryid,
            'question_id' => $questionid,
            'answer_option' => $ansoption,
            'data' => $data,
        ];
        // Validating parameters.
        $params = self::validate_parameters(self::submit_answer_parameters(), $params);
        $quizattmptid = $params['try_id'];
        $timenow = time();
        // Add a page, required by validate_attempt.
        list($attemptobj, $messages) = self::validationof_attempt($params);

        $qarecord = $DB->get_record('question_attempts', ['questionusageid' => $attemptobj->get_attempt()->uniqueid, 'questionid' => $questionid]);

        if ($qarecord->slot) {
            $prefix = 'q'. $attemptobj->get_attempt()->uniqueid .':' . $qarecord->slot .'_';
            $answerslistsql = "SELECT id FROM {question_answers} WHERE question = :questionid";
            $answers = $DB->get_fieldset_sql($answerslistsql, ['questionid' => $questionid]);
            $data = [
                ['name' => 'slot', 'value' => $qarecord->slot],
                ['name' => $prefix . ':sequencecheck',
                        'value' => $attemptobj->get_question_attempt($qarecord->slot)->get_sequence_check_count()],
                ['name' => $prefix . 'answer', 'value' => (int) array_search($ansoption, $answers)],
            ];
            // print_r($attemptobj->get_question_attempt($slotinfo)->get_sequence_check_count());
        }
        // }

        // Prevent functions like file_get_submitted_draft_itemid() or form library requiring a sesskey for WS requests.
        if (WS_SERVER || PHPUNIT_TEST) {
            $USER->ignoresesskey = true;
        }

        $transaction = $DB->start_delegated_transaction();

        // Create the $_POST object required by the question engine.
        $_POST = [];
        foreach ($data as $element) {
            $_POST[$element['name']] = $element['value'];
            // Some deep core functions like file_get_submitted_draft_itemid() also requires $_REQUEST to be filled.
            $_REQUEST[$element['name']] = $element['value'];
        }
        // Update the timemodifiedoffline field.
        $attemptobj->set_offline_modified_time($timenow);
        $attemptobj->process_auto_save($timenow);
        $transaction->allow_commit();

        $sql = "UPDATE {question_attempts} set flagged = 1
                 WHERE questionid = :questionid
                   AND questionusageid = :quid";
        $updateflag = $DB->execute($sql, ['questionid' => $questionid, 'quid' => $attemptobj->get_attempt()->uniqueid]);
        
        $quizdetails = get_quizby_tryid_userid($quizattmptid, $USER->id);
        list($quiz, $course, $cm, $context) = self::validationof_quiz($quizdetails->quiz);

        if (!empty($quizdetails)) {

            // $studentoption = [];

            // $studentoption[] = $ansoption;

            // $questionids = get_questionids_list($quizdetails->quiz);
            $questionids = get_listof_questions_todo($attemptobj->get_attempt(), $USER->id);
            $answeredids = get_listof_questions_completed($attemptobj->get_attempt(), $USER->id);

            $nextids = [];
            $nextnextids = [];
            foreach ($questionids as $qkey => $value) {
                if ($value == $questionid) {
                    if (!empty(get_next_questionid($questionids, $qkey))) {
                        $nextids[] = get_next_questionid($questionids, $qkey);
                    }
                }
            }
            $nextquestionid = current($nextids);
            foreach ($questionids as $qkey => $value) {
                if ($value == $nextquestionid) {
                    if (!empty(get_next_questionid($questionids, $qkey))) {
                        $nextnextids[] = get_next_questionid($questionids, $qkey);
                    }
                }
            }
            $recordattempt = get_attemptrec_quiz($quiz->id, $USER->id);

            // To get next question id from the array.
            $nextnextquestionid = current($nextnextids);

            $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);

            // $answeredids = array_diff($questionids, $nextids);

            $questiontype = 2;
            if ($questionrec->qtype == 'multichoice') {
                $questiontype = 1;
            }
            $qids = get_questionids_list($quiz->id, $attemptobj->get_attempt()->id);

            $submission->question_type = $questiontype;
            $studentoption = [];

            if (!empty($answeredids)) {
                if (empty($nextquestionid)) {
                    $nextquestionid = current($qids);
                }
                if (in_array($nextquestionid, $answeredids)) {
                    $answroption = toget_choosen_answerid($attemptobj->get_attempt(), $nextquestionid, $USER->id, 0);
                    $studentoption[] = $answroption;
                }
            }
            $submission->student_option = $studentoption;

            $submission->next_question_id = (int) $nextnextquestionid;
            if (empty($nextnextquestionid) && empty($nextquestionid)) {
                $firstquestionid = current($qids);
                $nextnextquestionid = next($qids);
                $submission->next_question_id = (int) $nextnextquestionid;
            } else if (empty($nextnextquestionid) && !empty($nextquestionid)) {
                $nextnextquestionid = current($qids);
                $submission->next_question_id = (int) $nextnextquestionid;
            }
            $islastquestion = false;
            if (empty($nextquestionid)) {
                $islastquestion = true;
            }

            // To get answerslist of the question in the array.
            $answerslistsql = "SELECT id, answer as answer_option FROM {question_answers} WHERE question = :questionid";
            if (!empty($nextquestionid) && !empty($nextnextquestionid)) {
                $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
                $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);
                $questiontext = $questionrec->questiontext;
            } else if (!empty($nextquestionid) && empty($nextnextquestionid)) {
                $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
                $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);
                $questiontext = $questionrec->questiontext;
            } 

            if (empty($nextquestionid)) {
                $qlistagain = get_questionids_list($quiz->id, $attemptobj->get_attempt()->id);
                $firstquestionagain = current($qlistagain);
                $nextquestionid = (int) $firstquestionagain;
                $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
                $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);
                $questiontext = $questionrec->questiontext;
            }
            $submission->question_id = $nextquestionid;
            $submission->question_text = $questiontext;
            $submission->exam_name = $quiz->name;

            $submission->questions_list = $questionids;
            $array = [];
            $array['questionid'] = $nextquestionid;
            $array['questionusageid'] = $attemptobj->get_attempt()->uniqueid;
            $pcount = $DB->get_field('question_attempts', 'slot', $array);

            $submission->position = $pcount;
            $submission->answer_list = $answers;

            $submission->answered_questions_id = $answeredids;
            $timelimitsec = 0;
            if (!empty($quiz->timelimit)) {
                $timelimitsec = $quiz->timelimit;
            }
            $timelimit = gmdate('H:i:s', $quiz->timelimit);
            $submission->time_limit = $timelimit;
            $submission->is_last_question = $islastquestion;
            $submission->instructions = $quiz->intro;

            $submission->status = 'success';
            $submission->try_id = $quizattmptid;
            
            // To get the cmid.
            $cmid = $attemptobj->get_cmid();

            $quizattrecords = quiz_get_user_attempts($quizdetails->quiz, $USER->id, 'all');
            $quizattrecord = end($quizattrecords);

            $quizid = $quizdetails->quiz;
            $quizrecord = $DB->get_record('quiz', ['id' => $quizid]);
            if (!empty($quizrecord->sumgrades)) {
                $tmark = round($quizrecord->sumgrades);
            } else {
                $tmark = round($quizrecord->grade);
            }

            $markscored = format_float(0);
            $timetakenseconds = 0;
            $timetaken = '00:00:00';
            $correctcount = 0;
            $wrongcount = 0;
            $unansweredcount = 0;
            $finished = false;
            $examfinished = '';
            $emptyobj = new stdClass();
            $emptyobj->question_no = 0;
            $emptyobj->is_answered = false;
            $emptyobj->is_correct = false;
            $questionpalette[] = $emptyobj;
            if (!empty($quizattrecord)) {
                if ($quizattrecord->sumgrades > 0) {
                    $markscored = format_float($quizattrecord->sumgrades);
                }

                if ($quizattrecord->state == 'finished') {
                    $timetakenseconds = ($quizattrecord->timefinish - $quizattrecord->timestart);
                    // To get time taken by the user of the attempt.
                    if ($timetakenseconds > 0) {
                        $timetaken = get_time_conversion($timetakenseconds);
                    }
                } else {
                    $timetakenseconds = (time() - $quizattrecord->timestart);
                    // To get time taken by the user of the attempt.
                    if ($timetakenseconds > 0) {
                        $timetaken = get_time_conversion($timetakenseconds);
                    }
                }

                // To get last quiz attempt contains questions related data.
                $questionattmptdetails = get_quizlast_attempt($quizattrecord->id, $quizattrecord->quiz, $USER->id);
                // To get the correct, wrong and unanswered counts of the attempt.
                list($correctcount, $wrongcount, $unansweredcount) = get_cwu_count_of_attempt($questionattmptdetails, 1, $attemptobj->get_attempt(), $params['answer_option']);
                // To get the question palette.
                $questionpalette = get_question_palette($attemptobj->get_attempt(), $quizattrecord->quiz, $USER->id, $questionid, $ansoption);
                if ($quizattrecord->state == 'finished') {
                    $finished = true;
                    $examfinished = 'Exam is finished';
                }
            } else {
                throw new yearbook_exception('attemptnotfound', 'local_yearbook');
            }

            $submission->total_mark = $tmark;
            $submission->mark_scored = $markscored;
            $submission->time_taken = $timetaken;
            $submission->time_taken_sec = (int) $timetakenseconds;

            $submission->no_of_questions = 0;
            // To get questions count of the quiz.
            $cqueries = count_questions_inquiz($quizid);
            if (!empty($cqueries)) {
                $submission->no_of_questions = $cqueries;
            }

            // To get the tag names of the quiz.
            $tags = get_tag_names($quizid);
            $submission->tags = [];
            if (!empty($tags)) {
                $submission->tags = $tags;
            }

            $submission->correct_answer_count = $correctcount;
            $submission->wrong_answer_count = $wrongcount;
            $submission->unanswered_count = $unansweredcount;
            // To get the more quiz data.
            $morequizdata = get_morequiz_data($cmid);
            $submission->more_quiz = $morequizdata;
            $finalrank = 0;
            $ranks = get_rank_of_the_user($USER->id);
            $r = 0;
            $urank = [];
            foreach ($ranks as $k => $rank) {
                $urank[] = $rank->rank;
                $r ++;
                if ($r == 1) {
                    break;
                }
            }
            if (!empty($urank)) {
                $finalrank = implode(',', $urank);
            }
            $submission->total_rank = $finalrank;
            $submission->rank = $finalrank;
            $submission->wrong_answer_mark = '';
            $submission->wrong_answer_mark_app = 0;
            $submission->question_palette = $questionpalette;

            $submission->is_exam_finished = $finished;
            $submission->message = $examfinished;
            $submission->code = 1;

            return $submission;
        } else {
            throw new yearbook_exception('providecorrectdetails', 'local_yearbook');
        }
    }

    /**
     * Describes the submit answer return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function submit_answer_returns() {
        return new external_single_structure([
            'question_type' => new external_value(PARAM_INT, 'question_type', VALUE_OPTIONAL),
            'student_option' => new external_multiple_structure(
                new external_value(PARAM_INT, 'student_option'), 'student_option', VALUE_OPTIONAL
            ),
            'questions_list' => new external_multiple_structure(
                new external_value(PARAM_INT, 'questions_list'), 'questions_list', VALUE_OPTIONAL
            ),
            'exam_name' => new external_value(PARAM_RAW, 'exam_name', VALUE_OPTIONAL),
            'position' => new external_value(PARAM_INT, 'position', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_TEXT, 'status'),
            'next_question_id' => new external_value(PARAM_INT, 'next_question_id', VALUE_OPTIONAL),
            'answer_list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                    'answer_option' => new external_value(PARAM_RAW, 'answer_option', VALUE_OPTIONAL),
                ]), '', VALUE_OPTIONAL 
            ),
            'question_id' => new external_value(PARAM_INT, 'question_id', VALUE_OPTIONAL),
            'question_text' => new external_value(PARAM_RAW, 'question_text', VALUE_OPTIONAL),
            'try_id' => new external_value(PARAM_INT, 'try_id', VALUE_OPTIONAL),
            'total_mark' => new external_value(PARAM_INT, 'total_mark', VALUE_OPTIONAL),
            'mark_scored' => new external_value(PARAM_FLOAT, 'mark_scored', VALUE_OPTIONAL),
            // 'time_taken_sec' => new external_value(PARAM_INT, 'time_taken_sec', VALUE_OPTIONAL),
            'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions', VALUE_OPTIONAL),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'tags'), 'tags', VALUE_OPTIONAL
            ),
            'correct_answer_count' => new external_value(PARAM_INT, 'correct_answer_count', VALUE_OPTIONAL),
            'wrong_answer_count' => new external_value(PARAM_INT, 'wrong_answer_count', VALUE_OPTIONAL),
            'unanswered_count' => new external_value(PARAM_INT, 'unanswered_count', VALUE_OPTIONAL),
            'more_quiz' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                ]), 'more_quiz', VALUE_OPTIONAL
            ),
            'answered_questions_id' => new external_multiple_structure(
                new external_value(PARAM_INT, 'answered_questions_id'), 'answered_questions_id', VALUE_OPTIONAL
            ),
            'time_limit' => new external_value(PARAM_RAW, 'time_limit', VALUE_OPTIONAL),
            'time_taken' => new external_value(PARAM_RAW, 'time_taken', VALUE_OPTIONAL),
            'is_last_question' => new external_value(PARAM_BOOL, 'is_last_question', VALUE_OPTIONAL),
            'instructions' => new external_value(PARAM_RAW, 'instructions', VALUE_OPTIONAL),
            'time_taken_sec' => new external_value(PARAM_INT, 'time_taken_sec', VALUE_OPTIONAL),
            'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec', VALUE_OPTIONAL),
            'total_rank' => new external_value(PARAM_INT, 'total_rank', VALUE_OPTIONAL),
            'rank' => new external_value(PARAM_INT, 'rank', VALUE_OPTIONAL),
            'wrong_answer_mark' => new external_value(PARAM_RAW, 'wrong_answer_mark', VALUE_OPTIONAL),
            'wrong_answer_mark_app' => new external_value(PARAM_RAW, 'wrong_answer_mark_app', VALUE_OPTIONAL),
            'question_palette' => new external_multiple_structure(
                new external_single_structure([
                    'question_no' => new external_value(PARAM_INT, 'question_no'),
                    'is_answered' => new external_value(PARAM_BOOL, 'is_answered'),
                    'is_correct' => new external_value(PARAM_BOOL, 'is_correct'),
                ]), 'question_palette', VALUE_OPTIONAL
            ),
            'is_exam_finished' => new external_value(PARAM_BOOL, 'is_exam_finished', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_RAW, 'message', VALUE_OPTIONAL),
            'code' => new external_value(PARAM_INT, 'code', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for finish quiz.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function finish_quiz_parameters() {
        return new external_function_parameters (
            [
                'try_id' => new external_value(PARAM_INT, 'Try id'),
                'finishattempt' => new external_value(PARAM_BOOL, 'Finish attempt', VALUE_DEFAULT, true),
                'timeup' => new external_value(PARAM_BOOL, 'Time up', VALUE_DEFAULT, false),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            // 'name' => new external_value(PARAM_ALPHANUMEXT, 'data name'),
                            // 'value' => new external_value(PARAM_RAW, 'data value'),
                        ]
                    ), 'Data', VALUE_DEFAULT, []
                ),
            ]
        );
    }

    /**
     * Finishes the quiz.
     *
     * @param int $tryid try id.
     * @param bool $finishattempt quiz which is to be finished whether true or false default false.
     * @param int $ansoption selected option id from the options.
     * @return object of the submitted quiz data.
     * @since Moodle 3.1
     */
    public static function finish_quiz(int $tryid, $finishattempt = true, $timeup = false, $data = []) {        global $DB, $USER;
        $submission = new stdClass();
        $params = [
            'try_id' => $tryid,
            'finishattempt' => $finishattempt,
            'timeup' => $timeup,
            'data' => $data,
        ];
        // Validating parameters.
        $params = self::validate_parameters(self::finish_quiz_parameters(), $params);
        $quizattmptid = $params['try_id'];

        // Quiz attempt record by the try id.
        $quizdetails = get_quizby_tryid_userid($quizattmptid, $USER->id);
        if (!empty($quizdetails)) {
            $quizid = $quizdetails->quiz;

            // Do not check access manager rules and evaluate fail if overdue.
            $attemptobj = quiz_attempt::create($quizattmptid);
            $failifoverdue = !($attemptobj->get_quizobj()->get_quiz()->overduehandling == 'graceperiod');

            list($attemptobj, $messages) = self::validationof_attempt($params, false, $failifoverdue);

            // Prevent functions like file_get_submitted_draft_itemid() or form library requiring a sesskey for WS requests.
            if (WS_SERVER || PHPUNIT_TEST) {
                $USER->ignoresesskey = true;
            }
            $slots = $attemptobj->get_slots('all');
            foreach ($slots as $sid => $slot) {
                $prefix = 'q'. $attemptobj->get_attempt()->uniqueid .':' . $slot .'_';
                $prarray = [];
                $prarray['questionusageid'] = $attemptobj->get_attempt()->uniqueid;
                $prarray['slot'] = $slot;
                $qid = $DB->get_field('question_attempts', 'questionid', $prarray);
                $ansoption = toget_choosen_answerid($attemptobj->get_attempt(), $qid, $attemptobj->get_attempt()->userid, 1);
                $answersoption = null;
                if (!is_null($ansoption)) {
                    $answersoption = (int) $ansoption;
                }
                $data = [
                    ['name' => 'slot', 'value' => $slot],
                    ['name' => $prefix . ':sequencecheck',
                            'value' => $attemptobj->get_question_attempt($slot)->get_sequence_check_count()],
                    ['name' => $prefix . 'answer', 'value' => $answersoption],
                ];
            }

            // Create the $_POST object required by the question engine.
            // if (isset($data)) {
            //     $_POST = [];
            //     foreach ($data as $element) {
            //         $_POST[$element['name']] = $element['value'];
            //         $_REQUEST[$element['name']] = $element['value'];
            //     }
            // }
            $timenow = time();
            $timeup = $params['timeup'];

            // if (isset($params['finishattempt'])) {
            $finishattempt = $params['finishattempt'];
            // }

            $result = [];
            // Update the timemodifiedoffline field.
            $attemptobj->set_offline_modified_time($timenow);
            $attemptobj->process_attempt($timenow, $finishattempt, $timeup, 0);

            // To get the user quiz attempts.
            $quizattrecords = quiz_get_user_attempts($quizdetails->quiz, $USER->id, 'finished');
            // To get the last quiz attempt..
            $attquizrecord = end($quizattrecords);

            $submission->status = 'success'; 
            $submission->try_id = $quizattmptid;

            $tmark = 0;
            $markscored = format_float(0);
            $timetaken = '00:00:00';
            $timetakeninsec = 0;
            $correctcount = 0;
            $wrongcount = 0;
            $unansweredcount = 0;
            $finished = false;
            $examfinished = '';
            $emptyobj = new stdClass();
            $emptyobj->question_no = 0;
            $emptyobj->is_answered = false;
            $emptyobj->is_correct = false;
            $questionpalette[] = $emptyobj;
            if (!empty($attquizrecord)) {
                $quizrecord = $DB->get_record('quiz', ['id' => $attquizrecord->quiz]);
                if (!empty($quizrecord->sumgrades)) {
                    $tmark = (int) $quizrecord->sumgrades;
                } else {
                    $tmark = (int) ($quizrecord->grade);
                }
                if ($attquizrecord->sumgrades > 0) {
                    $markscored = format_float($attquizrecord->sumgrades);
                }

                $timetakeninsec = ($attquizrecord->timefinish - $attquizrecord->timestart);

                // To get time taken by the user to finish the test.
                $timetaken = get_time_conversion($timetakeninsec);

                if ($timetakeninsec > 0) {
                    // $timetakeninsec = number_format($timetakeninsec, 1);
                    $timetakeninsec = $timetakeninsec;
                } else {
                    $timetakeninsec = 0;
                }

                // To get last quiz attempt contains questions related data.
                $questionattmptdetails = get_quizlast_attempt($attquizrecord->id, $attquizrecord->quiz, $USER->id);

                // To get the correct, wrong and unanswered counts of the attempt.
                list($correctcount, $wrongcount, $unansweredcount) = get_cwu_count_of_attempt($questionattmptdetails, 0, $attemptobj->get_attempt(), null);
                // To get the question palette.
                $questionpalette = get_question_palette($attemptobj->get_attempt(), $attquizrecord->quiz, $USER->id, 0, 0);
                if ($attquizrecord->state == 'finished') {
                    $finished = true;
                    $examfinished = 'Exam is finished';
                }
            } else {
                throw new yearbook_exception('notevenfinishedone', 'local_yearbook');
            }

            $submission->total_mark = $tmark;
            $submission->mark_scored = $markscored;
            $submission->time_taken = $timetaken;
            $submission->time_taken_sec = $timetakeninsec;

            $submission->no_of_questions = 0;

            // To get the questions count of the quiz.
            $cqueries = count_questions_inquiz($quizid);
            if (!empty($cqueries)) {
                $submission->no_of_questions = $cqueries;
            }
            
            // To get the tag names of the quiz.
            $tags = get_tag_names($quizid);
            $submission->tags = [];
            if (!empty($tags)) {
                $submission->tags = $tags;
            }

            $submission->correct_answer_count = $correctcount;
            $submission->wrong_answer_count = $wrongcount;
            $submission->unanswered_count = $unansweredcount;

            // To get course module id.
            $cmid = $attemptobj->get_cmid();

            // To get more quiz data.
            $morequiz = get_morequiz_data($cmid);

            $submission->more_quiz = $morequiz;
            $finalrank = 0;
            $ranks = get_rank_of_the_user($USER->id);
            $r = 0;
            $urank = [];
            foreach ($ranks as $k => $rank) {
                $urank[] = $rank->rank;
                $r ++;
                if ($r == 1) {
                    break;
                }
            }
            if (!empty($urank)) {
                $finalrank = implode(',', $urank);
            }
            $submission->total_rank = $finalrank;
            $submission->rank = $finalrank;
            $submission->wrong_answer_mark = '';
            $submission->wrong_answer_mark_app = 0;

            $submission->question_palette = $questionpalette;

            $submission->is_exam_finished = $finished;
            $submission->message = $examfinished;
            $submission->code = 1;
            return $submission;
        } else {
            throw new yearbook_exception('providecorrectdetails', 'local_yearbook');
        }
    }

    /**
     * Describes the finish quiz return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function finish_quiz_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'try_id' => new external_value(PARAM_INT, 'try_id'),
            'total_mark' => new external_value(PARAM_INT, 'total_mark'),
            'mark_scored' => new external_value(PARAM_FLOAT, 'mark_scored'),
            'time_taken' => new external_value(PARAM_RAW, 'time_taken'),
            'time_taken_sec' => new external_value(PARAM_INT, 'time_taken_sec'),
            'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'tags'),
            ),
            'correct_answer_count' => new external_value(PARAM_INT, 'correct_answer_count'),
            'wrong_answer_count' => new external_value(PARAM_INT, 'wrong_answer_count'),
            'unanswered_count' => new external_value(PARAM_INT, 'unanswered_count'),
            'more_quiz' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'no_of_questions' => new external_value(PARAM_RAW, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                ]),
            ),
            'total_rank' => new external_value(PARAM_INT, 'total_rank'),
            'rank' => new external_value(PARAM_INT, 'rank'),
            'wrong_answer_mark' => new external_value(PARAM_RAW, 'wrong_answer_mark'),
            'wrong_answer_mark_app' => new external_value(PARAM_RAW, 'wrong_answer_mark_app'),
            'question_palette' => new external_multiple_structure(
                new external_single_structure([
                    'question_no' => new external_value(PARAM_INT, 'question_no'),
                    'is_answered' => new external_value(PARAM_BOOL, 'is_answered'),
                    'is_correct' => new external_value(PARAM_BOOL, 'is_correct'),
                ]),
            ),
            'is_exam_finished' => new external_value(PARAM_BOOL, 'is_exam_finished'),
            'message' => new external_value(PARAM_RAW, 'message'),
            'code' => new external_value(PARAM_INT, 'code'),
        ]);
    }

 /**
     * Describes the parameters for package list.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function package_list_parameters() {
        return new external_function_parameters (
            [
                 'page' => new external_value(PARAM_INT, 'page id'),
            ]
        );
    }

    /**
     * get list of the packages test.
     *
     * @param int $page try id.
     * @return object of the quiz data.
     * @since Moodle 3.1
     */
    public static function package_list (int $page ) {
         global $DB,$USER;
        $params = [
            'page' => $page,
        ];
        $currenttime = time();
        $total_tests = [];
        $quizids = [];
        $totalfeaturedtests = [];
        // Set limits. 
        $viewrecordstartlimit = ($page*10) - 10;
        $viewrecordlimit = ($page*10);
        
        // Get Mocktests.
        $mocktests = get_all_mocktests($viewrecordstartlimit,$viewrecordlimit);
        $featuredmocktests = get_all_featured_mocktests();
        $featuredmocktestscount = count($featuredmocktests);
        
        // Get Total yearbook quiz count.
        $mocktestcount = get_all_mocktests_count();
        
        // Count total number of response pages. 
        $total_pages=(int)($mocktestcount/10);
        if ( ( ( $mocktestcount % 10) > 0 ) || ( $mocktestcount < 10 ) ){
            $total_pages = $total_pages + 1;
        }
        $total_attempts = 0;
        $above_80 = 0;
        $above_60 = 0;
        $below_60 = 0;
        // Quizzes for yearbooks.
        foreach( $mocktests as $mocktest ) {
            $activequizsql = "SELECT q.*
                                FROM {quiz} q
                               WHERE q.course = :course
                               AND (
                                (q.timeopen = 0 AND q.timeclose = 0)
                                OR ($currenttime BETWEEN q.timeopen AND q.timeclose)
                                OR ((q.timeopen != 0 AND q.timeopen < $currenttime) AND q.timeclose = 0)
                                OR (
                                    (q.timeopen = 0 AND (q.timeclose != 0 AND q.timeclose > $currenttime)
                                        )
                                    )
                                )";
            $quiz = $DB->get_record_sql($activequizsql, ['course' => $mocktest->id]);
            if (!empty($quiz)) {
                $quizids[] = $quiz->id;
                $questioncount = 0;
                $gradereceived = 0;
                
                // Number of questions in quiz.
                $questioncount = numberof_questions_in_quiz($quiz->id);
                // Get Users attempt and grades.
                if(!is_siteadmin()){
                    $attempts = quiz_get_user_attempts($quiz->id, $USER->id);
                 //   $grades = quiz_get_user_grades($quiz, $USER->id);
                    foreach($attempts as $grade){
                        if($grade){
                            $gradereceived = quiz_rescale_grade($grade->sumgrades, $quiz, false);
                            $grade_percentage = ($gradereceived/$quiz->grade)*100;
                            if($grade_percentage >= 80){
                                $above_80++; 
                            } else if($grade_percentage >= 60 && $grade_percentage < 80){
                                $above_60++;
                            }else{
                                $below_60++;
                            }
                            $totalreceivedgrade = $totalreceivedgrade + $gradereceived;
                            $totalquizgrade = $totalquizgrade + $quiz->grade;
                        }
                    }
                }
                $courseid =$DB->get_field('quiz','course', ['id' => $quiz->id]);
                $course =$DB->get_record('course', ['id' => $courseid]);
                // Course image.
                $courseimage = course_summary_exporter::get_course_image($course);
                // if (!$courseimage) {
                //     $courseimage = $OUTPUT->get_generated_image_for_id($course->id);
                // }
                if ($quiz->sumgrades > 0) {
                    $marks = round($quiz->sumgrades);
                } else {
                    $marks = round($quiz->grade);
                }
                $tags=get_tag_names($quiz->id);
                $testobj = new stdClass();
                $testobj->tags = !empty($tags) ? $tags : [];
                $testobj->name = $mocktest->fullname;
                $testobj->description = $quiz->intro;
                $testobj->exam_name = $mocktest->fullname;
                $testobj->details = $quiz->intro;
                $testobj->no_of_questions = $questioncount;
                $testobj->mark = $marks;
                $testobj->id = $quiz->id;
                $testobj->image = $courseimage;
                $testobj->exam_count = 1;
                $testobj->time_limit = gmdate('H:i:s', $quiz->timelimit);
                $testobj->created_on = gmdate('Y-m-d T H:i:s', $quiz->timecreated);
                $testobj->time_limit_sec = $quiz->timelimit;
                $total_tests[]=$testobj;
            }
        }
        if (!empty($quizids)) {
            $allattempts = quiz_get_user_attempts($quizids, $USER->id, 'all');
            $total_attempts = count($allattempts);
        }
        foreach ($featuredmocktests as $featuredmocktest) {
            $quiz = $DB->get_record('quiz', ['course' => $featuredmocktest->id]);
            $courseid = $DB->get_field('quiz', 'course', ['id' => $quiz->id]);
            $course = $DB->get_record('course', ['id' => $courseid]);
            // Course image.
            $courseimage = course_summary_exporter::get_course_image($course);
            $questioncount = 0;
            if ($quiz->sumgrades > 0) {
                $mark = round($quiz->sumgrades);
            } else {
                $mark = round($quiz->grade);
            }
                
            // Number of questions in quiz.
            $questioncount = numberof_questions_in_quiz($quiz->id);
            $tags = get_tag_names($quiz->id);
            $featuredtestobj = new stdClass();
            $featuredtestobj->tags = !empty($tags) ? $tags : [];
            // $featuredtestobj->name = $featuredmocktest->fullname;
            // $featuredtestobj->description = $quiz->intro;
            $featuredtestobj->exam_name = $featuredmocktest->fullname;
            $featuredtestobj->details = $quiz->intro;
            $featuredtestobj->no_of_questions = $questioncount;
            $featuredtestobj->mark = $mark;
            $featuredtestobj->created_on = gmdate('Y-m-d T H:i:s', $quiz->timecreated);
            $featuredtestobj->id = $quiz->id;
            $featuredtestobj->image = $courseimage;
            // $featuredtestobj->exam_count = 1;
            $featuredtestobj->time_limit = gmdate('H:i:s', $quiz->timelimit);
            $featuredtestobj->time_limit_sec = $quiz->timelimit;
            $totalfeaturedtests[] = $featuredtestobj;
        }
        $atmtavg = $totalquizgrade ? (($totalreceivedgrade/$totalquizgrade)*100) : 0;
        $attemptavg = format_float($atmtavg);

        return [
            'status' => 'success',
            'popular_exam_list' => $total_tests,
            'featured_exam_list' => $totalfeaturedtests,
            'page' => $page,
            'code' => 1,
            'total_pages' => $total_pages,
            'attempt_avg' => $attemptavg,
            'attempt_count' => $total_attempts,
            'above_80' => $above_80,
            'above_60' => $above_60,
            'below_60' => $below_60,
        ];

    }

    /**
     * Describes the package list return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function package_list_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'popular_exam_list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'id'),
                    'name' => new external_value(PARAM_RAW, 'name'),
                    'description' => new external_value(PARAM_RAW, 'description'),
                    'exam_count' => new external_value(PARAM_INT, 'exam_count'),
                    'image' => new external_value(PARAM_URL, 'image'),
                ]),
            ),
            'featured_exam_list' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'details' => new external_value(PARAM_RAW, 'details'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                    'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec'),
                ]),
            ),
            'page' => new external_value(PARAM_INT, 'page'),
            'total_pages' => new external_value(PARAM_INT, 'total_pages'),
            'code' => new external_value(PARAM_INT, 'code'),
            'attempt_avg' => new external_value(PARAM_FLOAT, 'attempt_avg'),
            'attempt_count' => new external_value(PARAM_INT, 'attempt_count'),
            'above_80' => new external_value(PARAM_INT, 'above_80'),
            'above_60' => new external_value(PARAM_INT, 'above_60'),
            'below_60' => new external_value(PARAM_INT, 'below_60'),
        ]);
    }

    /**
     * Describes the parameters for package test list.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function package_test_list_parameters() {
        return new external_function_parameters (
            [
                 'package_id' => new external_value(PARAM_INT, 'package id'),
                 'page' => new external_value(PARAM_INT, 'page id'),
            ]
        );
    }

    /**
     * get package_test_list of the quiz.
     *
     * @param int $page try id.
     * @return object of the quiz data.
     * @since Moodle 3.1
     */
    public static function package_test_list (int $package_id, int $page ) {
         global $DB;
        $params = [
            'package_id' => $package_id,
        ];
        // $total_tests = [];
        $currenttime = time();
        $totalfeaturedtests = [];

        // Set limits. 
        $viewrecordstartlimit= ($page*10) - 10;
        $viewrecordlimit = ($page*10);

        // Get packageid details.
        $package = $DB->get_record('course_categories',['id' => $package_id]);
        $packageobj = new stdClass();
        $packageobj->id = $package->id;
        $packageobj->name = $package->name;
        // $totalpackages[]=$packageobj; // 

        // Get Mocktests.
        $featuredmocktests = get_tests_by_packageid($viewrecordstartlimit,$viewrecordlimit,$package_id);
        // $featuredmocktests = get_featuredtests_by_packageid($viewrecordstartlimit, $viewrecordlimit, $package_id);
        $featuredmocktestscount = count($featuredmocktests);

        // Count total number of response pages. 
        $total_pages = (int) ($featuredmocktestscount / 10);
        if ((($featuredmocktestscount % 10) > 0) || ($featuredmocktestscount < 10)) {
            $total_pages = $total_pages + 1;
        }

        // Get Total yearbook quiz count.
        // $testscount = get_tests_count_by_packageid();
        
        // // Count total number of response pages. 
        // $total_pages=(int)($testscount/10);
        // if ( ( ( $testscount % 10) > 0 ) || ( $testscount < 10 ) ){
        //     $total_pages = $total_pages + 1;
        // }

        
        // Quizzes for yearbooks.
        // foreach( $mocktests as $mocktest ) {
        //     $quiz = $DB->get_record('quiz',['course' => $mocktest->id]);
        //     $questioncount = 0;

        //     // Number of questions in quiz.
        //     $questioncount = numberof_questions_in_quiz($quiz->id);

        //     $courseid =$DB->get_field('quiz','course', ['id' => $quiz->id]);
        //     $course =$DB->get_record('course', ['id' => $courseid]);
            
        //     // Course image.
        //     $courseimage = course_summary_exporter::get_course_image($course);
        //     // if (!$courseimage) {
        //     //     $courseimage = $OUTPUT->get_generated_image_for_id($course->id);
        //     // }
        //     $tags=get_tag_names($quiz->id);
        //     $testobj = new stdClass();
        //     $testobj->tags = !empty($tags) ? $tags : [];;
        //     $testobj->exam_name = $mocktest->fullname;
        //     $testobj->details = $quiz->intro;
        //     $testobj->no_of_questions = $questioncount;
        //     $testobj->mark = $quiz->grade;
        //     $testobj->id = $quiz ->id;
        //     $testobj->image = $courseimage;
        //     $testobj->exam_count = 1;
        //     $testobj->time_limit = gmdate('H:i:s', $quiz ->timelimit);
        //     $testobj->created_on = gmdate('Y-m-d T H:i:s', $quiz -> timecreated);
        //     $testobj->time_limit_sec = $quiz ->timelimit;
        //     $total_tests[]=$testobj;
        // }

        foreach ($featuredmocktests as $featuredmocktest) {
            $activequizsql = "SELECT q.*
                                FROM {quiz} q
                               WHERE q.course = :course
                               AND (
                                (q.timeopen = 0 AND q.timeclose = 0)
                                OR ($currenttime BETWEEN q.timeopen AND q.timeclose)
                                OR ((q.timeopen != 0 AND q.timeopen < $currenttime) AND q.timeclose = 0)
                                OR (
                                    (q.timeopen = 0 AND (q.timeclose != 0 AND q.timeclose > $currenttime)
                                        )
                                    )
                                )";
            $quiz = $DB->get_record_sql($activequizsql, ['course' => $featuredmocktest->id]);
            if (!empty($quiz)) {

                $courseid = $DB->get_field('quiz', 'course', ['id' => $quiz->id]);
                $course = $DB->get_record('course', ['id' => $courseid]);
                // Course image.
                $courseimage = course_summary_exporter::get_course_image($course);
                $questioncount = 0;
                if ($quiz->sumgrades > 0) {
                    $marks = round($quiz->sumgrades);
                } else {
                    $marks = round($quiz->grade);
                }
                // Number of questions in quiz.
                $questioncount = numberof_questions_in_quiz($quiz->id);
                $tags = get_tag_names($quiz->id);
                $featuredtestobj = new stdClass();
                $featuredtestobj->tags = !empty($tags) ? $tags : [];
                // $featuredtestobj->name = $featuredmocktest->fullname;
                // $featuredtestobj->description = $quiz->intro;
                $featuredtestobj->exam_name = $featuredmocktest->fullname;
                $featuredtestobj->details = $quiz->intro;
                $featuredtestobj->no_of_questions = $questioncount;
                $featuredtestobj->mark = $marks;
                $featuredtestobj->created_on = gmdate('Y-m-d T H:i:s', $quiz->timecreated);
                $featuredtestobj->id = $quiz->id;
                $featuredtestobj->image = $courseimage;
                // $featuredtestobj->exam_count = 1;
                $featuredtestobj->time_limit = gmdate('H:i:s', $quiz->timelimit);
                $featuredtestobj->time_limit_sec = (int) $quiz->timelimit;
                $totalfeaturedtests[] = $featuredtestobj;
            }
        }

        return [
            'status' => 'success',
            'package_list' => $packageobj,
            'exam_list' => $totalfeaturedtests,
            'page' => $page,
            'code' => 1,
            'total_pages' => $total_pages,
        ];

    }

    /**
     * Describes the package_test_list return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function package_test_list_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'code' => new external_value(PARAM_INT, 'code'),
            // 'package_list' => new external_multiple_structure(
            //     new external_single_structure([
            //         'id' => new external_value(PARAM_INT, 'id'),
            //         'name' => new external_value(PARAM_RAW, 'exam_name'),
            //     ]),
            // ),
            'package_list' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'id'),
                'name' => new external_value(PARAM_RAW, 'exam_name'),
            ]),
            'exam_list' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'details' => new external_value(PARAM_RAW, 'details'),
                    'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                    'time_limit_sec' => new external_value(PARAM_INT, 'time_limit_sec'),
                ]),
            ),
            'page' => new external_value(PARAM_INT, 'page'),
            'total_pages' => new external_value(PARAM_INT, 'total_pages'),
        ]);
    }

    /**
     * Validate an attempt finished for review. The attempt would be reviewed by a user or a teacher.
     *
     * @param  array $params Array of parameters including the attemptid
     * @return  array containing the attempt object and display options
     * @since  Moodle 3.1
     */
    protected static function validationof_review_answers($params) {

        $attemptobj = quiz_attempt::create($params['try_id']);
        $attemptobj->check_review_capability();

        $displayoptions = $attemptobj->get_display_options(true);
        if ($attemptobj->is_own_attempt()) {
            if (!$attemptobj->is_finished()) {
                throw new yearbook_exception('attemptclosed', 'quiz', $attemptobj->view_url());
            } else if (!$displayoptions->attempt) {
                throw new yearbook_exception('noreview', 'quiz', $attemptobj->view_url(), null,
                    $attemptobj->cannot_review_message());
            }
        } else if (!$attemptobj->is_review_allowed()) {
            throw new yearbook_exception('noreviewattempt', 'quiz', $attemptobj->view_url());
        }
        return [$attemptobj, $displayoptions];
    }

    /**
     * Return questions information for a given attempt.
     *
     * @param  quiz_attempt  $attemptobj  the quiz attempt object
     * @param  bool  $review  whether if we are in review mode or not
     * @param  mixed  $page  string 'all' or integer page number
     * @return array array of questions including data
     */
    private static function get_attmpt_questionsdata(quiz_attempt $attemptobj, $review, $page = 'all') {
        global $PAGE;

        $questions = [];
        $displayoptions = $attemptobj->get_display_options($review);

        $renderer = $PAGE->get_renderer('mod_quiz');
        $contextid = $attemptobj->get_quizobj()->get_context()->id;

        foreach ($attemptobj->get_slots($page) as $slot) {
            $qtype = $attemptobj->get_question_type_name($slot);
            $qattempt = $attemptobj->get_question_attempt($slot);
            $questiondef = $qattempt->get_question(true);

            // Get response files (for questions like essay that allows attachments).
            $responsefileareas = [];
            foreach (question_bank::get_qtype($qtype)->response_file_areas() as $area) {
                if ($files = $attemptobj->get_question_attempt($slot)->get_last_qt_files($area, $contextid)) {
                    $responsefileareas[$area]['area'] = $area;
                    $responsefileareas[$area]['files'] = [];

                    foreach ($files as $file) {
                        $responsefileareas[$area]['files'][] = [
                            'filename' => $file->get_filename(),
                            'fileurl' => $qattempt->get_response_file_url($file),
                            'filesize' => $file->get_filesize(),
                            'filepath' => $file->get_filepath(),
                            'mimetype' => $file->get_mimetype(),
                            'timemodified' => $file->get_timemodified(),
                        ];
                    }
                }
            }

            // Check display settings for question.
            $settings = $questiondef->get_question_definition_for_external_rendering($qattempt, $displayoptions);

            $question = [
                'slot' => $slot,
                'type' => $qtype,
                'page' => $attemptobj->get_question_page($slot),
                'questionnumber' => $attemptobj->get_question_number($slot),
                'flagged' => $attemptobj->is_question_flagged($slot),
                'html' => $attemptobj->render_question($slot, $review, $renderer) . $PAGE->requires->get_end_code(),
                'responsefileareas' => $responsefileareas,
                'sequencecheck' => $qattempt->get_sequence_check_count(),
                'lastactiontime' => $qattempt->get_last_step()->get_timecreated(),
                'hasautosavedstep' => $qattempt->has_autosaved_step(),
                'settings' => !empty($settings) ? json_encode($settings) : null,
            ];

            if ($question['questionnumber'] === (string) (int) $question['questionnumber']) {
                $question['number'] = $question['questionnumber'];
            }

            if ($attemptobj->is_real_question($slot)) {
                $showcorrectness = $displayoptions->correctness && $qattempt->has_marks();
                if ($showcorrectness) {
                    $question['state'] = (string) $attemptobj->get_question_state($slot);
                }
                $question['status'] = $attemptobj->get_question_status($slot, $displayoptions->correctness);
                $question['blockedbyprevious'] = $attemptobj->is_blocked_by_previous_question($slot);
            }
            if ($displayoptions->marks >= question_display_options::MAX_ONLY) {
                $question['maxmark'] = $qattempt->get_max_mark();
            }
            if ($displayoptions->marks >= question_display_options::MARK_AND_MAX) {
                $question['mark'] = $attemptobj->get_question_mark($slot);
            }
            if ($attemptobj->check_page_access($attemptobj->get_question_page($slot), false)) {
                $questions[] = $question;
            }
        }
        return $questions;
    }

    /**
     * Describes the parameters for review answers.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function review_answers_parameters() {
        return new external_function_parameters (
            [
                'try_id' => new external_value(PARAM_INT, 'Try id'),
                'page' => new external_value(PARAM_INT, 'page number, empty for all the questions in all the pages',
                                                VALUE_DEFAULT, -1),
            ]
        );
    }

    /**
     * Finishes the quiz.
     *
     * @param int $tryid try id.
     * @param bool $finishattempt quiz which is to be finished whether true or false default false.
     * @param int $ansoption selected option id from the options.
     * @return object of the submitted quiz data.
     * @since Moodle 3.1
     */
    public static function review_answers(int $tryid, int $page) {
        global $DB, $USER;
        $attemptreview = new stdClass();
        $params = [
            'try_id' => $tryid,
            'page' => $page,
        ];
        // Validating parameters.
        $params = self::validate_parameters(self::review_answers_parameters(), $params);
        $quizattmptid = $params['try_id'];

        // Quiz attempt record by the try id.
        $quizdetails = get_quizby_tryid_userid($quizattmptid, $USER->id);
        if (!empty($quizdetails)) {
            $quizid = $quizdetails->quiz;

            list($attemptobj, $displayoptions) = self::validationof_review_answers($params);

            if ($params['page'] !== -1) {
                $page = $attemptobj->force_page_number_into_range($params['page']);
            } else {
                $page = 'all';
            }

            // Prepare the output.
            $result = [];
            $result['attempt'] = $attemptobj->get_attempt();
            $objattempt = $attemptobj->get_attempt();

            $a = self::get_attmpt_questionsdata($attemptobj, true, $page, true);

            $result['additionaldata'] = [];
            // Summary data (from behaviours).
            $summarydata = $attemptobj->get_additional_summary_data($displayoptions);
            foreach ($summarydata as $key => $data) {
                // This text does not need formatting (no need for external_format_[string|text]).
                $result['additionaldata'][] = [
                    'id' => $key,
                    'title' => $data['title'], $attemptobj->get_quizobj()->get_context()->id,
                    'content' => $data['content'],
                ];
            }

            // Feedback if there is any, and the user is allowed to see it now.
            $grade = quiz_rescale_grade($attemptobj->get_attempt()->sumgrades, $attemptobj->get_quiz(), false);

            $feedback = $attemptobj->get_overall_feedback($grade);
            if ($displayoptions->overallfeedback && $feedback) {
                $result['additionaldata'][] = [
                    'id' => 'feedback',
                    'title' => get_string('feedback', 'quiz'),
                    'content' => $feedback,
                ];
            }

            $result['grade'] = $grade;
            $result['warnings'] = $warnings;

            // This is the current course object.
            $courseobj = $attemptobj->get_course();

            // This is the current quiz object.
            $objquiz = $attemptobj->get_quizobj()->get_quiz();

            // To get enrolid of the user in the course.
            $enrolid = get_enrolid_of_user_incourse($courseobj->id, $USER->id);

            // To get the no.of questions of the quiz.
            $noofquestions = count_questions_inquiz($objquiz->id);

            $examstatus = new stdClass();
            $examstatus->exam_try_id = $quizattmptid;
            $examstatus->enrol_id = $enrolid;
            $examstatus->no_of_questions = $noofquestions;
            if ($objquiz->sumgrades > 0) {
                $exammark = round($objquiz->sumgrades);
            } else {
                $exammark = round($objquiz->grade);
            }
            if ($objattempt->sumgrades > 0) {
                $mark = format_float($objattempt->sumgrades);
            } else {
                $mark = format_float(0);
            }
            $examstatus->exam_mark = $exammark;
            $examstatus->mark = $mark;
            $examstatus->exam_name = $objquiz->name;

            $attemptreview->status = 'success'; 
            $attemptreview->exam_status = $examstatus;

            // To get the question details of the quiz.
            $questiondetails = get_question_detailsof_attempt($objattempt);
            $attemptreview->question_details = $questiondetails;
            $attemptreview->message = '';

            // To get last quiz attempt contains questions related data.
            $questionattmptdetails = get_quizlast_attempt($objattempt->id, $objattempt->quiz, $USER->id);

            // To get the correct, wrong and unanswered counts of the attempt.
            list($correctcount, $wrongcount, $unansweredcount) = get_cwu_count_of_attempt($questionattmptdetails, 0, $attemptobj->get_attempt(), null);

            $attemptreview->correct_answer_count = $correctcount;
            $attemptreview->wrong_answer_count = $wrongcount;
            $attemptreview->unanswered_count = $unansweredcount;
            $attemptreview->code = 1;

            return $attemptreview;
        } else {
            throw new yearbook_exception('attemptnotfound', 'local_yearbook');
        }
    }

    /**
     * Describes the finish quiz return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function review_answers_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'exam_status' => new external_single_structure([
                'exam_try_id' => new external_value(PARAM_INT, 'exam_try_id'),
                'enrol_id' => new external_value(PARAM_INT, 'enrol_id'),
                'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
                'exam_mark' => new external_value(PARAM_INT, 'exam_mark'),
                'mark' => new external_value(PARAM_FLOAT, 'mark'),
                'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
            ]),
            'question_details' => new external_multiple_structure(
                new external_single_structure([
                    'position' => new external_value(PARAM_INT, 'position'),
                    'question_id' => new external_value(PARAM_INT, 'question_id'),
                    'question' => new external_value(PARAM_RAW, 'question'),
                    'marks' => new external_value(PARAM_INT, 'marks'),
                    'hint' => new external_value(PARAM_RAW, 'hint'),
                    'solution' => new external_value(PARAM_RAW, 'solution'),
                    'question_type' => new external_value(PARAM_INT, 'question_type'),
                    'student_answer' => new external_value(PARAM_BOOL, 'student_answer'),
                    'student_answer_is_correct' => new external_value(PARAM_BOOL, 'student_answer_is_correct'),
                    'answer_options' => new external_multiple_structure(
                        new external_single_structure([
                            'student_answer' => new external_value(PARAM_BOOL, 'student_answer'),
                            'student_answer_is_correct' => new external_value(PARAM_BOOL, 'student_answer_is_correct'),
                            'id' => new external_value(PARAM_INT, 'id'),
                            'answer_option' => new external_value(PARAM_RAW, 'answer_option'),
                            'is_correct' => new external_value(PARAM_BOOL, 'is_correct'),
                            'description' => new external_value(PARAM_RAW, 'description'),
                        ]),
                    ),
                ]),
            ),
            'message' => new external_value(PARAM_RAW, 'message'),
            'correct_answer_count' => new external_value(PARAM_INT, 'correct_answer_count'),
            'wrong_answer_count' => new external_value(PARAM_INT, 'wrong_answer_count'),
            'unanswered_count' => new external_value(PARAM_INT, 'unanswered_count'),
            'code' => new external_value(PARAM_INT, 'code'),
        ]);
    }
/**
     * Describes the parameters my_performance service.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function my_performance_parameters() {
        return new external_function_parameters (
            [
            ]
        );
    }

    /**
     * get user performance based on test.
     *
     * @return object of the performance data.
     * @since Moodle 3.1
     */
    public static function my_performance () {
         global $DB,$USER;
      
        $total_tests = [];
        $sumofgrades = [];

        // Get Mocktests.
        $mocktests = get_all_mocktests_by_userid();

        // Get Total yearbook quiz count.
        $testscount = count(get_all_mocktests_by_userid());

        // Quizzes for yearbooks.
        foreach( $mocktests as $mocktest ) {
            $quiz[] = $DB->get_field('quiz','id',['course' => $mocktest->id]);
        }
        if(!is_siteadmin() && !empty($quiz)){
            $attempts = quiz_get_user_attempts($quiz, $USER->id);
            
            foreach($attempts as $grade){
                $quizinstance = $DB->get_record('quiz',['id' => $grade->quiz]);
                if($grade){
                    $rank = 0;
                    $attemptrec = $DB->get_records_sql('SELECT qa.* FROM {quiz_attempts} as qa WHERE qa.quiz = :quiz and qa.state = :state ORDER BY qa.sumgrades DESC ', ['quiz' => $grade->quiz, 'state' => 'finished']);
                    foreach($attemptrec as $attempt){
                        $rank++;
                        if($attempt->userid == $USER->id && $grade->attempt == $attempt->attempt)
                        break;
                    }
                    $gradereceived = quiz_rescale_grade($grade->sumgrades, $quizinstance, false);
                    $testobj = new stdClass();
                    $testobj->last_try_date = gmdate('Y-m-d T H:i:s', $grade->gradednotificationsenttime);
                    $testobj->exam_name = $quizinstance->name;
                    $testobj->mark = $gradereceived;
                    $testobj->rank = $rank;
                    $total_tests[]=$testobj;
                }
            }
        }
        $attemptcount = 0;
        $totalscore = 0;
        if (!empty($quiz)) {
            $allattempts = quiz_get_user_attempts($quiz, $USER->id, 'all');
            foreach ($allattempts as $a => $atmt) {
                $sumofgrades[] = $atmt->sumgrades;
            }
            $attemptcount = count($allattempts);
            if (!empty($sumofgrades)) {
                $totalscore = (int) array_sum($sumofgrades);
            }
        }
        return [
            'status' => 'success',
            'exam_data' => $total_tests,
            'code' => 1,
            'total_mark' => $totalscore,
            'count' => $attemptcount,
        ];

    }

    /**
     * Describes the performance data return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function my_performance_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'code' => new external_value(PARAM_INT, 'code'),
            'exam_data' => new external_multiple_structure(
                new external_single_structure([
                    'last_try_date' => new external_value(PARAM_RAW, 'id'),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'rank' => new external_value(PARAM_INT, 'rank'),
                ]),
            ),
            'total_mark' => new external_value(PARAM_INT, 'total_mark'),
            'count' => new external_value(PARAM_INT, 'count'),
        ]);
    }

    /**
     * get user test performance.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function test_performance_parameters() {
        return new external_function_parameters (
            [
            ]
        );
    }

    /**
     * get user test performance.
     *
     * @return object of the test performance data.
     * @since Moodle 3.1
     */
    public static function test_performance() {
        global $DB, $USER;
        $testperformance = new stdClass();
        $page = 1;

        // Get Mocktests.
        $mocktests = get_all_testslist_by_userid();

        // Get Total quiz count.
        $mocktestcount = count($mocktests);
        if ($mocktestcount > 0) {
            // Count total number of response pages. 
            $totalpages = (int)($mocktestcount / 10);
            if ((($mocktestcount % 10) > 0 ) || ($mocktestcount < 10)) {
                $totalpages = $totalpages + 1;
            }
            $totalattempts = 0;
            $above80 = 0;
            $above60 = 0;
            $below60 = 0;

            // Quizzes for courses.
            foreach ($mocktests as $mocktest) {
                $finalgrades = [];
                $quiz = $DB->get_record('quiz', ['course' => $mocktest->id]);
                $attempts = quiz_get_user_attempts($quiz->id, $USER->id);
                $params = [];
                $params['cid'] = $mocktest->id;
                $params['quizid'] = $quiz->id;
                $params['uid'] = $USER->id;
                $params['type'] = 'mod';
                $sql = "SELECT gg.*
                          FROM {grade_items} gi
                          JOIN {grade_grades} gg ON gg.itemid = gi.id
                         WHERE gi.courseid = :cid
                           AND gi.iteminstance = :quizid
                           AND gg.userid = :uid
                           AND gi.itemtype = :type";
                $grades = $DB->get_records_sql($sql, $params);
                $gradereceived = 0;
                $totalquizgrade = 0;
                foreach($grades as $grade) {
                    $finalgrades[] = $grade->finalgrade;

                    $gradepercentage = ($grade->finalgrade / $grade->rawgrademax) * 100;
                    if ($gradepercentage >= 80) {
                        $above80 ++; 
                    } else if ($gradepercentage >= 60 && $gradepercentage < 80) {
                        $above60 ++;
                    } else {
                        $below60 ++;
                    }
                    $totalreceivedgrade = $totalreceivedgrade + array_sum($finalgrades);
                    $totalquizgrade = $totalquizgrade + $grade->rawgrademax;
                    $totalattempts = $totalattempts + count($attempts);
                }
            }
            $below60 = $totalattempts - ($above80 + $above60);
            $testperformance->above_80 = $above80;
            $testperformance->above_60 = $above60;
            $testperformance->below_60 = $below60;
            $testperformance->status = 'success';
            $atmptavg = $totalquizgrade ? (($totalreceivedgrade / $totalquizgrade) * 100) : 0;
            $attemptavg = format_float($atmptavg);
            $testperformance->attempt_avg = $attemptavg;
            $testperformance->attempt_count = $totalattempts;
            $graphdata = get_testperformance_user_graph_data($USER->id);

            $testperformance->graph_data = $graphdata;

            $attemptinfo = get_testperformance_attempt_info($USER->id);
            $testperformance->attempt_info = $attemptinfo;
            $testperformance->total_pages = $page;
            $testperformance->code = 1;
            return $testperformance;
        } else {
            throw new yearbook_exception('notestsfound', 'local_yearbook');
        }
    }

    /**
     * Describes the test performance data return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function test_performance_returns() {
        return new external_single_structure([
            'above_80' => new external_value(PARAM_INT, 'above_80'),
            'above_60' => new external_value(PARAM_INT, 'above_60'),
            'below_60' => new external_value(PARAM_INT, 'below_60'),
            'status' => new external_value(PARAM_TEXT, 'status'),
            'attempt_avg' => new external_value(PARAM_FLOAT, 'attempt_avg'),
            'attempt_count' => new external_value(PARAM_INT, 'attempt_count'),
            'graph_data' => new external_multiple_structure(
                new external_single_structure([
                    'avg_mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'month' => new external_value(PARAM_RAW, 'month'),
                ]),
            ),
            'attempt_info' => new external_multiple_structure(
                new external_single_structure([
                    'last_try_date' => new external_value(PARAM_RAW, 'last_try_date'),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'mark' => new external_value(PARAM_FLOAT, 'mark'),
                    'rank' => new external_value(PARAM_INT, 'rank'),
                ]),
            ),
            'total_pages' => new external_value(PARAM_INT, 'total_pages'),
            'code' => new external_value(PARAM_INT, 'code'),
        ]);
    }

    /**
     * get user skip questions.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question_parameters() {
        return new external_function_parameters (
            [
                'question_id' => new external_value(PARAM_INT, 'Question id'),
                'try_id' => new external_value(PARAM_INT, 'Try id'),
            ]
        );
    }

    /**
     * get user skip questions.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question($questionid, $tryid) {
        global $DB, $USER;
        $submission = new stdClass();
        $params = [
            'question_id' => $questionid,
            'try_id' => $tryid,
        ];
        $page = 1;
        // Validating parameters.
        $params = self::validate_parameters(self::skip_question_parameters(), $params);

        $quizattmptid = $params['try_id'];
        // Quiz attempt record by the try id.
        $quizdetails = get_quizby_tryid_userid($quizattmptid, $USER->id);
        if (!empty($quizdetails)) {
            $quizid = $quizdetails->quiz;
            list($attemptobj, $messages) = self::validationof_attempt($params, false, $failifoverdue);
            // Quiz attempt record by the try id.
            $quizattrecords = quiz_get_user_attempts($quizdetails->quiz, $USER->id, 'finished');
            // To get the last quiz attempt..
            $attquizrecord = end($quizattrecords);

            $submission->status = 'success'; 
            $submission->try_id = $quizattmptid;

            $tmark = 0;
            $markscored = format_float(0);
            $timetaken = '00:00:00';
            $timetakeninsec = 0;
            $correctcount = 0;
            $wrongcount = 0;
            $unansweredcount = 0;
            $finished = false;
            $examfinished = '';
            $emptyobj = new stdClass();
            $emptyobj->question_no = 0;
            $emptyobj->is_answered = false;
            $emptyobj->is_correct = false;
            $questionpalette[] = $emptyobj;
            if (!empty($attquizrecord)) {
                $quizrecord = $DB->get_record('quiz', ['id' => $attquizrecord->quiz]);
                if ($quizrecord->sumgrades > 0) {
                    $tmark = round($quizrecord->sumgrades);
                } else {
                    $tmark = round($quizrecord->grade);
                }
                if ($attquizrecord->sumgrades > 0) {
                    $markscored = format_float($attquizrecord->sumgrades);
                }

                if ($attquizrecord->timefinish > 0) {
                    $timetakeninsec = ($attquizrecord->timefinish - $attquizrecord->timestart);
                }

                // To get time taken by the user to finish the test.
                $timetaken = get_time_conversion($timetakeninsec);

                if ($timetakeninsec > 0) {
                    // $timetakeninsec = number_format($timetakeninsec, 1);
                    $timetakeninsec = $timetakeninsec;
                } else {
                    $timetakeninsec = 0;
                }

                // To get last quiz attempt contains questions related data.
                $questionattmptdetails = get_quizlast_attempt($attquizrecord->id, $attquizrecord->quiz, $USER->id);

                // To get the correct, wrong and unanswered counts of the attempt.
                list($correctcount, $wrongcount, $unansweredcount) = get_cwu_count_of_attempt($questionattmptdetails, 0, $attemptobj->get_attempt(), null);
                // To get the question palette.
                $questionpalette = get_question_palette($attemptobj->get_attempt(), $attquizrecord->quiz, $USER->id, $questionid, 0);
                if ($attquizrecord->state == 'finished') {
                    $finished = true;
                    $examfinished = 'Exam is finished';
                }
            }

            $submission->total_mark = $tmark;
            $submission->mark_scored = $markscored;
            $submission->time_taken = $timetaken;
            $submission->time_taken_sec = $timetakeninsec;

            $submission->no_of_questions = 0;

            // To get the questions count of the quiz.
            $cqueries = count_questions_inquiz($quizid);
            if (!empty($cqueries)) {
                $submission->no_of_questions = $cqueries;
            }
            
            // To get the tag names of the quiz.
            $tags = get_tag_names($quizid);
            $submission->tags = [];
            if (!empty($tags)) {
                $submission->tags = $tags;
            }

            $sql = "UPDATE {question_attempts} set flagged = 1
                     WHERE questionid = :questionid
                       AND questionusageid = :quid";
            $updateflag = $DB->execute($sql, ['questionid' => $questionid, 'quid' => $attemptobj->get_attempt()->uniqueid]);

            $submission->correct_answer_count = $correctcount;
            $submission->wrong_answer_count = $wrongcount;
            $submission->unanswered_count = $unansweredcount;

            // To get course module id.
            $cmid = $attemptobj->get_cmid();

            // To get more quiz data.
            $morequiz = get_morequiz_data($cmid);

            $submission->more_quiz = $morequiz;
            $finalrank = 0;
            $ranks = get_rank_of_the_user($USER->id);
            $r = 0;
            $urank = [];
            foreach ($ranks as $k => $rank) {
                $urank[] = $rank->rank;
                $r ++;
                if ($r == 1) {
                    break;
                }
            }
            if (!empty($urank)) {
                $finalrank = implode(',', $urank);
            }
            $submission->total_rank = $finalrank;
            $submission->rank = $finalrank;
            $submission->wrong_answer_mark = '';
            $submission->wrong_answer_mark_app = 0;

            $submission->question_palette = $questionpalette;

            $submission->is_exam_finished = $finished;
            $submission->message = $examfinished;
            $submission->code = 1;
            return $submission;
        } else {
            throw new yearbook_exception('wrongdetailsprovided', 'local_yearbook');
        }
    }

    /**
     * get user skip questions returns.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'try_id' => new external_value(PARAM_INT, 'try_id'),
            'total_mark' => new external_value(PARAM_INT, 'total_mark'),
            'mark_scored' => new external_value(PARAM_FLOAT, 'mark_scored'),
            'time_taken' => new external_value(PARAM_RAW, 'time_taken'),
            'time_taken_sec' => new external_value(PARAM_RAW, 'time_taken_sec'),
            'no_of_questions' => new external_value(PARAM_INT, 'no_of_questions'),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'tags'),
            ),
            'correct_answer_count' => new external_value(PARAM_INT, 'correct_answer_count'),
            'wrong_answer_count' => new external_value(PARAM_INT, 'wrong_answer_count'),
            'unanswered_count' => new external_value(PARAM_INT, 'unanswered_count'),
            'more_quiz' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'no_of_questions' => new external_value(PARAM_RAW, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                ]),
            ),
            'total_rank' => new external_value(PARAM_INT, 'total_rank'),
            'rank' => new external_value(PARAM_INT, 'rank'),
            'wrong_answer_mark' => new external_value(PARAM_RAW, 'wrong_answer_mark'),
            'wrong_answer_mark_app' => new external_value(PARAM_RAW, 'wrong_answer_mark_app'),
            'question_palette' => new external_multiple_structure(
                new external_single_structure([
                    'question_no' => new external_value(PARAM_INT, 'question_no'),
                    'is_answered' => new external_value(PARAM_BOOL, 'is_answered'),
                    'is_correct' => new external_value(PARAM_BOOL, 'is_correct'),
                ]),
            ),
            'is_exam_finished' => new external_value(PARAM_BOOL, 'is_exam_finished'),
            'message' => new external_value(PARAM_RAW, 'message'),
            'code' => new external_value(PARAM_INT, 'code'),
        ]);
    }

    /**
     * get user skip questions default.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question_default_parameters() {
        return new external_function_parameters (
            [
                'question_id' => new external_value(PARAM_INT, 'Question id'),
                'try_id' => new external_value(PARAM_INT, 'Try id'),
            ]
        );
    }

    /**
     * get user skip questions default.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question_default($questionid, $tryid) {
        global $DB, $USER;
        $submissiondefault = new stdClass();
        $params = [
            'question_id' => $questionid,
            'try_id' => $tryid,
        ];
        // Validating parameters.
        $params = self::validate_parameters(self::skip_question_default_parameters(), $params);
        // Add a page, required by validate_attempt.
        list($attemptobj, $messages) = self::validationof_attempt($params);
        if (!empty($attemptobj)) {
            $quiz = $attemptobj->get_quizobj()->get_quiz();
            list($quiz, $course, $cm, $context) = self::validationof_quiz($quiz->id);
            $quizattempt = $attemptobj->get_attempt();
            $timelimit = '00:00:00';
            if ($quiz->timelimit > 0) {
                $timelimit = gmdate('H:i:s', $quiz->timelimit);
            }

            // Questions to do.
            $questionids = get_listof_questions_todo($attemptobj->get_attempt(), $USER->id);
            $answeredids = get_listof_questions_completed($attemptobj->get_attempt(), $USER->id);
            $nextids = [];
            // $nextnextids = [];
            foreach ($questionids as $qkey => $value) {
                if ($value == $questionid) {
                    if (!empty(get_next_questionid($questionids, $qkey))) {
                        $nextids[] = get_next_questionid($questionids, $qkey);
                    }
                }
            }
            $nextquestionid = current($nextids);
            // foreach ($questionids as $qkey => $value) {
            //     if ($value == $nextquestionid) {
            //         if (!empty(get_next_questionid($questionids, $qkey))) {
            //             $nextnextids[] = get_next_questionid($questionids, $qkey);
            //         }
            //     }
            // }
            // $nextnextquestionid = current($nextnextids);

            $questionslist = get_questionids_list($quiz->id, $quizattempt->id);
            if (empty($questionslist)) {
                throw new yearbook_exception('questionsnotfound', 'local_yearbook');
            }

            // foreach ($questionslist as $q => $qid) {
            //     if ($qid == $questionid) {
            //         break;
            //     }
            // }

            // To get answerslist of the question in the array.
            $answerslistsql = "SELECT id, answer as answer_option FROM {question_answers} WHERE question = :questionid";
            // if (empty($nextnextquestionid) && empty($nextquestionid)) {
            //     $answers = [];
            //     $questiontext = '';
            // } 
            // if (!empty($nextquestionid) && !empty($nextnextquestionid)) {
            //     $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
            //     $questionrecord = $DB->get_record('question', ['id' => $nextquestionid]);
            //     $questiontext = $questionrecord->questiontext;
            // } else if (!empty($nextquestionid) && empty($nextnextquestionid)) {
            //     $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
            //     $questionrecord = $DB->get_record('question', ['id' => $nextquestionid]);
            //     $questiontext = $questionrecord->questiontext;
            // }

            $answers = $DB->get_records_sql($answerslistsql, ['questionid' => $questionid]);
            $questionrecord = $DB->get_record('question', ['id' => $questionid]);
            $questiontext = $questionrecord->questiontext;

            if (!empty($answers)) {
                $answerslist = $answers;
            } else {
                $answerslist = [];
                $obj = new stdClass();
                $obj->id = 0;
                $obj->answer_option = '';
                $answerslist[] = $obj;
            }

            $questiontype = 2;
            if ($questionrecord->qtype == 'multichoice') {
                $questiontype = 1;
            }

            // if (empty($nextnextquestionid) && empty($nextquestionid)) {
            //     $firstquestionid = current($questionslist);
            //     $nextnextquestionid = next($questionslist);
            //     $nextnextquestionid = (int) $nextnextquestionid;
            // } else if (empty($nextnextquestionid) && !empty($nextquestionid)) {
            //     $nextnextquestionid = current($questionslist);
            //     $nextnextquestionid = (int) $nextnextquestionid;
            // }
            // if (empty($nextquestionid)) {
            //     $questionslist = get_questionids_list($quiz->id, $quizattempt->id);
            //     $firstquestionagain = current($questionslist);
            //     $nextquestionid = (int) $firstquestionagain;
            //     $answerslist = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
            //     $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);
            //     $questiontext = $questionrec->questiontext;
            // }
            if (empty($nextquestionid)) {
                $questionslist = get_questionids_list($quiz->id, $quizattempt->id);
                $firstquestionagain = current($questionslist);
                $nextquestionid = (int) $firstquestionagain;
                // $answerslist = $DB->get_records_sql($answerslistsql, ['questionid' => $nextquestionid]);
                // $questionrec = $DB->get_record('question', ['id' => $nextquestionid]);
                // $questiontext = $questionrec->questiontext;
            }

            $submissiondefault->status = 'success';
            $submissiondefault->time_limit = $timelimit;
            $submissiondefault->question_text = $questiontext;
            $submissiondefault->answer_list = $answerslist;
            $submissiondefault->code = 1;

            // To get the tag names of the quiz.
            $tags = get_tag_names($quiz->id);
            $submissiondefault->tags = [];
            if (!empty($tags)) {
                $submissiondefault->tags = $tags;
            }
            $finished = false;
            if ($quizattempt->state == 'finished') {
                $finished = true;
            }
            
            $submissiondefault->exam_name = $quiz->name;

            $studentoption = [];

            if (!empty($answeredids)) {
                if (in_array($questionid, $answeredids)) {
                    $answroption = toget_choosen_answerid($attemptobj->get_attempt(), $questionid, $USER->id, 0);
                    $studentoption[] = $answroption;
                }
            }

            $submissiondefault->answered_questions_id = $answeredids;
            $submissiondefault->student_option = $studentoption;
            $submissiondefault->try_id = $quizattempt->id;
            $submissiondefault->question_type = $questiontype;
            $submissiondefault->questions_list = $questionslist;
            $array = [];
            $array['questionid'] = $questionid;
            $array['questionusageid'] = $attemptobj->get_attempt()->uniqueid;
            $pcount = $DB->get_field('question_attempts', 'slot', $array);
            $submissiondefault->position = $pcount;
            $submissiondefault->is_exam_finished = $finished;
            
            // To get course module id.
            $cmid = $attemptobj->get_cmid();

            // To get more quiz data.
            $morequiz = get_morequiz_data($cmid);

            $timetaken = '00:00:00';
            if ($quizattempt->state == 'finished') {
                $timetakenseconds = ($quizattempt->timefinish - $quizattempt->timestart);
                // To get time taken by the user of the attempt.
                if ($timetakenseconds > 0) {
                    $timetaken = get_time_conversion($timetakenseconds);
                }
            } else {
                $timetakenseconds = (time() - $quizattempt->timestart);
                // To get time taken by the user of the attempt.
                if ($timetakenseconds > 0) {
                    $timetaken = get_time_conversion($timetakenseconds);
                }
            }
            
            $sql = "UPDATE {question_attempts} set flagged = 1
                     WHERE questionid = :questionid
                       AND questionusageid = :quid";
            $updateflag = $DB->execute($sql, ['questionid' => $questionid, 'quid' => $attemptobj->get_attempt()->uniqueid]);
            $submissiondefault->more_quiz = $morequiz;
            $submissiondefault->time_taken = $timetaken;
            $submissiondefault->next_question_id = $nextquestionid;
            $submissiondefault->question_id = $questionid;
            return $submissiondefault;
        } else {
            throw new yearbook_exception('foundnoattempt', 'local_yearbook');
        }
    }

    /**
     * get user skip questions returns.
     * @return external_function_parameters.
     * @since Moodle 3.1
     */
    public static function skip_question_default_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
            'question_text' => new external_value(PARAM_RAW, 'question_text'),
            'answer_list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'id'),
                    'answer_option' => new external_value(PARAM_RAW, 'answer_option'),
                ]),
            ),
            'code' => new external_value(PARAM_INT, 'code'),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'tags'),
            ),
            'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
            'answered_questions_id' => new external_multiple_structure(
                new external_value(PARAM_INT, 'answered_questions_id'), 'answered_questions_id'
            ),
            'student_option' => new external_multiple_structure(
                new external_value(PARAM_INT, 'student_option'), 'student_option'
            ),
            'try_id' => new external_value(PARAM_INT, 'try_id'),
            'question_type' => new external_value(PARAM_INT, 'question_type'),
            'questions_list' => new external_multiple_structure(
                new external_value(PARAM_INT, 'questions_list'), 'questions_list'),
            'position' => new external_value(PARAM_INT, 'position'),
            'is_exam_finished' => new external_value(PARAM_BOOL, 'is_exam_finished'),
            'more_quiz' => new external_multiple_structure(
                new external_single_structure([
                    'tags' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'tags'),
                    ),
                    'exam_name' => new external_value(PARAM_RAW, 'exam_name'),
                    'no_of_questions' => new external_value(PARAM_RAW, 'no_of_questions'),
                    'mark' => new external_value(PARAM_INT, 'mark'),
                    'created_on' => new external_value(PARAM_RAW, 'created_on'),
                    'id' => new external_value(PARAM_INT, 'id'),
                    'image' => new external_value(PARAM_URL, 'image'),
                    'time_limit' => new external_value(PARAM_RAW, 'time_limit'),
                ]),
            ),
            'time_taken' => new external_value(PARAM_RAW, 'time_taken'),
            'next_question_id' => new external_value(PARAM_INT, 'next_question_id'),
            'question_id' => new external_value(PARAM_INT, 'question_id'),
        ]);
    }
}
