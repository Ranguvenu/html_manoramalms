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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
defined('MOODLE_INTERNAL') || die;
class batchcourselib extends courselib {
    public $parentcourseinfo;
    public $batchid;
	public function __construct($moodlecourse, $oldcourseid, $parentcourseid) {
		$this->parentcourseinfo = get_course($parentcourseid);
        $this->batchid = str_replace('BAT_', '', $moodlecourse->idnumber);
		parent::__construct($moodlecourse, $oldcourseid);
	}
	public function create_course_module($data, $courseid, $latestsection, $module) {
        global $DB,$CFG,$OUTPUT;
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
        $moduledata->name = $data->key_label.' : '.$data->name;
        $moduledata->modulename = $module;
        $moduledata->course =(int)$courseid;
        $moduledata->section = $latestsection;
        $moduledata->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $moduledata->visible = 1;
        if($module == 'quiz') {
            $moduledata->testtype = ($data->key_label == 'Chapter Test') ? 0 : 1;
            $moduledata->quizpassword = 0;
            $moduledata->preferredbehaviour = 'adaptive';
            $moduledata->questionsperpage = 1;
        } else if ($module == 'page') {
            $moduledata->pagetype = ($data->key_label == 'Live Class') ? 1 : 0;
        }
        $moduledata->visible = ((int)$data->is_active > 0) ? 1 : 0;
        
        $noderesponse = $api->fetch_node_data((int)$data->id, $this->parentcourseinfo->idnumber, $refetchfromservice);
        if($module == 'assign') {
            $moduleinfo = create_module($moduledata);
            $this->totalactivities++;

            $subjectivetestrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'subjectivetest',(int)$this->batchid);
        }  else  if ($module == 'folder') {
            $moduleinfo = create_module($moduledata);
            $modulecontext = context_module::instance($moduleinfo->coursemodule);
            $this->totalactivities++;
            if($noderesponse){
                $noderesponse = json_decode($noderesponse);
                $mediacontenturl = $api->settings->mediacontenturl;
                $i=1;
                foreach($noderesponse->response->content_docs_data as $mediacontents){
                    $mediacontenturl = $api->settings->mediacontenturl;
                    $intro = '';
                    if(!empty($mediacontents->elearning_content[0]->content)){
                        $fileinfo = $DB->get_record('files',['component' => 'mod_folder', 
                        'filearea' => 'content',
                        'contextid' => $modulecontext->id,
                        'itemid' => 0,
                        'filename' => $mediacontents->learning_objective[0]->name,
                        'filepath' => '/'
                        ]);
                        $filename = ($fileinfo->id > 0) ? $mediacontents->learning_objective[0]->name.'-'.$i: $mediacontents->learning_objective[0]->name;
                        $filerecord = [ 'component' => 'mod_folder', 
                        'filearea' => 'content',
                        'contextid' => $modulecontext->id,
                        'itemid' => 0,
                        'filename' => $filename,
                        'filepath' => '/'
                        ];
                        $mediaurl = $mediacontenturl.implode("/", array_map("rawurlencode", explode("/", $mediacontents->elearning_content[0]->content)));
                        $content = $api->get($mediaurl, [], ['CURLOPT_HTTPHEADER' =>  []]);
                        $fs = get_file_storage();
                        $fs->create_file_from_string($filerecord, $content);
                        $i++;
                    }
                }
            }
        } else if ($module == 'page') {
            $moduleinfo = create_module($moduledata);
            $modulecontext = context_module::instance($moduleinfo->coursemodule);
            $this->totalactivities++;
            $pagerecord = $DB->get_record('page',['id'=>$moduleinfo->instance]);
            $intro = '';
            if ($moduledata->pagetype) {
                $liveclassrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'classroomdata',(int)$this->batchid);
                if (is_object($liveclassrepsonse) && isset($liveclassrepsonse->response->class_rooms) && !empty ($liveclassrepsonse->response->class_rooms)){
                    $content = '';
                    foreach ($liveclassrepsonse->response->class_rooms AS $classroom) {
                        if($classroom->is_active){
                            $timestart = strtotime($classroom->start_time);
                            $timeend = strtotime($classroom->end_time);
                            // $timeend = $timestart + ($classroom->duration*60);//Minute to second conversion.
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
                            $hassubtopic = ($classroom->subtopic) ? true :false;
                            $liveclasscard = $OUTPUT->render_from_template('mod_zoom/zoom_card', [
                                'title' => $classroom->contents, 
                                'hassubtopic'=>$hassubtopic,
                                'subtopic' => $classroom->subtopic,
                                'summary' => '', 
                                'timestart' => $timestart, 
                                'timeend' => $timeend,
                                'classnotes'=>$lessonnotesurl,
                                'lessonplan'=>$lessonplanurl
                                ]);
                            $introcontent = $liveclasscard;
                            $content .= $introcontent.'<br/><video controls="true">
                            <source src="'.$classroom->recording_url.'">'.$classroom->recording_url.'
                            </video> <br/>';
                            $intro .= $introcontent;
                        }
                        
                    }
                }
            }else{
                // if($noderesponse){
                //     $noderesponse = json_decode($noderesponse);
                //     if($noderesponse->status == 'success' && !empty($noderesponse->response->content_docs_data[0]->elearning_content[0]->content)) {
                //       $content = $noderesponse->response->content_docs_data[0]->elearning_content[0]->content;
                //     } 
                // }
                $content = '';
                if($noderesponse){
                    $noderesponse = json_decode($noderesponse);
                    $this->totalactivities++;
                    foreach($noderesponse->response->content_docs_data as $videocontent){
                        $pagerecord = $DB->get_record('page',['id'=>$moduleinfo->instance]);
                        $intro = '';
                        if(!empty($videocontent->elearning_content[0]->content)){
                            $content .= '<div class ="w-100"><iframe  class ="w-100 video-frame" src="'.$videocontent->elearning_content[0]->content.'" title="description"></iframe>'.'</div>';
                            $content .='<div class ="conceptvideocard card mb-3"><div class="card-header"><h5 class="mb-0"> Concepts Covered </h5></div><div class ="card-body"><p>'.$videocontent->learning_objective[0]->name.'</p> </div></div>';
                        }
                    }
                  
                }
            }
            $pagerecord->content = $content;
            $pagerecord->intro = $intro;
            $pagerecord->introformat = 1;

