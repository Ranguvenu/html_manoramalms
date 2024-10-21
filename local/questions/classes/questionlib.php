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
namespace local_questions;
defined('MOODLE_INTERNAL') || die;
class questionlib{
	public function set_additional_information($question){
		global $DB;
		$return = '';
		// additional fields in export format.
		$questionaddlinfo = $DB->get_record('local_questions_courses', array('questionid' => $question->id));
		if($questionaddlinfo->goalid){
			$return .= $this->get_hierarchy_info($questionaddlinfo->goalid, 'goal');		
		}
		if($questionaddlinfo->boardid){
			$return .= $this->get_hierarchy_info($questionaddlinfo->boardid, 'board');
		}
		if($questionaddlinfo->classid){
			$return .= $this->get_hierarchy_info($questionaddlinfo->classid, 'class');
		}
		if($questionaddlinfo->courseid){
			$return .= $this->get_course_info($questionaddlinfo->courseid, 'course');
		}
		if($questionaddlinfo->topicid){
			$return .= $this->get_topic_info($questionaddlinfo->topicid, 'topics');
		}
		if($questionaddlinfo->chapterid){
			$return .= $this->get_chapter_info($questionaddlinfo->chapterid, 'chapter');
		}
		if($questionaddlinfo->unitid){
			$return .= $this->get_unit_info($questionaddlinfo->unitid, 'unit');
		}
		if($questionaddlinfo->conceptid){
			$return .= $this->get_concept_info($questionaddlinfo->conceptid, 'concept');
		}
        if($questionaddlinfo->difficulty_level){
			$return .= $this->get_diffiuculty_info($questionaddlinfo->difficulty_level, 'difficultylevel');
		}
        if($questionaddlinfo->cognitive_level){
			$return .= $this->get_cognitive_info($questionaddlinfo->cognitive_level, 'cognitivelevel');
		}
        if($questionaddlinfo->source){
			$return .= $this->get_source_info($questionaddlinfo->source, 'source');
		}
		return $return;
	}
	private function get_hierarchy_info($infofield, $infolabel){
		global $DB;
		$info = $DB->get_field('local_hierarchy', 'code', array('id' => $infofield));
		if($info){
			return "<{$infolabel}>{$info}</{$infolabel}>\n";
		}
	}
	private function get_course_info($courseid, $courselabel){
		global $DB;
		$coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
		if($coursecode){
			return "<{$courselabel}>{$coursecode}</{$courselabel}>\n";
		}
	}
	private function get_topic_info($topicids, $topiclabel){
		global $DB;
        $topicssql = "SELECT id AS id,name as fullname
        FROM {local_units} WHERE id = $topicids";
		$coursetopiccode = $DB->get_records_sql_menu($topicssql);
        $coursetopicname=implode(',',$coursetopiccode);
		if($coursetopiccode){
			return "<{$topiclabel}>{$coursetopicname}</{$topiclabel}>";
		}
	}
	private function get_chapter_info($chapterid, $topiclabel){
		global $DB;
		
        $chaptersql = "SELECT id AS id,name as fullname
        FROM {local_chapters} WHERE id = $chapterid ";
		$chaptercode = $DB->get_records_sql_menu($chaptersql);
		$chaptername=implode(',',$chaptercode);
		if($chaptername){
			return "<{$topiclabel}>{$chaptername}</{$topiclabel}>";
		}
	}
	private function get_unit_info($unitid, $topiclabel){
		global $DB;
        $unitsql = "SELECT id AS id,name as fullname
        FROM {local_topics} WHERE id = $unitid ";
		$unitcode = $DB->get_records_sql_menu($unitsql);
		$unitname=implode(',',$unitcode);
		if($unitname){
			return "<{$topiclabel}>{$unitname}</{$topiclabel}>";
		}
	}
	private function get_concept_info($conceptid, $conceptlabel){
		global $DB;
        $conceptsql = "SELECT id AS id,name as fullname FROM {local_concept} WHERE id = $conceptid";
		$conceptcode = $DB->get_records_sql_menu($conceptsql);
		$conceptname=implode(',',$conceptcode);
		if($conceptname){
			return "<{$conceptlabel}>{$conceptname}</{$conceptlabel}>";
		}
	}
    private function get_diffiuculty_info($difficulty, $difficultylabel){
		global $DB;
		$difficultycode = $DB->get_field('local_questions_courses', 'difficulty_level', array('difficulty_level' => $difficulty));		
		$difficultylevelsname = [1 =>'High', 2=>'Medium', 3=>'Low'];
		if(isset($difficultylevelsname[$difficultycode])){
			return "<{$difficultylabel}>{$difficultylevelsname[$difficultycode]}</{$difficultylabel}>\n";
		}
	}
    private function get_cognitive_info($cognitive, $cognitivelabel){
		global $DB;
		$cognitivecode = $DB->get_field('local_questions_courses', 'cognitive_level', array('cognitive_level' => $cognitive));
        $cognitivecodename = "Level".$cognitivecode;
		if($cognitivecode){
			return "<{$cognitivelabel}>{$cognitivecodename}</{$cognitivelabel}>\n";
		}
	}
    private function get_source_info($sourceid, $sourcelable){
		global $DB;
  
		$sourcecode = $DB->get_field('local_question_sources', 'name', array('id' => $sourceid));
		if($sourcecode){
			return "<{$sourcelable}>{$sourcecode}</{$sourcelable}>\n";
		}
	}
	public function get_additional_information($questionobj, $questionxml){
		global $DB;
        $findnext = true;
        $goal_label  		  = $questionxml['#']['goal']['0']['#'] ? $questionxml['#']['goal']['0']['#'] : 0 ;
        $board_label 		  = $questionxml['#']['board']['0']['#'] ? $questionxml['#']['board']['0']['#'] : 0 ;
        $class_label 		  = $questionxml['#']['class']['0']['#'] ? $questionxml['#']['class']['0']['#'] :0 ;
        $subject_label  	  = $questionxml['#']['subject']['0']['#'] ? $questionxml['#']['subject']['0']['#'] : 0 ;
        $unit_label 		  = $questionxml['#']['unit']['0']['#'] ? $questionxml['#']['unit']['0']['#'] : 0 ;
        $chapter_label 		  = $questionxml['#']['chapter']['0']['#'] ? $questionxml['#']['chapter']['0']['#'] : 0 ;
        $topic_label 		  = $questionxml['#']['topics']['0']['#'] ? $questionxml['#']['topics']['0']['#'] : 0 ;
        $concept_label 		  = $questionxml['#']['concept']['0']['#'] ? $questionxml['#']['concept']['0']['#']: 0 ;
        $difficulty_label 	  = $questionxml['#']['difficultylevel']['0']['#'] ? $questionxml['#']['difficultylevel']['0']['#'] : 0 ;
        $cognitivelevel_label = $questionxml['#']['cognitivelevel']['0']['#'] ? $questionxml['#']['cognitivelevel']['0']['#'] : 0 ;
        $questionstatus_label = $questionxml['#']['questionstatus']['0']['#'] ? $questionxml['#']['questionstatus']['0']['#'] : 0 ;
        $source_label 		  = $questionxml['#']['source']['0']['#'] ? $questionxml['#']['source']['0']['#'] : 0 ;
        $gversion_label = substr($questionxml['#']['idnumber']['0']['#'],0,3);
        if($gversion_label == "V1_"){
           $sourcename =  $questionxml['#']['source']['0']['#'];
           $classname = $questionxml['#']['class']['0']['#'];
           $topicname =  $questionxml['#']['topics']['0']['#'];
           $subjectname = $questionxml['#']['subject']['0']['#'];
           $getdatasql ="SELECT * FROM {local_actual_hierarchy} WHERE 1=1 ";
           if($sourcename){
            $getdatasql .= " AND TRIM(source_name) = :sourcename ";
           }
           if($classname){
            $getdatasql .= " AND TRIM(course_class) = :classname ";
           }
           if($topicname){
            $getdatasql .= " AND TRIM(topic) = :topicname ";
           }
           if($subjectname){
            $getdatasql .= " AND TRIM(subject) = :subjectname";
           }
           $actualhierarchy = $DB->get_record_sql($getdatasql,['sourcename'=> trim($sourcename), 'classname' => trim($classname), 'topicname' => trim($topicname), 'subjectname' => trim($subjectname)]);

			$goal_label  		  = $actualhierarchy->act_goal ? $actualhierarchy->act_goal : $goal_label;
			$board_label 		  = $actualhierarchy->act_board ? $actualhierarchy->act_board : $board_label; 
			$class_label 		  = $actualhierarchy->act_class ? $actualhierarchy->act_class : $class_label ;
			$subject_label  	  = $actualhierarchy->act_subject ? $actualhierarchy->act_subject : $subject_label;
			$unit_label 		  = $actualhierarchy->act_unit ? $actualhierarchy->act_unit : $unit_label;
			$chapter_label 		  = $actualhierarchy->act_chapter ? $actualhierarchy->act_chapter: $chapter_label;
			$topic_label 		  = $actualhierarchy->act_topic ? $actualhierarchy->act_topic :$topic_label;
			$source_label 		  = $actualhierarchy->act_source ? $actualhierarchy->act_source :$source_label;
        }
       if(!empty($goal_label)){

			// $goalid = $DB->get_field('local_hierarchy', 'id', ['name' => $goal_label, 'depth' => 1]);
			$goalid = $DB->get_field_sql('SELECT id FROM {local_hierarchy} WHERE TRIM(name) LIKE :name AND depth = :depth AND parent = :parent', ['name' => trim($goal_label), 'depth' => 1, 'parent' => 0]);
			if($goalid){
				$questionobj->customfield_goal = $goalid;
			}else{
                $findnext = false;
                $questionobj->customfield_goal = 0;
            }
		}else{
			$questionobj->customfield_goal = 0;
            $findnext = false;
        }


        if(!empty($board_label) && $findnext){
           
            $boardsql ="SELECT hi.id from {local_hierarchy} as hi WHERE TRIM(hi.name) LIKE :bname AND  hi.parent = :parentid AND hi.depth = 2 ";
            $boardid = $DB->get_field_sql($boardsql, ['parentid' => $questionobj->customfield_goal, 'bname' => trim($board_label)]);
         
			if($boardid){
				$questionobj->customfield_board = $boardid;
			}else{
                $questionobj->customfield_board = 0;
                $findnext = false;  
            }
		}else{
			$questionobj->customfield_board = 0;
            $findnext = false;
        }

        if(!empty($class_label) && $findnext){
			// $classid = $DB->get_field('local_hierarchy', 'id', ['name' => $class_label,'parent' => $questionobj->customfield_board,'depth' => 3]);
			$classid = $DB->get_field_sql('SELECT id FROM {local_hierarchy} WHERE TRIM(name) like :name AND parent = :parent AND depth = :depth ', ['name' => trim($class_label),'parent' => $questionobj->customfield_board,'depth' => 3]);
			$classid = $classid ? $classid:0;
            $classsql ="SELECT hi.id,hi.name as fullname from {local_hierarchy} as hi WHERE hi.id = $classid AND hi.parent = $questionobj->customfield_board AND  hi.depth =3";
            $getclass = $DB->get_records_sql($classsql);
			if($getclass){
               
				$questionobj->customfield_class = $classid;
			}else{
				$questionobj->customfield_class = 0;
                $findnext = false;   
            }
		}else{
			$questionobj->customfield_class = 0;
            $findnext = false;
        }
        if(!empty($subject_label) && $findnext){
			// $courseid = $DB->get_field('local_subjects', 'courseid', ['name' => $subject_label,'classessid' => $questionobj->customfield_class]);
			$courseid = $DB->get_field_sql('SELECT courseid FROM {local_subjects} WHERE TRIM(name) LIKE :name AND classessid = :classessid ', ['name' => trim($subject_label),'classessid' => $questionobj->customfield_class]);
			$courseid = $courseid ? $courseid:0;
            $coursesql ="SELECT sub.courseid as id,sub.name as fullname 
                         FROM {local_subjects} AS sub 
                         WHERE sub.courseid= $courseid 
                         AND sub.classessid = $questionobj->customfield_class";
            $getcourse = $DB->get_records_sql($coursesql);
			if($getcourse){
				$questionobj->customfield_courses = $courseid;
			}else{
				$findnext =false;
				$questionobj->customfield_courses = 0;
			}
		}else{
			
            $findnext = false;
			$questionobj->customfield_courses = 0;
           
        }
        if(!empty($unit_label) && $findnext){
			// $uid = $DB->get_field('local_units', 'id', ['name' => $unit_label,'courseid' => $questionobj->customfield_courses]);  
			$uid = $DB->get_field_sql('SELECT id FROM {local_units} WHERE TRIM(name) LIKE :name AND courseid = :courseid ', ['name' => trim($unit_label),'courseid' => $questionobj->customfield_courses]);  
			if($uid){
				$questionobj->customfield_unit = $uid;
			}else{
				$findnext =false;
				$questionobj->customfield_unit = 0;
			}
		}else{
            $findnext = false;
			$questionobj->customfield_unit = 0;
        }
		if(!empty($chapter_label) && $findnext){     
			// $chpid = $DB->get_field_sql('local_chapters', 'id', ['name' => $chapter_label,'courseid' => $questionobj->customfield_courses, 'unitid' => $questionobj->customfield_unit]); 
             $chpid = $DB->get_field_sql('SELECT id FROM {local_chapters} WHERE TRIM(name) LIKE :name AND courseid = :courseid AND unitid = :unitid',['name' => trim($chapter_label),'courseid' => $questionobj->customfield_courses, 'unitid' => $questionobj->customfield_unit]);

			if($chpid){
				$questionobj->customfield_chapter = $chpid;
			}else{
				$findnext =false;
				$questionobj->customfield_chapter = 0;
			}
		}else{
            $findnext = false;
			$questionobj->customfield_chapter = 0;
        }
        if(!empty($topic_label)  && $findnext){
            // $tid = $DB->get_field('local_topics', 'id', ['name' => $topic_label,'courseid' => $questionobj->customfield_courses, 'unitid' => $uid, 'chapterid' => $questionobj->customfield_chapter]);
            $tid = $DB->get_field_sql('SELECT id FROM {local_topics} WHERE TRIM(name) LIKE :name AND courseid = :courseid AND unitid = :unitid AND chapterid = :chapterid ', ['name' => trim($topic_label),'courseid' => $questionobj->customfield_courses, 'unitid' => $uid, 'chapterid' => $questionobj->customfield_chapter]);
           if($tid){
				$questionobj->customfield_topics = $tid;
			}else{
				$findnext =false;
				$questionobj->customfield_topics = 0;
			}
		}else{
			 $findnext = false;
			$questionobj->customfield_topics = 0;
		}

		 if(!empty($concept_label)  && $findnext){
		 	  $cname =$concept_label;
              $conceptsql = "SELECT id FROM {local_concept} WHERE TRIM(name) LIKE :name AND courseid =:courseid AND unitid =:unitid AND chapterid =:chapterid AND topicid =:topicid  ";
              $params =[];
              $params['name'] = trim($concept_label);
              $params['courseid'] = $questionobj->customfield_courses;
              $params['unitid'] = $uid;
              $params['chapterid'] =$questionobj->customfield_chapter;
              $params['topicid'] = $questionobj->customfield_topics;
              $conceptid = $DB->get_record_sql($conceptsql, $params);
              $concid =$conceptid->id;
           if($concid){
				$questionobj->customfield_concept = $concid;
			}else{
				$findnext =false;
				$questionobj->customfield_concept = 0;
			}
		}else{
			 $findnext = false;
			$questionobj->customfield_concept = 0;
		}

        if(!empty($difficulty_label  || $difficulty_label == 0)){
            $difficultylevels = ['High' => 1, 'Medium' => 2, 'Low' => 3];
            if(isset($difficultylevels[trim($difficulty_label)])){
                $questionobj->customfield_difficultylevel = $difficultylevels[trim($difficulty_label)];
            }else{
				$questionobj->customfield_difficultylevel = 0;
			}
		}

        if(!empty($cognitivelevel_label) || $cognitivelevel_label == 0){
            $cognitivelevels = ['NA' => 1, 'Creating' => 2, 'Evaluating' => 3, 'Analysing' => 4, 'Applying' => 5, 'Understanding' => 6, 'Remembering' => 7];
			if(isset($cognitivelevels[trim($cognitivelevel_label)])){
                $questionobj->customfield_cognitivelevel = $cognitivelevels[trim($cognitivelevel_label)];
            }
			else{
				$questionobj->customfield_cognitivelevel = 0;
			}
		}
        if(!empty($source_label || $source_label == 0)){

			$sourceid = $DB->get_field('local_question_sources', 'id', ['name' => trim($source_label)]);
			if($sourceid){
				$questionobj->customfield_source = $sourceid;
			}else{
				$questionobj->customfield_source = 0;
			}
		}
		if(!empty($questionstatus_label || $questionstatus_label == 0)){
			
            $qstatuses = ['draft'=>'draft','readytoreview'=>'readytoreview','underreview'=>'underreview','reject'=>'reject','publish'=>'publish'];
			if(isset($qstatuses[trim($questionstatus_label)])){
			
                $questionobj->customfield_qstatus = $qstatuses[trim($questionstatus_label)];
            }
			else{
				$questionobj->customfield_qstatus = "draft";
			}
		}
		
		return $questionobj;
	}
}
