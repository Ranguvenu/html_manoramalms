<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/questionlib.php');
global $DB;
$limit = optional_param('limit', 1000,  PARAM_INT);

// $existingquestions = $DB->get_records_sql('SELECT q.id FROM {question} q JOIN {question_versions} qv  ', [], 'id DESC', 'id', 0, $deletelimit);
// foreach($existingquestions AS $question){
// 	mtrace("Started Deletion of question with id $question->id");
// 	question_delete_question($question->id);
// 	mtrace("Deletion of question with id $question->id completed");
// }
$missedquestionssql = "SELECT lqc.*, qbe.idnumber FROM {local_questions_courses} lqc 
JOIN {question_versions} qv ON qv.questionid = lqc.questionid
JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
WHERE lqc.goalid = 0 OR lqc.boardid = 0 OR lqc.classid  = 0 OR lqc.courseid = 0 OR lqc.unitid = 0 OR lqc.chapterid = 0 OR lqc.topicid = 0 ORDER by lqc.id DESC";
$missedquestions = $DB->get_records_sql($missedquestionssql, [], 0, $limit);
foreach($missedquestions AS $question){
	$hierarchysql = "SELECT lah.* FROM {local_actual_hierarchy} lah 
	JOIN {test_center_question_oldhierarchy} tcqh ON TRIM(tcqh.course_class) = TRIM(lah.course_class) AND TRIM(tcqh.source_name) = TRIM(lah.source_name) AND TRIM(tcqh.topic) = TRIM(lah.topic) AND TRIM(lah.subject) = TRIM(tcqh.subject) WHERE tcqh.idnumber = :idnumber ";
	$questionhierarchy = $DB->get_record_sql($hierarchysql, ['idnumber' => $question->idnumber]);
	mtrace("Question hierarchy found with details with id {$questionhierarchy->id}");
	if ($questionhierarchy) {
		$goal_label  		  = trim($questionhierarchy->act_goal);
		$board_label 		  = trim($questionhierarchy->act_board);
		$class_label 		  = trim($questionhierarchy->act_class);
		$subject_label  	  = trim($questionhierarchy->act_subject);
		$unit_label 		  = trim($questionhierarchy->act_unit);
		$chapter_label 		  = trim($questionhierarchy->act_chapter);
		$topic_label 		  = trim($questionhierarchy->act_topic);
		$source_label 		  = trim($questionhierarchy->act_source);
		$findnext = true;

		if(!empty($goal_label)){
			// $goalid = $DB->get_field('local_hierarchy', 'id', ['name' => $goal_label, 'depth' => 1]);
			$goalid = $DB->get_field_sql('SELECT id FROM {local_hierarchy} WHERE TRIM(name) LIKE :name AND depth = :depth AND parent = :parent', ['name' => trim($goal_label), 'depth' => 1, 'parent' => 0]);
			if($goalid){
				$question->goalid = $goalid;
			}else{
                $findnext = false;
                $question->goalid = 0;
            }
		}else{
			$question->goalid = 0;
            $findnext = false;
        }


        if(!empty($board_label) && $findnext){
           
            $boardsql ="SELECT hi.id from {local_hierarchy} as hi WHERE TRIM(hi.name) LIKE :bname AND  hi.parent = :parentid AND hi.depth = 2 ";
            $boardid = $DB->get_field_sql($boardsql, ['parentid' => $question->goalid, 'bname' => trim($board_label)]);
         
			if($boardid){
				$question->boardid = $boardid;
			}else{
                $question->boardid = 0;
                $findnext = false;  
            }
		}else{
			$question->boardid = 0;
            $findnext = false;
        }

        if(!empty($class_label) && $findnext){
			// $classid = $DB->get_field('local_hierarchy', 'id', ['name' => $class_label,'parent' => $question->boardid,'depth' => 3]);
			$classid = $DB->get_field_sql('SELECT id FROM {local_hierarchy} WHERE TRIM(name) like :name AND parent = :parent AND depth = :depth ', ['name' => trim($class_label),'parent' => $question->boardid,'depth' => 3]);
			$classid = $classid ? $classid:0;
            $classsql ="SELECT hi.id,hi.name as fullname from {local_hierarchy} as hi WHERE hi.id = $classid AND hi.parent = $question->boardid AND  hi.depth =3";
            $getclass = $DB->get_records_sql($classsql);
			if($getclass){
               
				$question->classid = $classid;
			}else{
				$question->classid = 0;
                $findnext = false;   
            }
		}else{
			$question->classid = 0;
            $findnext = false;
        }
        if(!empty($subject_label) && $findnext){
			// $courseid = $DB->get_field('local_subjects', 'courseid', ['name' => $subject_label,'classessid' => $question->classid]);
			$courseid = $DB->get_field_sql('SELECT courseid FROM {local_subjects} WHERE TRIM(name) LIKE :name AND classessid = :classessid ', ['name' => trim($subject_label),'classessid' => $question->classid]);
			$courseid = $courseid ? $courseid:0;
            $coursesql ="SELECT sub.courseid as id,sub.name as fullname 
                         FROM {local_subjects} AS sub 
                         WHERE sub.courseid= $courseid 
                         AND sub.classessid = $question->classid";
            $getcourse = $DB->get_records_sql($coursesql);
			if($getcourse){
				$question->courseid = $courseid;
			}else{
				$findnext =false;
				$question->courseid = 0;
			}
		}else{
			
            $findnext = false;
			$question->courseid = 0;
           
        }
        if(!empty($unit_label) && $findnext){
			// $uid = $DB->get_field('local_units', 'id', ['name' => $unit_label,'courseid' => $question->courseid]);  
			$uid = $DB->get_field_sql('SELECT id FROM {local_units} WHERE TRIM(name) LIKE :name AND courseid = :courseid ', ['name' => trim($unit_label),'courseid' => $question->courseid]);  
			if($uid){
				$question->topicid = $uid;
			}else{
				$findnext =false;
				$question->topicid = 0;
			}
		}else{
            $findnext = false;
			$question->topicid = 0;
        }
		if(!empty($chapter_label) && $findnext){     
			// $chpid = $DB->get_field_sql('local_chapters', 'id', ['name' => $chapter_label,'courseid' => $question->courseid, 'unitid' => $question->topicid]); 
             $chpid = $DB->get_field_sql('SELECT id FROM {local_chapters} WHERE TRIM(name) LIKE :name AND courseid = :courseid AND unitid = :unitid',['name' => trim($chapter_label),'courseid' => $question->courseid, 'unitid' => $question->topicid]);

			if($chpid){
				$question->chapterid = $chpid;
			}else{
				$findnext =false;
				$question->chapterid = 0;
			}
		}else{
            $findnext = false;
			$question->chapterid = 0;
        }
        if(!empty($topic_label)  && $findnext){
            // $tid = $DB->get_field('local_topics', 'id', ['name' => $topic_label,'courseid' => $question->courseid, 'unitid' => $uid, 'chapterid' => $question->chapterid]);
            $tid = $DB->get_field_sql('SELECT id FROM {local_topics} WHERE TRIM(name) LIKE :name AND courseid = :courseid AND unitid = :unitid AND chapterid = :chapterid ', ['name' => trim($topic_label),'courseid' => $question->courseid, 'unitid' => $uid, 'chapterid' => $question->chapterid]);
           if($tid){
				$question->unitid = $tid;
			}else{
				$findnext =false;
				$question->unitid = 0;
			}
		}else{
			 $findnext = false;
			$question->unitid = 0;
		}
		$DB->update_record('local_questions_courses',  $question);
		\question_bank::load_question_data($question->questionid);
		mtrace("Updated question with id {$question->idnumber}");
		// if(!empty($concept_label)  && $findnext){
		//  	  $cname =$concept_label;
        //       $conceptsql = "SELECT id FROM {local_concept} WHERE TRIM(name) LIKE :name AND courseid =:courseid AND unitid =:unitid AND chapterid =:chapterid AND topicid =:topicid  ";
        //       $params =[];
        //       $params['name'] = trim($concept_label);
        //       $params['courseid'] = $question->courseid;
        //       $params['unitid'] = $uid;
        //       $params['chapterid'] =$question->chapterid;
        //       $params['topicid'] = $question->unitid;
        //       $conceptid = $DB->get_record_sql($conceptsql, $params);
        //       $concid =$conceptid->id;
        //    if($concid){
		// 		$question->customfield_concept = $concid;
		// 	}else{
		// 		$findnext =false;
		// 		$question->customfield_concept = 0;
		// 	}
		// }else{
		// 	 $findnext = false;
		// 	$question->customfield_concept = 0;
		// }
	}
}