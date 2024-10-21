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
namespace local_questions\local;
defined('MOODLE_INTERNAL') or die;
use html_writer;
use moodle_url;
use context_system;
use context_user;
use core_user;
use filters_form;
use stdClass;
use moodle_exception;
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once('../../config.php');

class questionbank{
	public function questionsinfo() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_questions');
        $filterparams  = $renderer->get_questions(true);
        $filterparams['submitid'] = 'form#filteringform';
        $filterparams['widthclass'] = 'col-md-12';
        $filterparams['placeholder'] = get_string('search_questions','local_questions');
        $globalinput=$renderer->global_filter($filterparams);
        $questiondetails = $renderer->get_questions();
        $filterparams['questiondetails'] = $questiondetails;
        $filterparams['globalinput'] = $globalinput;
        $renderer->questionview($filterparams);
    }

    public function get_listof_questions($stable, $filterdata) {
        global $DB;
        $selectsql = "SELECT * FROM {local_questions} lo "; 
        $countsql  = "SELECT COUNT(lo.id) FROM {local_questions} lo  ";
        $formsql =" WHERE 1=1 ";
        if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
            $formsql .= " AND (lo.questionid LIKE :questionid 
                        ) ";
            $searchparams = array(
                  'questionid' => '%'.trim($filterdata->search_query).'%',
           );
        } else {
            $searchparams = array();
        }
        $params = array_merge($searchparams);
        $totalrecords = $DB->count_records_sql($countsql.$formsql,$params);         
        $formsql .=" ORDER BY lo.id DESC";
        $records = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);
        $questionslist = array();
        $count = 0;
        foreach($records as $record) {
            $questionslist[$count]["id"] = $record->id;
            $questionslist[$count]["questionid"] = $record->questionid;
            $count++;

        }
        $questionContext = array(
            "hascourses" => $questionslist,
            "totalrecords" => $totalrecords,
            "length" => count($questionslist)
        );
        return $questionContext;
    }

       public function questions_reviewstatus($questionid,$qcategory,$status) {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        $courseid =  $DB->get_field('local_questions_courses','courseid',array('questionbankid'=>$qcategory,'questionid'=>$questionid));
        if($qcategory){
            $data =new stdClass();
            $data->questionbankid = $qcategory;
            $data->questionid =$questionid;
            $data->courseid =$courseid;
            $data->reviewdon = time();
            $data->qstatus = $status;
            $data->reviewdby  = $USER->id;
            $questioninfo =  $DB->get_field('local_qb_questionreview','id',array('questionid'=>$questionid,'questionbankid'=>$qcategory));
            $questioninfoid =  $DB->get_field('local_questions_courses','id',array('questionid'=>$questionid,'questionbankid'=>$qcategory));
            $updatereview = new stdClass();
            $updatereview->id = $questioninfoid;
            if(empty($questioninfo)){
                $data->id = $DB->insert_record('local_qb_questionreview',$data);
            }else{
                $data->id  =  $questioninfo;
                $DB->update_record('local_qb_questionreview',$data);
            }
            if($status == "underreview" || $status == "readytoreview" || $status == "publish" || $status == "reject"){
                $updatereview->underreviewby = $USER->id;
                $DB->update_record('local_questions_courses',$updatereview);
            }
            if($status == "readytoreview"){
                $updatereview->reviewby = $USER->id;
                $DB->update_record('local_questions_courses',$updatereview);
            }
            if($status == "publish" || $status == "reject"){
                $updatereview->finalstatusby = $USER->id;
                $DB->update_record('local_questions_courses',$updatereview);
            }

        }
        purge_other_caches();
        return true;
            
        
    }

    public function get_quizinfo($quizid) {
        global $DB;

        $sql = "SELECT q.id AS questionid, q.questiontext, q.name AS questionname
                  FROM mdl_quiz_slots slot
             LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = slot.id
             LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
             LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
             LEFT JOIN mdl_question q ON q.id = qv.questionid
                 WHERE slot.quizid = ". $quizid;
        $questions = $DB->get_records_sql($sql);

        $data = [];
        foreach($questions as $question) {
            $row = [];
            $row['questionid'] = $question->questionid;
            $row['questionname'] = $question->questionname;
            $row['questiontext'] = $question->questiontext;
            $row['answers'] = self::get_questionanswers($question->questionid);
            $data[] = $row;
        }

        return $data;
    }

    public function get_questionanswers($questionid) {
        global $DB;
        $qanswers = $DB->get_records('question_answers', ['question' => $questionid]);

        $data= [];
        foreach($qanswers as $qanswer) {
            $row = [];
            $row['optionid']= $qanswer->id;
            $row['option']= $qanswer->answer;
            $data[] = $row;
        }

        return $data;
    }

    public function createorupdate_user($user, $role, $sources, $old_id=NULL, $profile=false) {
        global $CFG, $DB;
        $context = context_system::instance();
        require_once($CFG->dirroot."/user/lib.php");
        require_once($CFG->dirroot."/user/editlib.php");

        $hostid = $DB->get_field('mnet_host', 'id', ['wwwroot' => $CFG->wwwroot]);
        if ($hostid) {
            $user['mnethostid'] = $hostid;
        } else {
            $user['mnethostid'] = 1;
        }
        $user['confirmed'] = 1;
        $user['idnumber'] = !empty($old_id) ? $old_id : '';
        $userid = $DB->get_field('user', 'id', ['username' => $user['username']]);
        $roles = explode(',', $role); 
        if ($userid) {
            $user['id'] = $userid;            
            user_update_user($user, false, false);  
            $existingroles = $DB->get_records('role_assignments',array('userid'=>$userid)); 
            self::unassign_existingroles($existingroles,$userid);
            self::assign_newroles($roles,$userid);

            // $fs = get_file_storage();
            // $image = $profile;
            // $profile = 'blob:https://manoramal.eabyas.in/919dfde2-1954-4e85-beef-6791cb3bc0a5';

            // $context = context_user::instance($userid);
            // $filerecorda = array(
            //     'contextid' => $context->id,
            //     'component' => 'user',
            //     'filearea'  => 'icon',
            //     'filepath'  => '/',
            //     'filename'  => "sampletest.jpg",
            // );
            // $filerecorda['itemid'] = 0;

            // $fs->create_file_from_url($fileinfo, $profile);

        } else {
            $userid = user_create_user($user, true, false);            
              
            foreach($roles as  $role){ 
                $roleid = $DB->get_field('role','id',array('shortname'=>$role));    
                role_assign($roleid, $userid, $context->id);
            }
          
        }
        if(!empty($sources)) {
            self::insert_sources($sources, $userid);
        }

        return $userid;
    }

    public function insert_sources($sources, $userid) {
        global $DB;
        $sources = unserialize(base64_decode($sources));   
        $sourcecodes = array_column($sources, 'code');

        foreach($sources as $source) {
            $params = [];
            $params['name'] = $source['name'];
            $params['code'] = $source['code'];
            
            $id = $DB->get_field('local_question_sources', 'id', ['code' => $source['code']]);

            if ($id) {
                $params['id'] = $id;
                $params['timeupdated'] = time();
                $DB->update_record('local_question_sources', $params);
                $id = $params['id'];
            } else {
                $params['timecreated'] = time();
                $id = $DB->insert_record('local_question_sources', $params);
            }
        }

        $srccodesstring = implode(',', $sourcecodes);
        $id = self::insert_usersources($userid, $srccodesstring);

        if ($id) {
            return $id;
        }

    }

    public function insert_usersources($userid, $sourcecodes) {
        global $DB;

        $sources = str_replace(",", "','", $sourcecodes);
        $sql = "SELECT GROUP_CONCAT(id) as sources
                  FROM {local_question_sources}
                 WHERE code IN ('$sources')";
        
        $sources = $DB->get_record_sql($sql);

        $id = $DB->get_field('user_sources', 'id', ['userid' => $userid]);
        if ($id) {
            if ($sources) {
                $DB->update_record('user_sources', ['id' => $id, 'userid' => $userid, 'sourceid' => $sources->sources, 'timecreated' => time()]);
    
                return $id;
            }
        } else {
            if ($sources) {
                $id = $DB->insert_record('user_sources', ['userid' => $userid, 'sourceid' => $sources->sources, 'timecreated' => time()]);
    
                return $id;
            }
        }
    }
    ////Function Name mod_quiz_output_fragment_quiz_question_bank 
    public function override_quiz_fragment($args){
     global $CFG, $DB, $PAGE;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    require_once($CFG->dirroot . '/question/editlib.php');
    $querystring = preg_replace('/^\?/', '', $args['querystring']);
    $params = [];
    parse_str($querystring, $params);
    // Build the required resources. The $params are all cleaned as
    // part of this process.
    list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars) =
            question_build_edit_resources('editq', '/mod/quiz/edit.php', $params, \local_questions\local\qselection_view::DEFAULT_PAGE_SIZE);
            $systemcontext = context_system::instance();
            $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
            $pagevars['cat'] = $pcategory.','.$systemcontext->id;
            $systemcontext = context_system::instance();
      $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
      $cat = $pcategory.','.$systemcontext->id;

    // Get the course object and related bits.
    $course = get_course($quiz->course);
    require_capability('mod/quiz:manage', $contexts->lowest());

    // Create quiz question bank view.
    $questionbank = new \local_questions\local\qselection_view($contexts, $thispageurl, $course, $cm, $params);
    $questionbank->set_quiz_has_attempts(quiz_has_attempts($quiz->id));

    // Output.
    $renderer = $PAGE->get_renderer('mod_quiz', 'edit');
    return $renderer->question_bank_contents($questionbank, array_merge($pagevars, $params));



    }
    public function unassign_existingroles($existingroles,$userid){
        global $DB;
        $context = context_system::instance();
        foreach( $existingroles as   $existingrole){
            role_unassign($existingrole->roleid, $userid, $context->id);          
        }
    }
    public function assign_newroles($roles,$userid){
        global $DB;
        $context = context_system::instance();
        foreach($roles as  $role){ 
            $roleid = $DB->get_field('role','id',array('shortname'=>$role)); 
            role_assign($roleid, $userid, $context->id);
        }

    }
}
