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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_masterdata;
use context_system;
use context_module;
use curl;
defined('MOODLE_INTERNAL') || die;
class courselib {
    public $parent = 0;
    public $course;
    public $courseformat;
    public $latestsection;

    public $oldcourseid;

    public $totalactivities;

    public function __construct($moodlecourse,$oldcourseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        $this->course = $moodlecourse;
        $this->oldcourseid = $oldcourseid;
        $this->totalactivities = 0;
        $this->latestsection = $DB->get_record('course_sections', ['section' => 0, 'course' => $this->course->id]);
        $this->courseformat = \core_courseformat\base::instance($moodlecourse);
    }
    public function process_mastercoursedata($child) {
        switch ($child->key_label) {
            case 'Chapter' :
            case 'Lesson' :
                $this->create_course_section($child);
            break;
            case 'Subjective Test' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'assign');
            break;
            case 'Flash Card' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'folder');
            break;
            case 'Topic' :
            case 'Live Class' :
            case 'Textual Contents' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'page');
            break;
            case 'Chapter Test' :
            case 'Practice Bundle' :
               $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'quiz');
            break;
            default :
            break;
        }
        if(!empty($child->children)) {
        	$currentparent = $this->parent;
            foreach($child->children AS $newchild){
            	$this->parent = $currentparent;
                $this->process_mastercoursedata($newchild);
            }
        }
    }
    public function create_course_section($structure) {
    	global $DB;
        if($structure->is_active) {
            $this->parent = $this->courseformat->create_new_section($this->parent);
            $section = $DB->get_record('course_sections', ['section' => $this->parent, 'course' => $this->course->id]);
            $this->latestsection = $section;
            course_update_section($section->course, $section, array('name' => $structure->name));
        }
    }

    public function create_course_module($data,$courseid,$latestsection,$module) {
    	global $DB,$OUTPUT,$CFG;

        // $section_num = $this->courseformat->create_new_section($this->parent);
        // $sectiondata = $DB->get_record('course_sections', ['section' => $section_num, 'course' => $this->course->id]);
        // course_update_section($sectiondata->course, $sectiondata, array('name' => $data->name));

        if ($CFG->debug !== DEBUG_DEVELOPER) {
            $refetchfromservice = false;
        } else {
            $refetchfromservice = true;
        }
        $api = (new api(['debug' => false]));
        $moduledata = new \stdClass();
        $moduledata->name = $data->name;
        $moduledata->modulename = $module;
        $moduledata->course =(int)$courseid;
        $moduledata->section = $latestsection;
        $moduledata->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $moduledata->visible = ((int)$data->is_active > 0) ? 1 : 0;
        if($module == 'quiz') {
            $moduledata->testtype = ($data->key_label == 'Chapter Test') ? 0 : 1;
            $moduledata->quizpassword = 0;
            $moduledata->preferredbehaviour = 'adaptive';
        } else if ($module == 'page') {
            $moduledata->pagetype = ($data->key_label == 'Live Class') ? 1 : 0;
        }
        $moduleinfo = create_module($moduledata);
        $this->totalactivities++;
        $noderesponse = $api->fetch_node_data($data->id, $this->course->idnumber, $refetchfromservice);
        $modulecontext = context_module::instance($moduleinfo->coursemodule);
        if($module == 'assign') {
            $subjectivetestrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'subjectivetest');
        }  else  if ($module == 'folder') {
            if($noderesponse){
                $noderesponse = json_decode($noderesponse);
                $mediacontenturl = $api->settings->mediacontenturl;
                $mediacontents = $noderesponse->response->content_docs_data;
                if($noderesponse->status == 'success' && !empty($mediacontents)) {
                    foreach($mediacontents AS $mcontent) {
                        $innermcontent = $mcontent->elearning_content;
                        if (!empty($innermcontent)) {
                            foreach($innermcontent AS $innercontent) {
                                $filerecord = [ 'component' => 'mod_folder', 
                                                'filearea' => 'content',
                                                'contextid' => $modulecontext->id,
                                                'itemid' => 0,
                                                'filename' => basename(implode("/", array_map("rawurlencode", explode("/", $innercontent->content)))), 
                                                'filepath' => '/'
                                            ];
                                $mediaurl = $mediacontenturl.implode("/", array_map("rawurlencode", explode("/", $innercontent->content)));
                                $content = $api->get($mediaurl, [], ['CURLOPT_HTTPHEADER' =>  []]);
                                $fs = get_file_storage();
                                $fs->create_file_from_string($filerecord, $content);
                            }
                        }
                    }
                } 
            }
        } else if ($module == 'page') {
            $pagerecord = $DB->get_record('page',['id'=>$moduleinfo->instance]);
            $intro = '';
            if ($moduledata->pagetype) {
                $liveclassrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'classroomdata');
                if (is_object($liveclassrepsonse) && isset($liveclassrepsonse->response->class_rooms) && !empty ($liveclassrepsonse->response->class_rooms)){
                    $content = '';
                    foreach ($liveclassrepsonse->response->class_rooms AS $classroom) {
                        $timestart = strtotime($classroom->start_time);
                        $timeend = $timestart + ($classroom->duration*60);//Minute to second conversion.
                        // Class Notes
                        if(!empty($classroom->chapter_notes)) {

                            $filerecord = [ 'component' => 'mod_page', 
                                'filearea' => 'content',
                                'contextid' => $modulecontext->id,
                                'itemid' => 0,
                                'filename' => basename(implode("/", array_map("rawurlencode", explode("/", $classroom->chapter_notes)))), 
                                'filepath' => '/'
                            ];
                            $chapter_notescontent = $api->get($classroom->chapter_notes, [], ['CURLOPT_HTTPHEADER' =>  []]);
                            $fs = get_file_storage();
                            $fs->create_file_from_string($filerecord, $chapter_notescontent);
                            $lessonnotes_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->chapter_notes)))));
                            $lessonnotesurl = $lessonnotes_url->out();

                        }
                        // Lesson Plans
                        if(!empty($classroom->lesson_plan)) {

                            $filerecord = [ 'component' => 'mod_page', 
                                'filearea' => 'content',
                                'contextid' => $modulecontext->id,
                                'itemid' => 0,
                                'filename' => basename(implode("/", array_map("rawurlencode", explode("/", $classroom->lesson_plan)))), 
                                'filepath' => '/'
                            ];
                          
                            $lessonplanscontent = $api->get($classroom->lesson_plan, [], ['CURLOPT_HTTPHEADER' =>  []]);
                            $fs = get_file_storage();
                            $fs->create_file_from_string($filerecord, $lessonplanscontent);

                            $lessonplan_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->lesson_plan)))));
                            $lessonplanurl = $lessonplan_url->out();

                        }

                        $liveclasscard = $OUTPUT->render_from_template('mod_zoom/zoom_card', ['title' => $classroom->contents, 'summary' => '', 'timestart' => $timestart, 'timeend' => $timeend,'classnotes'=>$lessonnotesurl,'lessonplan'=>$lessonplanurl]);
                        $introcontent = $liveclasscard;
                        $content .= $introcontent.'<br/><video controls="true">
                        <source src="'.$classroom->recording_url.'">'.$classroom->recording_url.'
                        </video> <br/>';
                        $intro .= $introcontent;
                        
                    }
                }
            }else{
                if($noderesponse){
                    $noderesponse = json_decode($noderesponse);
                    if($noderesponse->status == 'success' && !empty($noderesponse->response->content_docs_data[0]->elearning_content[0]->content)) {
                      $content = $noderesponse->response->content_docs_data[0]->elearning_content[0]->content;
                    } 
                }
            }
            $pagerecord->content = $content;
            $pagerecord->intro = $intro;
            $DB->update_record('page',$pagerecord);
        } else if ($module == 'quiz') {
            $adminuserid =(int) $DB->get_field('user','id',['username'=>'admin']);
            $quizobj = \mod_quiz\quiz_settings::create($moduleinfo->instance, $adminuserid);
            if ($noderesponse && $moduledata->testtype == 1) {
                $noderesponse = json_decode($noderesponse);
                if($noderesponse->status == 'success' && !empty($noderesponse->response->node_info->details)){
                    $this->proces_practice_bundle_topics($noderesponse->response->node_info->details,$quizobj);
                }
            // } else {
            //     $testresponse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'mcqtestdata');
            //     if (is_object($testresponse) && isset($testresponse->response->exams) && !empty ($testresponse->response->exams)){
            //         foreach ($testresponse->response->exams AS $texamdata) {
            //             if($texamdata->exams_id->use_qn_pool) {
            //                 // MCQ Exam Pool
            //                 $questionpoolresponse = $api->fetchdata((int)$texamdata->exams_id->id, $this->course->idnumber, $refetchfromservice,'mcqexampool');
            //                 if (is_object($questionpoolresponse) && isset($questionpoolresponse->response->pool_list) && !empty ($questionpoolresponse->response->pool_list)){
            //                     foreach ($questionpoolresponse->response->pool_list AS $poollistdata) {
            //                         $pooldataquestions = explode(',',$poollistdata->questions);
            //                         $questions =[];
            //                         foreach ($pooldataquestions AS $pooldataquestion) {
            //                             $questions[] ='V1_'.trim($pooldataquestion); 
            //                         }
            //                         if(COUNT($questions) > 0) {
            //                             list($sql,$params) = $DB->get_in_or_equal($questions);
            //                             $querysql = "SELECT MAX(qv.questionid) AS questionid FROM {question_versions} qv 
            //                             JOIN {question_bank_entries} qbe ON qv.questionbankentryid  = qbe.id 
            //                             WHERE qbe.idnumber $sql";
            //                             $questionids= $DB->get_records_sql($querysql,$params); 
            //                             if(COUNT($questionids) > 0) {
            //                                 foreach ($questionids AS $question) {
            //                                     if((int)$question->questionid > 0) {
            //                                         quiz_add_quiz_question((int)$question->questionid, $quizobj->get_quiz());
            //                                     }
                                                
            //                                 }
            //                             }
            //                         }
            //                     }
            //                 }
            //             }  else {
            //                 // Test Center Topic Split Info
                
            //                 $topicsplitresponse = $api->fetchdata((int)$texamdata->exams_id->id, $this->course->idnumber, $refetchfromservice,'topicsplit');
            //                 $source = $topicsplitresponse->response->exam->source;
            //                 if($source) {
            //                     $source_name = $DB->get_field('test_centre_source','source_name',['id'=>$source]);
            //                 }
            //                 $split_by = $topicsplitresponse->response->exam->split_by;
            //                 $no_of_questions = $topicsplitresponse->response->exam->no_of_questions;
            //                 if (is_object($topicsplitresponse) && isset($topicsplitresponse->response->topic_split) && !empty ($topicsplitresponse->response->topic_split)){
            //                     $randomqnum = 0;
            //                     if($split_by == 1) {
            //                         $randomqnum = round(($no_of_questions)/COUNT($topicsplitresponse->response->topic_split));
            //                     }
            //                     foreach ($topicsplitresponse->response->topic_split AS $topic_split) {
            //                         if($topic_split->is_active) {
            //                             require_once($CFG->dirroot.'/local/questions/lib.php');
            //                             if($split_by == 2) {
            //                                 $randomqnum = $topic_split->percentage;
            //                             }
            //                             $hierarchyrecord = $DB->get_record_sql('SELECT * FROM {local_actual_hierarchy} WHERE source_name =:tssourcename AND course_class =:tscourseclass AND subject =:tssubject AND topic =:tstopic  ORDER BY ID DESC LIMIT 1',
            //                             [
            //                             'tssourcename'=>$source_name,
            //                             'tscourseclass'=>$topic_split->exam_class->label,
            //                             'tssubject'=>$topic_split->subject->label,
            //                             'tstopic'=>$topic_split->topic->label,]);

            //                             $goalid =(int) (new \local_masterdata\questionslib())->get_goalid($hierarchyrecord->act_goal,0);

            //                             $boardid =(int) (new \local_masterdata\questionslib())->get_boardid($hierarchyrecord->act_board,$goalid);

            //                             $classid =(int) (new \local_masterdata\questionslib())->get_classid($hierarchyrecord->act_class,$boardid);

            //                             $subjectid =(int) (new \local_masterdata\questionslib())->get_subjectid($hierarchyrecord->act_subject,$classid);

            //                             $unitid =(int) (new \local_masterdata\questionslib())->get_unitid($hierarchyrecord->act_unit,$subjectid);

            //                             $chapterid =(int) (new \local_masterdata\questionslib())->get_chapterid($hierarchyrecord->act_chapter,$unitid);

            //                             $topicid =(int) (new \local_masterdata\questionslib())->get_topicid($hierarchyrecord->act_topic,$chapterid);

            //                             $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
            //                             $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
            //                             $systemcontext = \context_system::instance();
            //                             $categoryid = $pcategory.','.$systemcontext->id;
            //                             local_questions_quiz_add_random_questions($quiz, 0, $categoryid, $randomqnum, 0, [], $goalid, $boardid, $classid, $courseid, $topicid,$chapterid,$unitid,0);

            //                         }
            //                     }
            //                 }
            //             }
            //             // MCQ Attempt list.
            //             $attemptlistresponse = $api->fetchdata((int)$texamdata->exams_id->id, $this->course->idnumber, $refetchfromservice,'mcqattemptlist');
                        
            //             if (is_object($attemptlistresponse) && isset($attemptlistresponse->response->exam_attempts) && !empty ($attemptlistresponse->response->exam_attempts)){
            //                 foreach ($attemptlistresponse->response->exam_attempts AS $exam_attempt) {
            //                     // MCQ Attempt info.
            //                     $questionattemptsdata = new \stdClass();
                               
            //                     $questionattemptsdata->examid = (int)$texamdata->exams_id->id;
            //                     $questionattemptsdata->cmid = (int)$moduleinfo->coursemodule;
            //                     $questionattemptsdata->quizid = (int)$moduleinfo->instance;
            //                     $questionattemptsdata->attemptid = (int)$exam_attempt->attempt_id;
            //                     $questionattemptsdata->studentid = ((int)$exam_attempt->student_id) ? (int)$exam_attempt->student_id : 0;

            //                     $attemptinforesponse = $api->fetchdata((int)$exam_attempt->attempt_id, $this->course->idnumber, $refetchfromservice,'mcqattemptinfo');
            //                     $attemptdata = $attemptinforesponse->response->attempt_details;
            //                     if (is_object($attemptinforesponse) && isset($attemptdata->student_answer) && !empty ($attemptdata->student_answer)){
            //                         foreach ($attemptdata->student_answer AS $student_answer) {
            //                             $answeroptions= $DB->get_records_sql('SELECT id,answer_option,is_correct FROM {test_centre_answeroptions} WHERE question_id =:questionid',['questionid'=>(int)$student_answer->question_id]);
            //                             if(!empty($answeroptions)){
            //                                 $student_answer->answeroptions = (object)array_values($answeroptions);
            //                             } else {
            //                                 $student_answer->answeroptions = [];
            //                             }
            //                         }
                                    
            //                     }
            //                     $mdl_userid = (int)$DB->get_field_sql('SELECT id FROm {user}
            //                     WHERE  idnumber=:studentid',['studentid'=>(int)$exam_attempt->student_id]);
            //                     $questionattemptsdata->userid =($mdl_userid) ? $mdl_userid : 0;
            //                     $questionattemptsdata->attemptsinfo =($attemptdata->student_answer) ? json_encode($attemptdata->student_answer) : 'No Data';
            //                     $questionattemptsdata->attempt_start_date =($attemptdata->attempt_start_date) ? $attemptdata->attempt_start_date : null; 
            //                     $questionattemptsdata->last_try_date =($attemptdata->last_try_date) ? $attemptdata->last_try_date: null; 
            //                     $questionattemptsdata->time_taken =($attemptdata->time_taken) ? $attemptdata->time_taken : null; 
            //                     $questionattemptsdata->difficulty_level =($attemptdata->difficulty_level) ? $attemptdata->difficulty_level : null ; 
            //                     $questionattemptsdata->mark =($attemptdata->mark) ? $attemptdata->mark :0; 
            //                     $questionattemptsdata->viewed_questions =($attemptdata->viewed_questions) ? $attemptdata->viewed_questions : null; 
            //                     $questionattemptsdata->questions_under_review =($attemptdata->questions_under_review) ? $attemptdata->questions_under_revie : null; 
            //                     $questionattemptsdata->is_exam_finished =$attemptdata->is_exam_finished; 
            //                     $questionattemptsdata->exam_mode =($attemptdata->exam_mode) ? $attemptdata->exam_mode : 0; 
            //                     $questionattemptsdata->no_of_qns =($attemptdata->no_of_qns)? $attemptdata->no_of_qns : 0; 
            //                     $questionattemptsdata->is_exam_paused =($attemptdata->is_exam_paused) ?$attemptdata->is_exam_paused : 0; 
            //                     $questionattemptsdata->is_module_wise_test =($attemptdata->is_module_wise_test) ? $attemptdata->is_module_wise_test : 0; 
            //                     $questionattemptsdata->total_mark =($attemptdata->total_mark) ? $attemptdata->total_mark : 0; 
            //                     $questionattemptsdata->timecreated =time(); 
            //                     $questionattemptsdata->usercreated =$adminuserid; 
            //                     $DB->insert_record('local_question_attempts',$questionattemptsdata);
            //                 }
            //             }
            //         }
            //     }
            }
        }
        mtrace('<b>'.ucfirst($module).'</b> module having name <b>'.$data->name.'</b> created successfully'.'</br>');
    }

    public function proces_practice_bundle_topics($node_info_details,$quizobj){
        global $DB;
        $migratequestions = new \local_masterdata\questionslib();
        foreach ($node_info_details AS $detail) {
            $hierarchy = explode('/', $detail->topic_path);
            $questionids = $migratequestions->get_hierary_questions($hierarchy);
            if(COUNT($questionids) > 0) {
                foreach ($questionids AS $question) {
                    if((int)$question->id > 0) {
                        quiz_add_quiz_question((int)$question->id, $quizobj->get_quiz());
                    }
                    
                }
                \mod_quiz\quiz_settings::create((int)$quizobj->get_quiz()->id)->get_grade_calculator()->recompute_quiz_sumgrades();
            }
        }
    }

    public  function create_data_log($statusmessage){
        global $DB,$USER;
        $logrecord = new \stdClass();
        $logrecord->courseid = $this->course->id;
        $logrecord->activitiescount = $this->totalactivities;
        $logrecord->oldcourseid =  $this->oldcourseid;
        $logrecord->status =  ($statusmessage == 1) ? 0 : 1;
        $logrecord->status_message =  ($statusmessage == 1) ? null : $statusmessage;
        $logrecord->timecreated =time();
        $logrecord->usercreated = $USER->id;
        $DB->insert_record('local_masterdata_log',$logrecord);


    }

}
