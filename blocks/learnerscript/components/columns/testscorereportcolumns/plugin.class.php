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
use moodle_url;
use DateTime;
class plugin_testscorereportcolumns extends pluginbase{
  public function init(){
    $this->fullname = get_string('testscorereportcolumns','block_learnerscript');
    $this->type = 'undefined';
    $this->form = true;
    $this->reporttypes = array('testscorereport');
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
    global $DB, $CFG;
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

                        $row->{$data->column} = '<div class="reportchapters">'.$chaptername.'</div><div class="text-muted reporttops">'.$topicname.'</div><div class="text-muted reporttops">'.$row->activity.'</div>';
                    } else {
                        $chapter = $DB->get_record_sql("SELECT cs.name 
                        FROM {course_sections} cs 
                        JOIN {course_format_options} cf ON cf.sectionid = cs.id
                        WHERE cs.id = :sectionid AND cf.value = 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);
                        $chaptername = ($chapter->name) ? ($chapter->name) : get_section_name($row->courseid, $row->section);
                        $row->{$data->column} = '<div class="reportchapters">'.$chaptername.'</div><div class="reportchapters">'.$row->activity.'</div>';
                    }
                } else {
                    $row->{$data->column} = 'NA';
                }
                break;
            case 'datetime':
                if(isset($row->startdate) && !empty($row->startdate)) {
                    $startdate = !empty($row->startdate) ? userdate($row->startdate, "%d %b %Y") : '';
                    $starttime = !empty($row->startdate) ? userdate($row->startdate, "%H:%m %p") : '';

                    $duedate = !empty($row->duedate) ? userdate($row->duedate, "%d %b %Y") : '';
                    $duetime = !empty($row->duedate) ? userdate($row->duedate, "%H:%m %p") : '';

                    $row->{$data->column} = $startdate.' '.$starttime.' '.'<br>'.$duedate.' '.$duetime;

                } else {
                    $row->{$data->column} = 'NA';
                }
            break;
            case 'activity':
                if(isset($row->activity) && !empty($row->activity)) {
                    $row->{$data->column} = $row->{$data->column};
                } else {
                    $row->{$data->column} = '';
                }
                break;
            case 'score':
                if(isset($row->score) && !empty($row->score)) {
                    $row->{$data->column} = $row->{$data->column};
                } else {
                    $modulesql = "SELECT gg.finalgrade 
                            FROM {grade_grades} gg 
                            JOIN {grade_items} gi ON gi.id = gg.itemid
                            JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND gi.courseid = cm.course
                            JOIN {modules} m ON m.id = cm.module";
                        if($row->modulename == 'quiz') {
                            $modulesql .= " JOIN {quiz_attempts} qa ON qa.quiz = cm.instance AND qa.state = 'finished'";
                        } else {
                            $modulesql .= " JOIN {assign_submission} asub ON asub.assignment = cm.instance AND asub.status = 'submitted'";
                        }

                            $modulesql .= " WHERE gi.itemmodule = :modulename AND gi.iteminstance = :activityid AND gg.userid = :userid AND gi.courseid = :courseid AND gi.itemtype = 'mod'";

                        $score = $DB->get_field_sql($modulesql, ['modulename' => $row->modulename, 'activityid' => $row->activityid, 'userid' => $row->userid, 'courseid' => $row->courseid] )
                             ;
                    //echo '<pre>';print_r($score);exit;
                    $row->{$data->column} = $score ? round($score, 2).' / '.ROUND($row->grade, 0) : '--';
                }
                break;
            case 'status':
                if(isset($row->status) && !empty($row->status)){
                     $row->{$data->column} = $row->{$data->column};
                }else{
                  
                    if($row->modulename == 'assign') {
                        $submission = $DB->get_field_sql("SELECT id FROM {assign_submission}
                           WHERE assignment = :activityid AND status = 'submitted'
                            AND userid = :userid", ['userid' => $row->userid, 'activityid' => $row->activityid]);
                        if(!empty($submission)){
                            $status = get_string('submitted', 'block_learnerscript');
                        } else {
                            $date = new DateTime();
                            $timestamp = $date->getTimestamp();
                            if($timestamp > $row->duedate) {
                                $status = get_string('missed', 'block_learnerscript');
                            } else {
                                $started = $DB->get_field_sql("SELECT id FROM {assign_submission}
                           WHERE assignment = :activityid AND userid = :userid AND status = 'new'", ['userid' => $row->userid, 'activityid' => $row->activityid]);
                                if($started) {
                                    $status = get_string('inprogress', 'block_learnerscript');
                                } else {
                                    $status = get_string('notstarted', 'block_learnerscript');
                                }
                                
                            }
                        }
                    } else {
                         $submitteddate =  $DB->get_field_sql("SELECT qa.id FROM {quiz_attempts} qa WHERE qa.quiz = :quizid AND qa.userid=:userid AND qa.state = 'finished' ORDER BY attempt DESC LIMIT 1", ['userid' => $row->userid,'quizid' => $row->activityid]);
                         if($submitteddate) {
                            $status = get_string('submitted', 'block_learnerscript');
                         } else {
                            $date = new DateTime();
                            $timestamp = $date->getTimestamp();
                            if(!empty($row->duedate)) {
                                 if($timestamp > $row->duedate) {
                                $status = get_string('missed', 'block_learnerscript');
                                } else {
                                    $started =  $DB->get_field_sql("SELECT qa.id FROM {quiz_attempts} qa WHERE qa.quiz = :quizid AND qa.userid=:userid AND state = 'inprogress' ORDER BY attempt DESC LIMIT 1", ['userid' => $row->userid,'quizid' => $row->activityid]);
                                    if ($started) {
                                        $status = get_string('inprogress', 'block_learnerscript');
                                    } else {
                                        $status = get_string('notstarted', 'block_learnerscript');
                                    }
                                    
                                }
                            } else {
                                $status = get_string('notstarted', 'block_learnerscript');
                            }
                           
                         }
                    }
                    $status = $status ? $status : 'NA';
                }
                $row->{$data->column} = $status ? $status : 'NA';
                break;
            case 'scorecard':
                $url = new moodle_url('/mod/'.$row->modulename.'/view.php',
                                   array('id' => $row->instanceid,'userid' => $row->userid));
                if($row->status == 'Submitted') {
                  $row->{$data->column} =  '<a href="'.$url.'"><button type="button" class="btn btn-primary activitystatus">View Score</button></a>';
                } else if($row->status == 'Not started') {
                    $row->{$data->column} =  '<a href="'.$url.'"><button type="button" class="btn btn-primary">Attend Now</button></a>';
                } else {
                    $row->{$data->column} =  '<a href="'.$url.'"><button type="button" class="btn btn-primary activitystatus">Answer Key</button></a>';
                }

                break;
            }
    return (isset($row->{$data->column}))? $row->{$data->column} : '';
  }
}