            $DB->update_record('page',$pagerecord);
        } else if ($module == 'quiz') {
            $adminuserid =(int) $DB->get_field('user','id',['username'=>'admin']);
            if ($noderesponse && $moduledata->testtype == 1) {
                $moduleinfo = create_module($moduledata);
                $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
                $quiz->reviewcorrectness = 69632;
                $quiz->reviewmarks = 69632;
                $quiz->reviewspecificfeedback = 69632;
                $quiz->reviewgeneralfeedback = 69632;
                $quiz->reviewrightanswer= 69632;
                $quiz-> reviewoverallfeedback = 69632;
                $quiz->attemptimmediately = 1;
                $quiz->correctnessimmediately = 1;
                $quiz->marksimmediately = 1;
                $quiz->specificfeedbackimmediately = 1;
                $quiz->generalfeedbackimmediately = 1;
                $quiz->rightanswerimmediately = 1;
                $quiz->overallfeedbackimmediately = 1;
                $quiz->attemptopen = 1;
                $quiz->correctnessopen = 1;
                $quiz->marksopen = 1;
                $quiz->specificfeedbackopen = 1;
                $quiz->generalfeedbackopen = 1;
                $quiz->rightansweropen = 1;
                $quiz->overallfeedbackopen = 1;
                $quiz->attemptclosed = 1;
                $quiz->correctnessclosed = 1;
                $quiz->marksclosed = 1;
                $quiz->specificfeedbackclosed = 1;
                $quiz->generalfeedbackclosed = 1;
                $quiz->rightanswerclosed = 1;
                $quiz->overallfeedbackclosed = 1;
                $DB->update_record('quiz',$quiz);
                $quizobj = \mod_quiz\quiz_settings::create($moduleinfo->instance, $adminuserid);
                $noderesponse = json_decode($noderesponse);
                if($noderesponse->status == 'success' && !empty($noderesponse->response->node_info->details)){
                    $this->proces_practice_bundle_topics($noderesponse->response->node_info->details,$quizobj);
                }
            } else {
                $testresponse = $api->fetchdata((int)$data->id, $this->course->idnumber, $refetchfromservice,'mcqtestdata',(int)$this->batchid);
                if (is_object($testresponse) && isset($testresponse->response->exams) && !empty ($testresponse->response->exams)){
                    foreach ($testresponse->response->exams AS $texamdata) {
                        if($texamdata->is_active){
                            $examinforecord =$DB->get_record('test_centre_exam',['id'=>(int)$texamdata->exams_id->id]);
                            $moduledata->name = ($examinforecord->exam_name) ? $data->key_label.' : '.$examinforecord->exam_name : $data->key_label.' : '.$data->name;
                            $moduledata->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
                            $moduledata->quizpassword = 0;
                            $moduledata->preferredbehaviour = 'deferredfeedback';
                            $moduledata->questionsperpage = 1;
                            $moduledata->attempts = 1;
                        
                            $moduleinfo = create_module($moduledata);
                            $quizobj = \mod_quiz\quiz_settings::create($moduleinfo->instance, $adminuserid);
                            $this->totalactivities++;
                            if($texamdata->exams_id->use_qn_pool) {
                                // MCQ Exam Pool
                                $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
                                $quiz->timeopen =($examinforecord->start_date)? strtotime(str_replace("T"," ",$examinforecord->start_date)) : 0;
                                $quiz->timeclose =  $examinforecord->end_date ? strtotime(str_replace("T"," ",$examinforecord->end_date)) : 0;
                                $quiz->timelimit =$examinforecord->time_limit ? $api->get_seconds($examinforecord->time_limit):0;
                                $grade = ($examinforecord->mark)? $examinforecord->mark : 0;
                                if($examinforecord->instructions) {
                                    $quiz->intro =  $examinforecord->instructions;
                                    $quiz->introformat = 1;
                                }
                                $quiz->reviewcorrectness = 69632;
                                $quiz->reviewmarks = 69632;
                                $quiz->reviewspecificfeedback = 69632;
                                $quiz->reviewgeneralfeedback = 69632;
                                $quiz->reviewrightanswer= 69632;
                                $quiz-> reviewoverallfeedback = 69632;
                                $quiz->attemptimmediately = 1;
                                $quiz->correctnessimmediately = 1;
                                $quiz->marksimmediately = 1;
                                $quiz->specificfeedbackimmediately = 1;
                                $quiz->generalfeedbackimmediately = 1;
                                $quiz->rightanswerimmediately = 1;
                                $quiz->overallfeedbackimmediately = 1;
                                $quiz->attemptopen = 1;
                                $quiz->correctnessopen = 1;
                                $quiz->marksopen = 1;
                                $quiz->specificfeedbackopen = 1;
                                $quiz->generalfeedbackopen = 1;
                                $quiz->rightansweropen = 1;
                                $quiz->overallfeedbackopen = 1;
                                $quiz->attemptclosed = 1;
                                $quiz->correctnessclosed = 1;
                                $quiz->marksclosed = 1;
                                $quiz->specificfeedbackclosed = 1;
                                $quiz->generalfeedbackclosed = 1;
                                $quiz->rightanswerclosed = 1;
                                $quiz->overallfeedbackclosed = 1;
                                $DB->update_record('quiz',$quiz);
                                
                                $questionpoolresponse = $api->fetchdata((int)$texamdata->exams_id->id, $this->course->idnumber, $refetchfromservice,'mcqexampool');
                                if (is_object($questionpoolresponse) && isset($questionpoolresponse->response->pool_list) && !empty ($questionpoolresponse->response->pool_list)){
                                    foreach ($questionpoolresponse->response->pool_list AS $poollistdata) {
                                        $pooldataquestions = explode(',',$poollistdata->questions);
                                        $questions =[];
                                        foreach ($pooldataquestions AS $pooldataquestion) {
                                            $questions[] ='V1_'.trim($pooldataquestion); 
                                        }
                                        if(COUNT($questions) > 0) {
                                            list($sql,$params) = $DB->get_in_or_equal($questions);
                                            $querysql = "SELECT MAX(qv.questionid) AS questionid FROM {question_versions} qv 
                                            JOIN {question_bank_entries} qbe ON qv.questionbankentryid  = qbe.id 
                                            WHERE qbe.idnumber $sql GROUP BY qv.questionbankentryid";
                                            $questionids= $DB->get_records_sql($querysql,$params); 
                                            if(COUNT($questionids) > 0) {
                                                foreach ($questionids AS $question) {
                                                    if((int)$question->questionid > 0) {
                                                        quiz_add_quiz_question((int)$question->questionid, $quizobj->get_quiz());
                                                    }
                                                }
                                                \mod_quiz\quiz_settings::create($moduleinfo->instance)->get_grade_calculator()->recompute_quiz_sumgrades();
                                                 \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->update_quiz_maximum_grade($grade);
                                            }
                                        }
                                    }
                                }
                            }  else {
                                // Test Center Topic Split Info
                                $topicsplitresponse = $api->fetchdata((int)$texamdata->exams_id->id, $this->course->idnumber, $refetchfromservice,'topicsplit');
                                $source = $topicsplitresponse->response->exam->source;
                                if($source) {
                                    $source_name = $DB->get_field('test_centre_source','source_name',['id'=>$source]);
                                }
                                $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
                                $quiz->timeopen = strtotime(str_replace("T"," ",$topicsplitresponse->response->exam->start_date));
                                $quiz->timeclose =  strtotime(str_replace("T"," ",$topicsplitresponse->response->exam->end_date));
                                $quiz->timelimit = $api->get_seconds($topicsplitresponse->response->exam->time_limit);
                                $grade = ($topicsplitresponse->response->exam->mark)? $topicsplitresponse->response->exam->mark : 0;
                                if($topicsplitresponse->response->exam->instructions) {
                                    $quiz->intro = $topicsplitresponse->response->exam->instructions;
                                    $quiz->introformat = 1;
                                }
                                $quiz->reviewcorrectness = 69632;
                                $quiz->reviewmarks = 69632;
                                $quiz->reviewspecificfeedback = 69632;
                                $quiz->reviewgeneralfeedback = 69632;
                                $quiz->reviewrightanswer= 69632;
                                $quiz-> reviewoverallfeedback = 69632;
                                $quiz->attemptimmediately = 1;
                                $quiz->correctnessimmediately = 1;
                                $quiz->marksimmediately = 1;
                                $quiz->specificfeedbackimmediately = 1;
                                $quiz->generalfeedbackimmediately = 1;
                                $quiz->rightanswerimmediately = 1;
                                $quiz->overallfeedbackimmediately = 1;
                                $quiz->attemptopen = 1;
                                $quiz->correctnessopen = 1;
                                $quiz->marksopen = 1;
                                $quiz->specificfeedbackopen = 1;
                                $quiz->generalfeedbackopen = 1;
                                $quiz->rightansweropen = 1;
                                $quiz->overallfeedbackopen = 1;
                                $quiz->attemptclosed = 1;
                                $quiz->correctnessclosed = 1;
                                $quiz->marksclosed = 1;
                                $quiz->specificfeedbackclosed = 1;
                                $quiz->generalfeedbackclosed = 1;
                                $quiz->rightanswerclosed = 1;
                                $quiz->overallfeedbackclosed = 1;
                                $DB->update_record('quiz',$quiz);
                                $split_by = $topicsplitresponse->response->exam->split_by;
                                $no_of_questions = $topicsplitresponse->response->exam->no_of_questions;
                                if (is_object($topicsplitresponse) && isset($topicsplitresponse->response->topic_split) && !empty ($topicsplitresponse->response->topic_split)){
                                    $randomqnum = 0;
                                    if($split_by == 1) {
                                        $randomqnum = round(($no_of_questions)/COUNT($topicsplitresponse->response->topic_split));
                                    }
                                    foreach ($topicsplitresponse->response->topic_split AS $topic_split) {
                                        if($topic_split->is_active) {
                                            require_once($CFG->dirroot.'/local/questions/lib.php');
                                            if($split_by == 2) {
                                                $randomqnum = $topic_split->percentage;
                                            }
                                            $hierarchyrecord = $DB->get_record_sql('SELECT * FROM {local_actual_hierarchy} WHERE source_name =:tssourcename AND course_class =:tscourseclass AND subject =:tssubject AND topic =:tstopic  ORDER BY ID DESC LIMIT 1',
                                            [
                                            'tssourcename'=>$source_name,
                                            'tscourseclass'=>$topic_split->exam_class->label,
                                            'tssubject'=>$topic_split->subject->label,
                                            'tstopic'=>$topic_split->topic->label,]);

                                            $goalid =(int) (new \local_masterdata\questionslib())->get_goalid($hierarchyrecord->act_goal,0);

                                            $boardid =(int) (new \local_masterdata\questionslib())->get_boardid($hierarchyrecord->act_board,$goalid);

                                            $classid =(int) (new \local_masterdata\questionslib())->get_classid($hierarchyrecord->act_class,$boardid);

                                            $subjectid =(int) (new \local_masterdata\questionslib())->get_subjectid($hierarchyrecord->act_subject,$classid);

                                            $unitid =(int) (new \local_masterdata\questionslib())->get_unitid($hierarchyrecord->act_unit,$subjectid);

                                            $chapterid =(int) (new \local_masterdata\questionslib())->get_chapterid($hierarchyrecord->act_chapter,$unitid);

                                            $topicid =(int) (new \local_masterdata\questionslib())->get_topicid($hierarchyrecord->act_topic,$chapterid);
                                            $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
                                            $systemcontext = \context_system::instance();
                                            $categoryid = $pcategory.','.$systemcontext->id;
                                            local_questions_quiz_add_random_questions($quiz, 0, $categoryid, $randomqnum, 0, [], $goalid, $boardid, $classid,$subjectid, $unitid,$chapterid,$topicid,0);
                                            \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
                                             \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->update_quiz_maximum_grade($grade);

                                        }
                                    }
                                }
                            }
                            // MCQ Attempt list.
                            $allattempts = $api->total_page_attempt_list((int)$texamdata->exams_id->id);
                            if (COUNT($allattempts) > 0){
                                foreach ($allattempts AS $exam_attempt) {
                                    // MCQ Attempt info.
                                    $questionattemptsdata = new \stdClass();
                                    $questionattemptsdata->examid = (int)$texamdata->exams_id->id;
                                    $questionattemptsdata->cmid = (int)$moduleinfo->coursemodule;
                                    $questionattemptsdata->quizid = (int)$moduleinfo->instance;
                                    $questionattemptsdata->attemptid = (int)$exam_attempt->attempt_id;
                                    $questionattemptsdata->studentid = ((int)$exam_attempt->student_id) ? (int)$exam_attempt->student_id : 0;

                                    $attemptinforesponse = $api->fetchdata((int)$exam_attempt->attempt_id, $this->course->idnumber, $refetchfromservice,'mcqattemptinfo');

                                    $attemptdata = $attemptinforesponse->response->attempt_details;
                                    $addedquestions = [];
                                    $answeroptions = [];
                                    $studentattemptdata = [];
                                    if (is_object($attemptinforesponse) && isset($attemptdata->student_answer) && !empty ($attemptdata->student_answer)){
                                        foreach ($attemptdata->student_answer AS $student_answer) {
                                            $questiondetails = $DB->get_record('test_centre_question',['id'=>(int)$student_answer->question_id]);
                                            $addedquestions[(int)$student_answer->question_id] = true;
                                            $answeroptions= $DB->get_records_sql('SELECT id,answer_option,is_correct FROM {test_centre_answeroptions} WHERE question_id =:questionid',['questionid'=>(int)$student_answer->question_id]);
                                            if(!empty($answeroptions)){
                                                $studentattemptdata[(int)$student_answer->question_id] = ['questiondetails' => $questiondetails, 'attemptinfo' => $student_answer, 'answeroptions' => (object)array_values($answeroptions)];
                                            } else {
                                                $studentattemptdata[(int)$student_answer->question_id] = [];
                                            }
                                        }
                                    }
                                    $questions = $attemptdata->question_paper->questions;
                                    foreach(explode(',', $questions) as $questionid) {
                                        if (!isset($addedquestions[$questionid])) {
                                            $questiondetails = $DB->get_record('test_centre_question',['id'=>(int)$questionid]);
                                            $answeroptions= $DB->get_records_sql('SELECT id,answer_option,is_correct FROM {test_centre_answeroptions} WHERE question_id =:questionid',['questionid'=>(int)$questionid]);
                                            if(!empty($answeroptions)){
                                                $studentattemptdata[$questionid] = ['questiondetails' => $questiondetails, 'answeroptions' => (object)array_values($answeroptions)];
                                            } else {
                                                $studentattemptdata[$questionid] = [];
                                            }
                                        } 
                                    }
                                    $mdl_userid = (int)$DB->get_field_sql('SELECT id FROm {user}
                                    WHERE  idnumber=:studentid',['studentid'=>(int)$exam_attempt->student_id]);
                                    $questionattemptsdata->userid =($mdl_userid) ? $mdl_userid : 0;
                                    $questionattemptsdata->attemptsinfo =($studentattemptdata) ? json_encode($studentattemptdata) : 'No Data';
                                    $questionattemptsdata->attempt_start_date =($attemptdata->attempt_start_date) ? $attemptdata->attempt_start_date : null; 
                                    $questionattemptsdata->last_try_date =($attemptdata->last_try_date) ? $attemptdata->last_try_date: null; 
                                    $questionattemptsdata->timetaken =($attemptdata->time_taken) ? $attemptdata->time_taken : null; 
                                    $questionattemptsdata->difficulty_level =($attemptdata->difficulty_level) ? $attemptdata->difficulty_level : null ; 
                                    $questionattemptsdata->mark =($attemptdata->mark) ? $attemptdata->mark :0; 
                                    $questionattemptsdata->viewed_questions =($attemptdata->viewed_questions) ? $attemptdata->viewed_questions : null; 
                                    $questionattemptsdata->questions_under_review =($attemptdata->questions_under_review) ? $attemptdata->questions_under_revie : null; 
                                    $questionattemptsdata->is_exam_finished =$attemptdata->is_exam_finished; 
                                    $questionattemptsdata->exam_mode =($attemptdata->exam_mode) ? $attemptdata->exam_mode : 0; 
                                    $questionattemptsdata->no_of_qns =($attemptdata->no_of_qns)? $attemptdata->no_of_qns : 0; 
                                    $questionattemptsdata->is_exam_paused =($attemptdata->is_exam_paused) ?$attemptdata->is_exam_paused : 0; 
                                    $questionattemptsdata->is_module_wise_test =($attemptdata->is_module_wise_test) ? $attemptdata->is_module_wise_test : 0; 
                                    $questionattemptsdata->total_mark =($attemptdata->total_mark) ? $attemptdata->total_mark : 0; 
                                    $questionattemptsdata->timecreated =time(); 
                                    $questionattemptsdata->usercreated =$adminuserid; 
                                    $DB->insert_record('local_question_attempts',$questionattemptsdata);
                                }
                            }

                        }
                    }
                }
            }
        }
        mtrace('<b>'.ucfirst($module).'</b> module having name <b>'.$data->name.'</b> created successfully'.'</br>');
    }
}
