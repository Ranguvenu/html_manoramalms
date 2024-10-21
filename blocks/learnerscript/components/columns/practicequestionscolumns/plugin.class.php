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

/** LearnerScript Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @subpackage learnerscript
  * @author: Sudharani
  * @date: 2023
  */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\ls;

class plugin_practicequestionscolumns extends pluginbase{
  public function init(){
    $this->fullname = get_string('practicequestionscolumns','block_learnerscript');
    $this->type = 'undefined';
    $this->form = true;
    $this->reporttypes = array('practicequestions');
  }
  public function summary($data){
    return format_string($data->columname);
  }
  public function colformat($data){
    $align = (isset($data->align))? $data->align : '';
    $size = (isset($data->size))? $data->size : '';
    $wrap = (isset($data->wrap))? $data->wrap : '';
    return array($align,$size,$wrap);
  }
  public function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
    global $DB;
    $quizattempt = $DB->get_field_sql("SELECT id FROM {quiz_attempts} WHERE quiz = :quizid AND state = :state AND userid = :userid ORDER BY id DESC LIMIT 1", ['quizid' => $row->id, 'state' => 'finished', 'userid' => $row->userid]);

    switch ($data->column) {
            case 'chaptertopic':
               if(isset($row->sectionid) && !empty($row->sectionid)) {
                    $topic = $DB->get_record_sql("SELECT cs.name, cf.value
                        FROM {course_sections} cs 
                        JOIN {course_format_options} cf ON cf.sectionid = cs.id
                        WHERE cs.id = :sectionid AND cf.value <> 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);
                    if($topic) {
                        $topicname = ($topic->name == '') ? get_section_name($row->courseid, $row->section) : $topic->name;
                        $parent_chapterid= (new ls)->get_parent_chapter($row->courseid, $row->sectionid);
   
                        // $chapter = $DB->get_field('course_sections', 'name', array('section' => $topic->value, 'course' => $row->courseid));
                        $chapter = $DB->get_field('course_sections', 'name', array('id' => $parent_chapterid, 'course' => $row->courseid));
                        $chaptername = $chapter ? $chapter : get_section_name($row->courseid, $parent_chapterid);

                        $row->{$data->column} = '<div class="reportchapters">'.$chaptername.'</div><div class="text-muted reporttops">'.$topicname.'</div><div class="text-muted reporttops">'.$row->quiz.'</div>';
                    } else {
                        $chapter = $DB->get_record_sql("SELECT cs.name 
                        FROM {course_sections} cs 
                        JOIN {course_format_options} cf ON cf.sectionid = cs.id
                        WHERE cs.id = :sectionid AND cf.value = 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);
                        $chaptername = ($chapter->name) ? ($chapter->name) : get_section_name($row->courseid, $row->section);
                        $row->{$data->column} = $chaptername.'<div class="text-muted reporttops">'.$row->quiz.'</div>';
                    }
                } else {
                    $row->{$data->column} = 'NA';
                }
                break;
            case 'totalquestions':
                if(isset($row->totalquestions) && !empty($row->totalquestions)) {
                     $row->{$data->column} = $row->{$data->column};
                } else {
                        $questions = $DB->get_field_sql("SELECT count(DISTINCT id)
                        FROM {quiz_slots} 
                        WHERE quizid = :quizid", ['quizid' => $row->id]);
                    $row->{$data->column} = $questions ? $questions : 0;
                }
                break;
            case 'attempted':
                if(isset($row->attempted) && !empty($row->attempted)) {
                     $row->{$data->column} = $row->{$data->column};
                } else {
                    if($quizattempt) {
                        $questionsattempts = $DB->get_field_sql("SELECT count(DISTINCT qat.id)
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {quiz_attempts} qatp ON qatp.uniqueid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qas.userid = :userid AND qatp.id = :quizattempt AND qu.component = :component", ['userid' => $row->userid, 'component' => 'mod_quiz', 'quizattempt' => $quizattempt]);

                    } else {
                        $questionsattempts = 0;
                    }
                    
                    $row->{$data->column} = $questionsattempts ? $questionsattempts : 0;
                }
                break;
            case 'answered':
                if(isset($row->answered) && !empty($row->answered)) {
                     $row->{$data->column} = $row->{$data->column};
                } else {
                    if($quizattempt) {
                        $questionsattempts = $DB->get_field_sql("SELECT count(DISTINCT qat.id)
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {quiz_attempts} qatp ON qatp.uniqueid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qat.responsesummary IS NOT NULL AND qas.userid = :userid AND qatp.id = :quizattempt AND qu.component = :component", ['userid' => $row->userid, 'component' => 'mod_quiz', 'quizattempt' => $quizattempt]);
                    } else {
                        $questionsattempts = 0;
                    }
                    
                    $row->{$data->column} = $questionsattempts ? $questionsattempts : 0;
                }
                break;
            case 'correct':
                if(isset($row->correct) && !empty($row->correct)) {
                    $row->{$data->column} = $row->{$data->column};
                } else {
                    $correct = 0;
                    $wrong = 0;
                    $questions = $DB->get_records_sql("SELECT DISTINCT qat.questionid, qat.rightanswer, qat.responsesummary
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qas.userid = :userid AND ctx.instanceid = :instanceid AND qu.component = :component", ['userid' => $row->userid, 'instanceid' => $row->instanceid, 'component' => 'mod_quiz']);
                    if($quizattempt) {
                        $questionsattempt = $DB->get_records_sql("SELECT DISTINCT qat.questionid, qat.rightanswer, qat.responsesummary
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {quiz_attempts} qatp ON qatp.uniqueid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qas.userid = :userid AND qatp.id = :quizattempt AND qu.component = :component", ['userid' => $row->userid, 'component' => 'mod_quiz', 'quizattempt' => $quizattempt]);
                        if(!empty($questionsattempt)) {
                            foreach ($questionsattempt as $key => $value) {
                                if($value->responsesummary != 'NULL'){
                                    if($value->rightanswer == $value->responsesummary) {
                                        $correct++;
                                    } else {
                                        $wrong++;
                                    }
                                }
                            
                            }
                        }
                    }
                    
                    $correctanswers = $correct;
                    $row->wronganswers = $wrong;
                    $row->{$data->column} = $correctanswers ? $correctanswers : 0;
                }
                break;
            case 'wrong':
                if(isset($row->wrong) && !empty($row->wrong)) {
                    $row->{$data->column} = $row->{$data->column};
                } else {
                    $row->{$data->column} = $row->wronganswers ? $row->wronganswers : 0;
                }
                break;
            }
    return (isset($row->{$data->column}))? $row->{$data->column} : '';
  }
}
