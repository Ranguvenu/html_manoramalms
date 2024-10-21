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

use coding_exception;
use mod_quiz\event\quiz_grade_updated;
use question_engine_data_mapper;
use stdClass;

/**
 * This class contains all the logic for computing the grade of a quiz.
 *
 * There are two sorts of calculation which need to be done. For a single
 * attempt, we need to compute the total attempt score from score for each question.
 * And for a quiz user, we need to compute the final grade from all the separate attempt grades.
 *
 * @package   mod_quiz
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_grade_calculator extends \mod_quiz\grade_calculator {
    /**
     * Constructor. Recommended way to get an instance is $quizobj->get_grade_calculator();
     *
     * @param quiz_settings $quizobj
     */
    protected function __construct(\mod_quiz\quiz_settings $quizobj) {
        $this->quizobj = $quizobj;
        parent::__construct($this->quizobj);
    }

    public static function create(\mod_quiz\quiz_settings $quizobj): custom_grade_calculator {
        return new self($quizobj);
    }
    /**
     * Update the sumgrades field of attempts at this quiz.
     */
    public function recompute_all_attempt_sumgrades($userid = null): void {
        global $DB;
        $timenow = time();
        $cm = $this->quizobj->get_cm();
        $quizinfo = $this->quizobj->get_quiz();
        $customfielddata = $DB->get_records_sql("SELECT cff.id, cff.shortname, cfd.value FROM {customfield_field} cff JOIN {customfield_data} cfd ON cfd.fieldid = cff.id WHERE cfd.instanceid = :cmid ", ['cmid' => $cm->id]);
        
        foreach($customfielddata AS $customdata) {
            $quizinfo->{$customdata->shortname} = $customdata->value;
        }

        if ($quizinfo->nsca && $quizinfo->nswa && $userid) { // Confg and userid is present(User Normal Submit).
            $grade = $this->sum_usage_marks_subquery('uniqueid', $quizinfo, $userid, true);
        } else if($quizinfo->nsca && $quizinfo->nswa && !$userid) { // Confg is present and userid is not present(Regrade When Neet Schema quiz).
            $grade = $this->sum_usage_marks_subquery('uniqueid', $quizinfo, false, true);
        } else if(!$quizinfo->nsca && !$quizinfo->nswa && !$userid) { // Confg and userid both not present(Regrade When normal quiz).
            $grade = $this->sum_usage_marks_subquery('uniqueid', $quizinfo, false, false);
        } else if($quizinfo->totalquestions > 0) { // Confg and userid both not present(Regrade When normal quiz).
            $grade = $this->sum_usage_marks_subquery('uniqueid', $quizinfo, false, false);
        } else {
            return;
        }

        $user = '';
        if ($userid) {
            $user = " AND userid = $userid ";
        }
        $DB->execute("
                UPDATE {quiz_attempts}
                   SET timemodified = :timenow,
                       sumgrades = (
                           {$grade}
                       )
                 WHERE quiz = :quizid AND state = :finishedstate $user
            ", [
                'timenow' => $timenow,
                'quizid' => $this->quizobj->get_quizid(),
                'finishedstate' => \mod_quiz\quiz_attempt::FINISHED
            ]);
    }
    /**
     * Return a sub-query that computes the sum of the marks for all the questions
     * in a usage. Which usage to compute the sum for is controlled by the $qubaid
     * parameter.
     *
     * See {@see \mod_quiz\grade_calculator::recompute_all_attempt_sumgrades()} for an example of the usage of
     * this method.
     *
     * This method may be called publicly.
     *
     * @param string $qubaid SQL fragment that controls which usage is summed.
     * This will normally be the name of a column in the outer query. Not that this
     * SQL fragment must not contain any placeholders.
     * @return string SQL code for the subquery.
     */
    public function sum_usage_marks_subquery($qubaid, $quizinfo, $userid=false, $state=false) {
        global $DB;
        // To explain the COALESCE in the following SQL: SUM(lots of NULLs) gives
        // NULL, while SUM(one 0.0 and lots of NULLS) gives 0.0. We don't want that.
        // We always want to return a number, so the COALESCE is there to turn the
        // NULL total into a 0.
        if ($quizinfo->totalquestions > 0 ){
            $limitquery = " LIMIT {$quizinfo->totalquestions} ";
        } else {
            $limitquery = '';
        }
        $user = '';
        if ($userid) {
            $user = " AND qas.userid = $userid ";
        }

        if ($state) {
            if (isset($quizinfo->nsca)) {
                $correctmark = floatval($quizinfo->nsca);
            } else {
                $correctmark = 4; // dirty hack not needed.
            }
            if (isset($quizinfo->nswa)) {
                $wrongmark = floatval($quizinfo->nswa);
            } else {
                $wrongmark = 4; // dirty hack not needed.
            }

            return "SELECT sum(grades) FROM (SELECT (CASE when qas.state LIKE 'gradedright' THEN {$correctmark} WHEN qas.state LIKE 'gradedwrong' THEN {$wrongmark} ELSE 0 END) as grades
                        FROM mdl_question_attempts qa
                        JOIN mdl_question_attempt_steps qas ON qas.questionattemptid = qa.id AND qas.sequencenumber = (
                                        SELECT MAX(summarks_qas.sequencenumber)
                                          FROM mdl_question_attempt_steps summarks_qas
                                         WHERE summarks_qas.questionattemptid = qa.id
                        )
                        WHERE qas.state IN ('gradedright', 'gradedwrong') AND qa.questionusageid = $qubaid $user ORDER BY qa.id ASC $limitquery ) customtable ";
        } else {


                return "SELECT sum(grades) FROM (SELECT (CASE when qas.state LIKE 'gradedright' THEN (qa.maxmark * qas.fraction) WHEN qas.state LIKE 'gradedwrong' THEN (qa.maxmark * qas.fraction) ELSE 0 END) as grades
                        FROM mdl_question_attempts qa
                        JOIN mdl_question_attempt_steps qas ON qas.questionattemptid = qa.id AND qas.sequencenumber = (
                                        SELECT MAX(summarks_qas.sequencenumber)
                                          FROM mdl_question_attempt_steps summarks_qas
                                         WHERE summarks_qas.questionattemptid = qa.id
                        )
                        WHERE qas.state IN ('gradedright', 'gradedwrong') AND qa.questionusageid = $qubaid $user ORDER BY qa.id ASC $limitquery ) customtable ";
            // return "SELECT COALESCE(SUM(qa.maxmark * qas.fraction), 0)
            //         FROM {question_attempts} qa
            //         JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
            //                 AND qas.sequencenumber = (
            //                         SELECT MAX(summarks_qas.sequencenumber)
            //                         FROM {question_attempt_steps} summarks_qas
            //                         WHERE summarks_qas.questionattemptid = qa.id 
            //         )
            //         WHERE qa.questionusageid = $qubaid $user
            //         HAVING COUNT(CASE
            //             WHEN qas.state = 'needsgrading' AND qa.maxmark > 0 THEN 1
            //             ELSE NULL
            //         END) = 0  ORDER BY qa.id ASC $limitquery ";
        }
    }
}
