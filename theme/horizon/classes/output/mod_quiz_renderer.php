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

namespace theme_horizon\output;

use cm_info;
use coding_exception;
use context;
use context_module;
use html_table;
use html_table_cell;
use html_writer;
use mod_quiz\access_manager;
use mod_quiz\form\preflight_check_form;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use moodle_url;
use plugin_renderer_base;
use popup_action;
use question_display_options;
use mod_quiz\quiz_settings;
use renderable;
use single_button;
use stdClass;
/**
 * Class mod_quiz_renderer
 *
 * @package    theme_horizon
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz\output\renderer {
  /*
    * View Page
    */
  /**
   * Generates the view page
   *
   * @param stdClass $course the course settings row from the database.
   * @param stdClass $quiz the quiz settings row from the database.
   * @param stdClass $cm the course_module settings row from the database.
   * @param context_module $context the quiz context.
   * @param view_page $viewobj
   * @return string HTML to display
   */
  public function view_page($course, $quiz, $cm, $context, $viewobj) {
    global $CFG,$DB,$OUTPUT,$USER;
    if ($quiz->cmid) {
      $quizobj = quiz_settings::create_for_cmid($quiz->cmid, $USER->id);
    } else {
      $quizobj = quiz_settings::create($quiz->id, $USER->id);
    }
    $context = $quizobj->get_context();
    // Cache some other capabilities we use several times.
    $canattempt = has_capability('mod/quiz:attempt', $context);

    $data = quiz_get_attempt_preview_data($quiz);
    // $data['attemptscount'] = count($viewobj->attempts);
    $data['startattempturl'] = htmlspecialchars_decode($viewobj->startattempturl->out(true));
    $data['scorecardurl'] = $CFG->wwwroot.'/theme/horizon/allattempts.php?id='.$quiz->cmid;
    $migratedattempts =$DB->count_records('local_question_attempts',['cmid'=>$quiz->cmid,'quizid'=>$quiz->id,'userid'=>$USER->id]);
    if(!is_siteadmin() && $canattempt) {
      if($migratedattempts == 1) {
        $attemptid =(int)$DB->get_field('local_question_attempts','attemptid',['cmid'=>$quiz->cmid,'quizid'=>$quiz->id,'userid'=>$USER->id]);
        $mdataurl = $CFG->wwwroot.'/local/masterdata/viewattempt.php?attemptid='.$attemptid.'&cmid='.$quiz->cmid;
      } else {
        $mdataurl  = $CFG->wwwroot. '/local/masterdata/viewquizattempts.php?quizid='.$quiz->id;
      }
      $mqparams = ['cmid'=>$quiz->cmid,'quizid'=>$quiz->id,'userid'=>$USER->id];
    } else {
      $mqparams = ['cmid'=>$quiz->cmid,'quizid'=>$quiz->id];
      $mdataurl  = $CFG->wwwroot. '/local/masterdata/viewquizattempts.php?quizid='.$quiz->id;
    }
    $migratedattemptscount = $DB->count_records('local_question_attempts',$mqparams);
    $data['migratedattemptscount'] = ($migratedattemptscount > 0) ? html_writer::tag('a',$migratedattemptscount,array('href' =>$mdataurl)) : 0;
    $data['migratedattemptview'] = ($migratedattemptscount > 0) ? true : false;
    $allwedattempts = $quizobj->get_num_attempts_allowed();

    $totaluserattempts = quiz_get_user_attempts($quiz->id, $USER->id, 'all', true);

    $quizstartdate = userdate($quiz->timeopen,get_string('strftimedatemonthabbr', 'core_langconfig'));
    $quizenddate = userdate($quiz->timeclose,get_string('strftimedatemonthabbr', 'core_langconfig'));
    $data['quizdate'] = $quizstartdate.' - '.$quizenddate;
    $data['canuserattempt'] = ($quiz->attempts == 0 || ($quiz->attempts > 0 && ((COUNT($totaluserattempts) + $migratedattempts) < $allwedattempts))) ? true :  false;

    echo $OUTPUT->render_from_template('theme_horizon/quiz_initial_view', $data);
  }

  /**
   * Outputs the table containing data from summary data array
   *
   * @param array $summarydata contains row data for table
   * @param int $page contains the current page number
   * @return string HTML to display.
   */
    public function review_summary_table($summarydata, $page, $attemptobj = []) {
        global $COURSE, $DB, $CFG, $USER;
        // $cmid = optional_param('id', '', PARAM_INT);
        $attempt = optional_param('attempt', '', PARAM_INT);
        $attempted = [];
        $object = new stdClass();
        $correctcount = 0;
        $wrongcount = 0;
        $unansweredcount = 0;
        $objattempt = $DB->get_record('quiz_attempts', ['id' => $attempt]);
        if ($objattempt) {
            $examname = $DB->get_field('quiz', 'name', ['id' => $objattempt->quiz]);
            $object->examname = $examname;
            $object->coursename = $COURSE->fullname;
            $object->attempt = $objattempt->attempt;
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

            $correctcount = $DB->count_records_sql($csql, [$objattempt->uniqueid, $objattempt->userid]);
            $wrongcount = $DB->count_records_sql($wsql, [$objattempt->uniqueid, $objattempt->userid]);
            $unansweredcount = $DB->count_records_sql($usql, [$objattempt->uniqueid, $objattempt->userid]);
        }
        $quiz = $DB->get_record('quiz', ['id' => $objattempt->quiz]);
        $a = new stdClass();
        $a->grade = quiz_format_grade($quiz, $objattempt->sumgrades);
        $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
        $summarydata['marks'] = [
            'title'   => get_string('marks', 'quiz'),
            'content' => get_string('outofshort', 'quiz', $a),
        ];
        foreach ($summarydata as $rowdata) {
            // if (!empty($rowdata['title'] == 'Grade')) {
            //     $object->reviewdata = strip_tags($rowdata['content']);
            // }
            if (!empty($rowdata['title'] == 'Marks')) {
                // $object->reviewdata = $rowdata['content'];
                $maxgrade = $quiz->grade;
                // $DB->get_field('grade_items', 'grademax', ['iteminstance' => $quiz->id, 'courseid' => $COURSE->id]);
                $sql = "SELECT gg.finalgrade AS finalgrade 
                            FROM {grade_grades} gg  
                            JOIN {grade_items} gi ON gg.itemid = gi.id  
                           WHERE 1 = 1 AND gi.itemmodule = :itemmodule AND gg.userid = :userid AND gi.iteminstance = :iteminstance ";

                $finalgrade = $DB->get_record_sql($sql, ['itemmodule' => 'quiz', 'userid' => $objattempt->userid, 'iteminstance' => $quiz->id]);

                $customfielddata = $DB->get_record_sql("SELECT cff.id, cff.shortname, cfd.value FROM {customfield_field} cff JOIN {customfield_data} cfd ON cfd.fieldid = cff.id WHERE cfd.instanceid = :cmid AND shortname = :shortname", ['cmid' => $attemptobj->get_cm()->id, 'shortname' => 'totalquestions']);

                $object->reviewdata = number_format($finalgrade->finalgrade, 2) .'/'. number_format($maxgrade, 2);

            }
            // if ($rowdata['title'] == 'Time taken') {
            //     $timetakensec = $rowdata['content'];
            //     $timetaken = gmdate('H:i:s', (int) $timetakensec);
            //     $object->timetaken = $timetaken;
            // }
        }
        $timetaken = '00:00:00';
        if ($objattempt->timefinish > 0 && $objattempt->timestart > 0) {
            $timetakenunix = $objattempt->timefinish - $objattempt->timestart;
            $timetaken = gmdate('H:i:s', $timetakenunix);
        }
        
        $object->timetaken = $timetaken;
        $object->answeredcorrect = $correctcount;
        $object->answeredwrong = $wrongcount;
        $object->unanswered = $unansweredcount;
        $content = [
            'data' => $object,
            'backtocourse' => $CFG->wwwroot.'/course/view.php?id='.$COURSE->id,
        ];
        
        return $this->output->render_from_template('theme_horizon/reviewpageheader', $content);
    }
}
