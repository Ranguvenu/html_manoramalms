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
use stdClass;
defined('MOODLE_INTERNAL') || die;
use context_course;
global $CFG;
require_once("{$CFG->libdir}/filelib.php");
class api extends \curl {
    public $settings;
    public function __construct($options = []) {
        $this->settings = get_config('local_masterdata');
        parent::__construct($options); 
    }
    // Returns the course ids in an array.
    public function get_list_of_courses() {
        return [72];
    }
    public function get_course_content($oldcourseid, $refetchfromservice = false) {
        global $DB, $CFG;
        $return = true;
        try {
            $existingcourse = $DB->get_record('course', ['idnumber' => $oldcourseid]);
            if (!empty($existingcourse)) {
                if($existingcourse->format !='flexsections'){

                    throw new \Exception(get_string('invalidcourseformat', 'local_masterdata'), 1);
                }
                if (!file_exists($CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata')) {
                    mkdir($CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata', 0777, true);
                }
                $filename = 'oldcoursejson_'.$oldcourseid.'.json';
                $filepath = $CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata'.DIRECTORY_SEPARATOR.$filename; 
                if(!file_exists($filepath) && $refetchfromservice) {
                    $coursefetchurl = $this->settings->mastercourseurl;
                    $bearertoken = $this->settings->bearertoken;
                    $response = $this->get($coursefetchurl, ['course_id' => $oldcourseid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken,'MIGRATEAPI:ZTPLBUUSPZ'] ]);
                    if (is_null(json_decode($response))) {
                        throw new \Exception(get_string('invalid_json_response', 'local_masterdata'), 1);
                    }
                    file_put_contents($filepath, $response);
                } else {
                    $response = file_get_contents($filepath);
                }
                $courseinfo = json_decode($response);
                if (is_object($courseinfo->response->data)) {
                    $content = $courseinfo->response->data;
                } else {
                    $content = json_decode($courseinfo->response->data);
                }
                $locallib = new courselib($existingcourse,$oldcourseid);
                foreach ($content->course->children as $child) {
                    $locallib->parent = 0;
                    $locallib->process_mastercoursedata($child);
                }
            } else {
                throw new \Exception(get_string('missingcourseinfo', 'local_masterdata'), 1);
            }
        } catch (\Exception $e) {
            $return = $e->getMessage();
        }
        if (!isset($locallib)) {
            $locallib = new courselib(get_course(1), 0);
        }
        $locallib->create_data_log($return);
        return $return;
    }
    public function get_batchcourse_content($oldcourseid, $refetchfromservice = false) {
        global $DB, $CFG;
        $return = true;
        try {
            if (strpos(strtoupper($oldcourseid), 'BAT_') !== 0){
                $oldcourseid = 'BAT_'.$oldcourseid;
            }
            $existingcourse = $DB->get_record('course', ['idnumber' => $oldcourseid]);
            if ($existingcourse->originalcourseid) {
                $oldmastercourseid = $DB->get_field('course', 'idnumber', ['id' => $existingcourse->originalcourseid]);
                if (!empty($existingcourse) && $oldmastercourseid) {
                    if($existingcourse->format !='flexsections'){
                        throw new \Exception(get_string('invalidcourseformat', 'local_masterdata'), 1);
                    }
                    if (!file_exists($CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata')) {
                        mkdir($CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata', 0777, true);
                    }
                    $filename = 'oldcoursejson_'.$oldmastercourseid.'.json';
                    $filepath = $CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata'.DIRECTORY_SEPARATOR.$filename; 
                    if(!file_exists($filepath) || $refetchfromservice) {
                        $coursefetchurl = $this->settings->mastercourseurl;
                        $bearertoken = $this->settings->bearertoken;
                        $response = $this->get($coursefetchurl, ['course_id' => $oldmastercourseid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken,'MIGRATEAPI:ZTPLBUUSPZ'] ]);
                        if (is_null(json_decode($response))) {
                            throw new \Exception(get_string('invalid_json_response', 'local_masterdata'), 1);
                        }
                        file_put_contents($filepath, $response);
                    } else {
                        $response = file_get_contents($filepath);
                    }
                    $courseinfo = json_decode($response);
                    if (is_object($courseinfo->response->data)) {
                        $content = $courseinfo->response->data;
                    } else {
                        $content = json_decode($courseinfo->response->data);
                    }
                    // $locallib = new courselib($existingcourse,$oldmastercourseid);
                    $locallib = new batchcourselib($existingcourse, $oldmastercourseid, $existingcourse->originalcourseid);//  Third parameter is parentcourseid.
                    foreach ($content->course->children as $child) {
                        $locallib->parent = 0;
                        $locallib->process_mastercoursedata($child);
                    }
                } else {
                    throw new \Exception(get_string('missingcourseinfo', 'local_masterdata'), 1);
                }
            } else {
                throw new \Exception(get_string('missingmastercourseinfo', 'local_masterdata', $oldcourseid), 1);
            }
        } catch (\Exception $e) {
            $return = $e->getMessage();
        }
        if (!isset($locallib)) {
            $locallib = new batchcourselib(get_course(1), 0, 1);
        }
        $locallib->create_data_log($return);
        return $return;
    }
    public function fetch_node_data($nodeid, $mastercourseid, $fetchfromservice = false){
        global $CFG;
        $rootpath = $CFG->dataroot.DIRECTORY_SEPARATOR.'mastercoursedata'.DIRECTORY_SEPARATOR.$mastercourseid;
        if (!file_exists($rootpath)) {
            mkdir($rootpath, 0777, true);
        }
        $filename = 'nodecontent_'.$nodeid.'.json';
        $filepath = $rootpath.DIRECTORY_SEPARATOR.$filename;
        if (!file_exists($filepath) || $fetchfromservice) {
            $nodefetchurl = $this->settings->nodedetailsurl;
            $bearertoken = $this->settings->bearertoken;
            $response = $this->get($nodefetchurl, ['node_id' => $nodeid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            file_put_contents($filepath, $response);
        } else {
            $response = file_get_contents($filepath);
        }
        return $response;
    }

    public function fetchdata($dataid, $mastercourseid, $fetchfromservice = false,$requesttype = null,$batchid = 0,$pathtype = null){
        global $CFG;
        $pathprefix = ($pathtype) ? $pathtype : 'mastercoursedata';
        $rootpath = $CFG->dataroot.DIRECTORY_SEPARATOR.$pathprefix.DIRECTORY_SEPARATOR.$mastercourseid;
        if (!file_exists($rootpath)) {
            mkdir($rootpath, 0777, true);
        }
        if($requesttype == 'subjectivetest') {
            $fprefix = 'subjective';
            $requesturl = $this->settings->apihosturl.'tuition/subjective-test-data/';
            $requestparam = 'node_id';
        } else if($requesttype == 'classroomdata') {
            $fprefix = 'classroom';
            $requesturl = $this->settings->apihosturl.'tuition/classroom-data/';
            $requestparam = 'node_id';
        } else if($requesttype == 'mcqtestdata') {
            $fprefix = 'mcqtest';
            $requesturl = $this->settings->apihosturl.'/tuition/mcq-test-data/';
            $requestparam = 'node_id';
        } else if($requesttype == 'mcqexampool'){
            $fprefix = 'exampool';
            $requesturl = $this->settings->apihosturl.'/test-centre/exam-pool/';
            $requestparam = 'exam_id';
        } else if($requesttype == 'topicsplit'){
            $fprefix = 'topicsplit';
            $requesturl =$this->settings->apihosturl.'/test-centre/exam-topic-split/';
            $requestparam = 'exam_id';
        } else if($requesttype == 'mcqattemptlist'){
            $fprefix = 'attemptlist';
            $requesturl =$this->settings->apihosturl.'/test-centre/mcq-test-attempt-list/';
            $requestparam = 'exam_id';
        } else if($requesttype == 'mcqattemptinfo'){
            $fprefix = 'attemptinfo';
            $requesturl =$this->settings->apihosturl.'/test-centre/mcq-attempt-data/';
            $requestparam = 'attempt_id';
        }
        $filename = $fprefix.'content_'.$dataid.'.json';
        $filepath = $rootpath.DIRECTORY_SEPARATOR.$filename;
        if (!file_exists($filepath) || $fetchfromservice) {
            $bearertoken = $this->settings->bearertoken;
            $params = ($batchid > 0) ? [$requestparam => $dataid,'batch_id' => $batchid] :[$requestparam => $dataid];
            $response = $this->get($requesturl, $params, ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            file_put_contents($filepath, $response);
        } else {
            $response = file_get_contents($filepath);
        }
        $response = json_decode($response);
        return $response;
    }

    public function send_zoom_cancellation_notification($cmobject) {
        global $DB;    
        $context = context_course::instance($cmobject->course);
        $roleid  = $DB->get_field('role','id',array('shortname'=>'student'));
        $students = get_role_users($roleid,$context);
        $zoomdetails =  $DB->get_record('zoom',array('id'=>$cmobject->instance));
        foreach($students as $student){ 
            $message = new \core\message\message();
            $message->component =  'local_masterdata';
            $message->name = 'zoom_cancel_notification';
            $message->userfrom = get_admin();
            $message->userto = $student;
            $message->notification = 1;
            $message->courseid = $cmobject->course;
            if($zoomdetails->alternative_hosts){           
                $message->userto->ccusers = $zoomdetails->alternative_hosts;
            }
            $message->subject = $zoomdetails->name.' '.'Cancelled';
            $startdate = date('d-m-Y',$zoomdetails->start_time);
            $starttime_meridian = date('a',$zoomdetails->start_time);
            $startmeridian = $starttime_meridian == 'am' ?'AM':'PM';
            $starttime =  date("H:i",$zoomdetails->start_time) . ' '.$startmeridian;             
            $messagehtml = "<p dir='ltr' style='text-align: left;'> Dear Student,<br> please be informed that your <b>$zoomdetails->name </b>, class on <b>$startdate</b> at <b>$starttime</b> stands cancelled. Compensatory classes will be provided - Team Horizon.- Team Horizon &nbsp; </p>";
            $message->fullmessage =  html_to_text($messagehtml);
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml =   $messagehtml;
            $message->smallmessage =  $message->subject;   
            $existingmails = $this->check_pending_mail_exists($message->userto->id,$message);
            if(!$existingmails)  {
                $message_send = message_send($message);            
            }   
  
            if($message_send){
                $data = new stdClass();
                $data->notification_type =   $message->name;
                $data->from_userid = $message->userfrom->id;
                $data->to_userid =  $message->userto->id;
                $data->ccuser =  $message->userto->ccusers ? 1 : 0;
                $data->courseid = $zoomdetails->course;
                $data->cmid =  $cmobject->id;
                $data->status = 1;
                $data->timecreated = time();
                $data->messagebody =   $messagehtml;
                $DB->insert_record('local_notification_logs',$data);
            }
        }
    }

    public function send_zoom_update_notification($cmobject) {
        global $DB;

        $context = context_course::instance($cmobject->course);
        $roleid  = $DB->get_field('role','id',array('shortname'=>'student'));
        $students = get_role_users($roleid,$context);
        foreach($students as $student){ 
            $message = new \core\message\message();
            $message->component =  'local_masterdata';
            $message->name = 'zoom_reschedule_notification';
            $message->userfrom = get_admin();
            $message->userto = $student;
            $message->notification = 1;
            $message->courseid = $cmobject->course;
            if($cmobject->alternative_hosts){           
                $message->userto->ccusers = $cmobject->alternative_hosts;
            }
            $message->subject = $cmobject->name.' '.'Rescheduled';
            $startdate = date('d-m-Y',$cmobject->start_time);
            $starttime_meridian = date('a',$cmobject->start_time);
            $startmeridian = $starttime_meridian == 'am' ?'AM':'PM';
            $starttime =  date("H:i",$cmobject->start_time) . ' '.$startmeridian;             
            $messagehtml = "<p dir='ltr' style='text-align: left;'> Dear Student,<br> please be informed that your <b>$cmobject->name </b>, class on <b>$startdate</b> at <b>$starttime</b> rescheduled.- Team Horizon &nbsp; </p>";
            $message->fullmessage =  html_to_text($messagehtml);
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml =   $messagehtml;
            $message->smallmessage =  $message->subject; 
            $existingmails = $this->check_pending_mail_exists($message->userto->id,$message);
            if(!$existingmails)  {
                $message_send = message_send($message);            
            } 
       
            if($message_send){
                $data = new stdClass();
                $data->notification_type =   $message->name;
                $data->from_userid = $message->userfrom->id;
                $data->to_userid =  $message->userto->id;
                $data->ccuser =  $message->userto->ccusers ? 1 : 0;
                $data->courseid =  $cmobject->course;
                $data->cmid =  $cmobject->coursemodule;
                $data->status = 1;
                $data->timecreated = time();
                $data->messagebody = $messagehtml;
                $DB->insert_record('local_notification_logs',$data);

            }
        }  

    }

    public function send_zoom_invite_notification($cmobject) {
        global $DB;  
        $context = context_course::instance($cmobject->course);
        $roleid  = $DB->get_field('role','id',array('shortname'=>'student'));
        $students = get_role_users($roleid,$context);
        foreach($students as $student){ 
            $message = new \core\message\message();
            $message->component =  'local_masterdata';
            $message->name = 'zoom_invite_notification';
            $message->userfrom = get_admin();
            $message->userto = $student;
            $message->notification = 1;
            $message->courseid = $cmobject->course;
            if($cmobject->alternative_hosts){           
                $message->userto->ccusers = $cmobject->alternative_hosts;
            }
            $message->subject = $cmobject->name.' '.'Invitation';
            $startdate = date('d-m-Y',$cmobject->start_time);      

            $starttime_meridian = date('a',$cmobject->start_time);          
            $startmeridian = ($starttime_meridian == 'am') ? 'AM':'PM';
            $starttime =  date("H:i",$cmobject->start_time) . ' '.$startmeridian;            
            $messagehtml = "<p dir='ltr' style='text-align: left;'> Dear Student,<br> you have a <b>$cmobject->name </b>, class  scheduled on <b>$startdate</b> at <b>$starttime</b> Login to manoramahorizon.com to
            attend the class - Team Horizon &nbsp; </p>";
            $message->fullmessage =  html_to_text($messagehtml);
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml =   $messagehtml;
            $message->smallmessage =  $message->subject;  
            $existingmails = $this->check_pending_mail_exists($message->userto->id,$message);
            if(!$existingmails)  {
                $message_send = message_send($message);            
            } 
               
            if($message_send){               
                $data = new stdClass();
                $data->notification_type =   $message->name;
                $data->from_userid = $message->userfrom->id;
                $data->to_userid =  $message->userto->id;
                $data->ccuser =  $message->userto->ccusers ? 1 : 0;
                $data->courseid =  $cmobject->course;
                $data->cmid =  $cmobject->coursemoduleid;
                $data->status = 1;
                $data->timecreated = time();   
                $data->messagebody =  $messagehtml ;
                $DB->insert_record('local_notification_logs',$data);
            }
        }
    }
    public function send_zoom_remainder_notification($cmobject) {
        global $DB;  
        $context = context_course::instance($cmobject->course);
        $roleid  = $DB->get_field('role','id',array('shortname'=>'student'));
        $students = get_role_users($roleid,$context);
        foreach($students as $student){ 
            $message = new \core\message\message();
            $message->component =  'local_masterdata';
            $message->name = 'zoom_remainder_notification';
            $message->userfrom = get_admin();
            $message->userto = $student;
            $message->notification = 1;
            $message->courseid = $cmobject->course;
            if($cmobject->alternative_hosts){           
                $message->userto->ccusers = $cmobject->alternative_hosts;
            }
            $message->subject = $cmobject->name.' '.'Remainder';
            $startdate = date('d-m-Y',$cmobject->start_time);
            $starttime_meridian = date('a',$cmobject->start_time);
            $startmeridian = ($starttime_meridian == 'am' ) ? 'AM':'PM';
            $starttime =  date("H:i",$cmobject->start_time) . ' '.$startmeridian;            
            $messagehtml = "<p dir='ltr' style='text-align: left;'> Dear Student,<br> Remainder for  <b>$cmobject->name </b>,  on <b>$startdate</b> at <b>$starttime</b>  - Team Horizon &nbsp; </p>";
            $message->fullmessage =  html_to_text($messagehtml);
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml =   $messagehtml;
            $message->smallmessage =  $message->subject;  
            $existingmails = $this->check_pending_mail_exists($message->userto->id,$message);
            if(!$existingmails)  {
                $message_send = message_send($message);            
            }   

            if($message_send){               
                $data = new stdClass();
                $data->notification_type =   $message->name;
                $data->from_userid = $message->userfrom->id;
                $data->to_userid =  $message->userto->id;
                $data->ccuser =  $message->userto->ccusers ? 1 : 0;
                $data->courseid =  $cmobject->course;
                $data->cmid =  $cmobject->coursemoduleid;
                $data->status = 1;
                $data->timecreated = time();
                $data->messagebody = $messagehtml;
    
                $DB->insert_record('local_notification_logs',$data);
            }
        }
    }
    public function create_yearbook_course($examid, $type) {
        global $DB;

        if($type == 'mocktest') {
            $requesturl = 'https://api.manoramahorizon.com/payment_package/package-data-detail';
            $bearertoken = $this->settings->bearertoken;
            $response = $this->get($requesturl,['package_id'=>116], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            $packageresponse = json_decode($response);
            $packageinfo = $packageresponse->response->package_details;
        } else {
          
            $packageinfo = $DB->get_record('test_centre_exam',['id'=>(int)$examid]);

        }
        $this->create_course($examid,$packageinfo,$type);

    }
    public function create_course($examid,$packageinfo,$type) {
        global $DB; 
        
        $categoryid = $DB->get_field("course_categories","id", ['idnumber' => 'yearbookv2']);
        $examname =$DB->get_field('test_centre_exam','exam_name',['id'=>(int)$examid]);
        $maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {course} WHERE category =:catid',['catid'=>$categoryid]);
        $catsortorder = $DB->get_field('course_categories','sortorder',['id'=>$categoryid]);
        $course = new stdClass();
        $pkgname =($packageinfo) ? $packageinfo->name : (($type =='mocktest') ? 'Yearbook Mocktest Course'  : 'Test Course');  
        $course->fullname = ($examname) ? $examname : $pkgname;
        $course->shortname = ($type =='mocktest') ? 'YB_MOCK_TEST_'.$examid  : 'TEST_'.$examid;
        $course->idnumber =($type =='mocktest') ? 'YB_MOCK_TEST_'.$examid  : 'TEST_'.$examid;
        $course->format = 'singleactivity';
        $course->activitytype = 'quiz';
        $course->startdate = time();
        $course->sortorder=($maxsortorder) ? ($maxsortorder + 1) : $catsortorder;
        $course->enddate = 0;
        $course->open_coursetype = 1;
        $course->open_module = ($type =='mocktest') ? 'year_book_mocktest'  : 'year_book';
        $course->category = $categoryid;
        $course->summaryformat = FORMAT_HTML;
        try{
            //set default value for completion
            if (\completion_info::is_enabled_for_site()) {
                $course->enablecompletion = 1;
            } else {
                $course->enablecompletion = 0;
            }
            $course = create_course($course);
            mtrace('New course having name <b>'.$course->fullname.'</b> and shortname <b>'.$course->shortname.'</b> created successfully'.'</br>');
            $this->ernoll_user_to_course($examid,$course);
            $this->create_exam_quiz($examid,$course,$packageinfo,$type);
        } catch(\moodle_exception $e){
            print_r($e);
        }
    }

    public  function create_exam_quiz($examid,$course,$packageinfo,$type) {
        global $DB; 
        $moduledata = new stdClass();
        
        $section = course_create_section((int)$course->id);
        $moduledata->name = $course->fullname;
        $moduledata->modulename = 'quiz';
        $moduledata->course =(int)$course->id;
        $moduledata->section = $section->section;
        $moduledata->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $moduledata->visible = 1;
        $moduledata->testtype = 0 ;
        $moduledata->quizpassword = 0;
        $moduledata->questionsperpage = 1;
        $moduledata->preferredbehaviour = 'deferredfeedback';
        $moduledata->questionsperpage = 1;
        $moduledata->attempts = 1;
        $moduleinfo = create_module($moduledata);
        $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
        $quiz->reviewcorrectness = 69632;
        $quiz->reviewmarks = 69632;
        $quiz->reviewspecificfeedback = 69632;
        $quiz->reviewgeneralfeedback = 69632;
        $quiz->reviewrightanswer= 69632;
        $quiz->reviewoverallfeedback = 69632;
        $DB->update_record('quiz',$quiz);
        mtrace('<b>'.ucfirst($moduledata->modulename).'</b> module having name <b>'.$moduledata->name.'</b> created successfully under course having shortname <b>'.$course->shortname.'</b>'.'</br>');
        $this->create_exam_question((int)$examid,$moduleinfo,$course,$packageinfo,$type);
    }
    public  function create_exam_question($examid,$moduleinfo,$course,$packageinfo,$type) {
        global $DB,$CFG;
        $adminuserid =(int) $DB->get_field('user','id',['username'=>'admin']);
        $quizobj = \mod_quiz\quiz_settings::create($moduleinfo->instance, $adminuserid);
        $qnpool = ($type =='mocktest') ?(int)$packageinfo->exam->use_qn_pool :(int) $packageinfo->use_qn_pool ;
        if($qnpool) {
            $examinforecord =$DB->get_record('test_centre_exam',['id'=>(int)$examid]);
            $quizmodule = new stdClass();
            $quizmodule->id = (int)$moduleinfo->id;
            $quizmodule->course=(int)$moduleinfo->course;
            $quizmodule->coursemodule = (int)$moduleinfo->id;
            $quizmodule->section = (int)$moduleinfo->section;
            $quizmodule->module = (int)$moduleinfo->module;
            $quizmodule->modulename = 'quiz';
            $quizmodule->instance =(int)$moduleinfo->instance;
            $quizmodule->timeopen = ($examinforecord->start_date)? strtotime(str_replace("T"," ",$examinforecord->start_date)) : 0;
            $quizmodule->timeclose =  $examinforecord->end_date ? strtotime(str_replace("T"," ",$examinforecord->end_date)) : 0;
            $quizmodule->timelimit = $examinforecord->time_limit ? $this->get_seconds($examinforecord->time_limit):0;
            $grade =($examinforecord->mark)? $examinforecord->mark : 0;
           
            if($examinforecord->marks_per_qn){
                $quizmodule->customfield_nsca =  $examinforecord->marks_per_qn;
            }
            if($examinforecord->exam->negative_mark_per_qn){
                $quizmodule->customfield_nswa =  $examinforecord->negative_mark_per_qn;
            }
            $quizmodule->quizmodulepassword = 0;
            $quizmodule->questionsperpage = 1;
            $quizmodule->add = 0;
            $quizmodule->quizpassword = 0;
            $quizmodule->update = (int)$moduleinfo->id;
            $quizmodule->visible = ((int)$examinforecord->is_active > 0) ? 1 : 0;
            if($examinforecord->instructions) {
                $quizmodule->introeditor = ['text' => $examinforecord->instructions, 'format' => FORMAT_HTML, 'itemid' => null];
            } else {
                 $quizmodule->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
            }
            update_module($quizmodule);
            // MCQ Exam Pool

            $bearertoken = $this->settings->bearertoken;
            $qresponse = $this->get($this->settings->apihosturl.'/test-centre/exam-pool/',['exam_id'=>(int)$examid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            $questionpoolresponse = json_decode($qresponse);

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
                            \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->recompute_quiz_sumgrades();
                            \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->update_quiz_maximum_grade($grade);

                        }
                    }
                }
            }
        }  else {
            // Test Center Topic Split Info
            $bearertoken = $this->settings->bearertoken;
            $tsresponse = $this->get($this->settings->apihosturl.'/test-centre/exam-topic-split/',['exam_id'=>(int)$examid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            $topicsplitresponse = json_decode($tsresponse);
            $source = $topicsplitresponse->response->exam->source;
            if($source) {
                $source_name = $DB->get_field('test_centre_source','source_name',['id'=>$source]);
            }
            $split_by = $topicsplitresponse->response->exam->split_by;
            $no_of_questions = $topicsplitresponse->response->exam->no_of_questions;
            $quizmodule = new stdClass();
            $quizmodule->id = (int)$moduleinfo->id;
            $quizmodule->course=(int)$moduleinfo->course;
            $quizmodule->coursemodule = (int)$moduleinfo->id;
            $quizmodule->section = (int)$moduleinfo->section;
            $quizmodule->module = (int)$moduleinfo->module;
            $quizmodule->modulename = 'quiz';
            $quizmodule->instance =(int)$moduleinfo->instance;
            $quizmodule->timeopen = strtotime(str_replace("T"," ",$topicsplitresponse->response->exam->start_date));
            $quizmodule->timeclose =  strtotime(str_replace("T"," ",$topicsplitresponse->response->exam->end_date));
            $quizmodule->timelimit = $this->get_seconds($topicsplitresponse->response->exam->time_limit);
            $grade = ($topicsplitresponse->response->exam->mark)? $topicsplitresponse->response->exam->mark : 0;
           
            if($topicsplitresponse->response->exam->marks_per_qn){
                $quizmodule->customfield_nsca =  $topicsplitresponse->response->exam->marks_per_qn;
            }
            if($topicsplitresponse->response->exam->negative_mark_per_qn){
                $quizmodule->customfield_nswa =  $topicsplitresponse->response->exam->negative_mark_per_qn;
            }
            $quizmodule->quizmodulepassword = 0;
            $quizmodule->questionsperpage = 1;
            $quizmodule->add = 0;
            $quizmodule->quizpassword = 0;
            $quizmodule->update = (int)$moduleinfo->id;
            $quizmodule->visible = ((int)$topicsplitresponse->response->exam->is_active > 0) ? 1 : 0;
            if($topicsplitresponse->response->exam->instructions) {
                $quizmodule->introeditor = ['text' => $topicsplitresponse->response->exam->instructions, 'format' => FORMAT_HTML, 'itemid' => null];
            } else {
                $quizmodule->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
            }
            update_module($quizmodule);
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
                        $quizobject = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
                        local_questions_quiz_add_random_questions($quizobject, 0, $categoryid, $randomqnum, 0, [], $goalid, $boardid, $classid, $subjectid,$unitid,$chapterid,$topicid,0);
                        \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->recompute_quiz_sumgrades();
                        \mod_quiz\quiz_settings::create((int)$moduleinfo->instance)->get_grade_calculator()->update_quiz_maximum_grade($grade);
                
                    }
                }
            }
        }
        $allattempts = $this->total_page_attempt_list($examid);
        if (COUNT($allattempts) > 0){
            $i=0;
            foreach ($allattempts AS $exam_attempt) {
                $questionattemptsdata = new stdClass();
                $questionattemptsdata->examid = $examid;
                $questionattemptsdata->cmid = (int)$moduleinfo->id;
                $questionattemptsdata->quizid = (int)$moduleinfo->instance;
                $questionattemptsdata->attemptid = (int)$exam_attempt->attempt_id;
                $questionattemptsdata->studentid = ((int)$exam_attempt->student_id) ? (int)$exam_attempt->student_id : 0;

                // MCQ Attempt info.
                $airesponse = $this->get($this->settings->apihosturl.'/test-centre/mcq-attempt-data/',['attempt_id'=>(int)$exam_attempt->attempt_id], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$this->settings->bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
                $attemptinforesponse = json_decode($airesponse);

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
                    $questionid = trim($questionid);
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
                $questionattemptsdata->is_exam_finished =($attemptdata->is_exam_finished) ? $attemptdata->is_exam_finished : 0;
                $questionattemptsdata->exam_mode =($attemptdata->exam_mode) ? $attemptdata->exam_mode : 0; 
                $questionattemptsdata->no_of_qns =($attemptdata->no_of_qns)? $attemptdata->no_of_qns : 0; 
                $questionattemptsdata->is_exam_paused =($attemptdata->is_exam_paused) ?$attemptdata->is_exam_paused : 0; 
                $questionattemptsdata->is_module_wise_test =($attemptdata->is_module_wise_test) ? $attemptdata->is_module_wise_test : 0; 
                $questionattemptsdata->total_mark =($attemptdata->total_mark) ? $attemptdata->total_mark : 0; 
                $questionattemptsdata->timecreated =time(); 
                $questionattemptsdata->usercreated =$adminuserid; 
                $DB->insert_record('local_question_attempts',$questionattemptsdata);
                $i++;
                mtrace('Attempt <b>'.(int)$exam_attempt->attempt_id.'</b> created on behalf of exam <b>'.$examid.'</b> successfully'.'</br>');
            }
            mtrace('Total <b>'.$i.'</b>  Attempts created successfully'.'</br>');
        }
    
    }
    public function ernoll_user_to_course($examid,$course){
        global $DB;
        $examenorlledstudents = $DB->get_records('test_centre_studentexamenrol',['is_active'=>1,'exam_id'=>$examid]);
        if(COUNT($examenorlledstudents) > 0) {
            foreach($examenorlledstudents AS $examenorlledstudent) {
                $manual = enrol_get_plugin('manual');
                $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', MUST_EXIST);
                $mdluser = $DB->get_record('user',['idnumber'=>$examenorlledstudent->student_id]);
                $roleid =(int) $DB->get_field('role','id',['shortname'=>'student']);
                $timestart = ($examenorlledstudent->enroled_on) ? strtotime($examenorlledstudent->enroled_on) : time();
                $timeend = ($examenorlledstudent->valid_till) ? strtotime($examenorlledstudent->valid_till) : 0;
                if($mdluser && $mdluser->id > 0){
                    $manual->enrol_user($instance,(int)$mdluser->id,$roleid,$timestart,$timeend);
                    $fullname = $mdluser->firstname.' '.$mdluser->lastname;
                    mtrace('<b>'.$fullname.'</b> enrolled to the course having shortname <b>'.$course->shortname.'</b>'.'</br>');

                }
            }
        }
    }
    public function total_page_attempt_list($examid){
        $requesturl = $this->settings->apihosturl.'/test-centre/mcq-test-attempt-list/';
        $bearertoken = $this->settings->bearertoken;
        $pageresponse = $this->get($requesturl,['exam_id'=>$examid], ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
        $attemptlist = json_decode($pageresponse);
        $totalpages = (int)$attemptlist->response->pagination_info->total_pages;
        $requesturl = $this->settings->apihosturl.'/test-centre/mcq-test-attempt-list/';
        $bearertoken = $this->settings->bearertoken;

        if($totalpages > 1)  {
            $attemptinfo= [];
            for($i=1; $i <= $totalpages; $i++) {
                $params = ['exam_id' => $examid,'page'=>$i];
                $response = $this->get($requesturl, $params, ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
                $attemptresponse = json_decode($response);
                
                $attemptinfo=array_merge($attemptinfo, $attemptresponse->response->exam_attempts);
            }
        } else {
            $params = ['exam_id' => $examid];
            $response = $this->get($requesturl, $params, ['CURLOPT_HTTPHEADER' =>  ['Authorization: Bearer '.$bearertoken, 'MIGRATEAPI:ZTPLBUUSPZ']]);
            $attemptresponse = json_decode($response);
            $attemptinfo = $attemptresponse->response->exam_attempts;
        }
        return $attemptinfo;
    }
    // $start_time should be in hh:mm:ss format only,
    public function get_seconds($str_time){

        $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);

        sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);

        $time_seconds = $hours * 3600 + $minutes * 60 + $seconds;

        return $time_seconds;
    }

    public function check_pending_mail_exists($user,$dataobject){

        global $DB, $USER;
        $sql =  " SELECT id FROM {local_notification_logs} WHERE to_userid = :to_userid AND (".$DB->sql_compare_text('messagebody')." = ".$DB->sql_compare_text(':messagebody').")";
        $params['to_userid'] = $user;
        $params['messagebody'] = $dataobject->fullmessagehtml;        
        return $DB->get_field_sql($sql ,$params);
    }

}
