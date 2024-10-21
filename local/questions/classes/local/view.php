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

use core_question\local\bank\view as qview;
use question_bank;
use qbank_previewquestion\question_preview_options;
use qbank_editquestion\editquestion_helper;
use question_engine;
use context_user;
use context_system;
use moodle_url;
use core_question\bank\search\category_condition;

class view extends qview {

    public function __construct($contexts, $pageurl, $course, $cm = null,$filterparams){
        // parent::__construct($contexts, $pageurl, $course, $cm);
        $this->contexts = $contexts;
        $this->baseurl = $pageurl;
        $this->course = $course;
        $this->cm = $cm;
        $this->filterparams = $filterparams;

        // Create the url of the new question page to forward to.
        $this->returnurl = $pageurl->out_as_local_url(false);
        //$this->editquestionurl = new moodle_url('/local/questions/editquestion.php', ['returnurl' => $this->returnurl]);
         $this->editquestionurl = new moodle_url('/question/bank/editquestion/question.php', ['returnurl' => $this->returnurl]);
        if ($this->cm !== null) {
            $this->editquestionurl->param('cmid', $this->cm->id);
        } else {
            $this->editquestionurl->param('courseid', $this->course->id);
        }

        $this->lastchangedid = optional_param('lastchanged', 0, PARAM_INT);

        // Possibly the heading part can be removed.
        $this->init_columns($this->wanted_columns(), $this->heading_column());
        $this->init_sort();
        $this->init_search_conditions();
        $this->init_bulk_actions();
    }


    
    public function display($pagevars, $tabname): void {
        global $DB,$USER,$CFG, $OUTPUT;
        $systemcontext = context_system::instance();
        $page = $pagevars['qpage'];
        $perpage = $pagevars['qperpage'];
        $goalid = $pagevars['goalid'];
        $boardid = $pagevars['boardid'];
        $classid = $pagevars['classid'];
        $courseid = $pagevars['courseid'];
        $topicid = $pagevars['topicid'];
        $chapterid = $pagevars['chapterid'];
        $unitid = $pagevars['unitid'];
        $conceptid = $pagevars['conceptid'];
        $cat = $pagevars['cat'];
        $recurse = $pagevars['recurse'];
        $showhidden = $pagevars['showhidden'];
        $showquestiontext = $pagevars['qbshowtext'];
        $tagids = [];
        if (!empty($pagevars['qtagids'])) {
            $tagids = $pagevars['qtagids'];
        }
       

        // $questioninfo =  $DB->get_field('local_qb_questionreview','id',array('questionid'=>$questions->id,'reviewdby'=>$USER->id));
        // if(($qinfo->createdby != $USER->id) && empty( $questioninfo)){
        //         continue;
        // }
        echo \html_writer::start_div('questionbankwindow boxwidthwide boxaligncenter');

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        
        array_unshift($this->searchconditions, new \core_question\bank\search\category_condition(
                        $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
        list($categoryid, $contextid) = explode(',', $cat);
        // Continues with list of questions.
        $this->display_question_list($this->baseurl, $cat,1, $page, $perpage,[]);

        echo \html_writer::end_div();


    }

    protected function display_question_list($pageurl, $categoryandcontext, $recurse = 1, $page = 0,
                                                $perpage = 100, $addcontexts = []): void {
        global $OUTPUT,$DB,$USER;
        $goalid      = optional_param('goalid','', PARAM_INT);
        $boardid      = optional_param('boardid', '', PARAM_INT);
        $classid      = optional_param('classid', '', PARAM_INT);
        $subjectid      = optional_param('subjectid', '', PARAM_INT);
        $courseid      = optional_param('courseid', 1, PARAM_INT);
        $topicid      = optional_param('topicid', '', PARAM_INT);
        $chapterid      = optional_param('chapterid', '', PARAM_INT);
        $unitid      = optional_param('unitid', '', PARAM_INT);
        $conceptid      = optional_param('conceptid', '', PARAM_INT);
        $cmid      = optional_param('cmid', '', PARAM_INT);
        $source      = optional_param('source', '', PARAM_INT);
        $qstatus      = optional_param('qstatus', '', PARAM_RAW);
        $cognitive      = optional_param('cognitive', '', PARAM_INT);
        $difficulty      = optional_param('difficulty', '', PARAM_INT);
        $qid      = optional_param('questionid', '', PARAM_RAW);    
        $qidentifier      = optional_param('qidentifier', '', PARAM_RAW);    
        $uploadfrom      = optional_param('uploadfrom', '', PARAM_RAW); 
        $uploadto      = optional_param('uploadto', '', PARAM_RAW);
        $systemcontext = context_system::instance();
        $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
        $cat = $pcategory.','.$systemcontext->id;
        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);
        $category = $this->get_current_category($categoryandcontext);
        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);
        $canadd = has_capability('moodle/question:add', $catcontext);
        $this->build_query();
        $totalnumber = $this->get_question_count();
        // if ($totalnumber == 0) {
        //     return;
        // }
        $questionsrs = $this->load_page_questions($page, $perpage);
        $questions = [];
        foreach ($questionsrs as $question) {
            if (!empty($question->id)) {
                $questions[$question->id] = $question;
                // if ($question->createdby == $USER->id) {
                //     $createdby[] = $question->id;
                // }
            }
        }
        $questionsrs->close();
        foreach ($this->requiredcolumns as $name => $column) {
            $column->load_additional_data($questions);
        }
        $pageingurl = new moodle_url($pageurl, $pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        $quesstatus = $this->get_ques_statuses();
        echo \html_writer::start_tag('div' , ['class' => 'd-flex justify-content-between align-items-cennter mb-4 mt-2']);
        $options = ['5' => 5, '20' => 20, '50' => 50, '100' => 100];
        $urlparams = array();
        if($courseid != "" ){
         $urlparams['courseid'] =1;
        }
         if($cat != "" ){
         $urlparams['cat'] =$category->id .',1';
        }
        if($qid != "" ){
         $urlparams['questionid'] =$qid;
        }
         if($qidentifier != "" ){
         $urlparams['qidentifier'] =$qidentifier;
        }
        if($goalid != "" ){
         $urlparams['goalid'] =$goalid;
        }
         if($boardid != "" ){
         $urlparams['boardid'] =$boardid;
        }
        if($classid != "" ){
         $urlparams['classid'] =$classid;
        }
        if($subjectid != "" ){
         $urlparams['subjectid'] =$subjectid;
        }
        if($topicid != "" ){
         $urlparams['topicid'] =$topicid;
        }
         if($chapterid != "" ){
         $urlparams['chapterid'] =$chapterid;
        }
        if($unitid != "" ){
         $urlparams['unitid'] =$unitid;
        }
         if($conceptid != "" ){
         $urlparams['conceptid'] =$conceptid;
        }
        if($difficulty != "" ){
         $urlparams['difficulty'] =$difficulty;
        }
        if($cognitive != "" ){
         $urlparams['cognitive'] =$cognitive;
        }
        if($source != "" ){
         $urlparams['source'] =$source;
        }
         if($qstatus != "" ){
         $urlparams['qstatus'] =$qstatus;
        }
        if($uploadfrom != "" ){
         $urlparams['uploadfrom'] =$uploadfrom;
        }
        if($uploadto != "" ){
         $urlparams['uploadto'] =$uploadto;
        }
         if($cmid != "" ){
         $urlparams['cmid'] =$cmid;
        }
        $url = new moodle_url('/local/questions/questionbank_view.php', $urlparams);  
        $singleselect = $OUTPUT->single_select($url, 'qperpage', $options, $perpage, null);
        echo \html_writer::start_tag('div', array('class' => 'd-flex align-items-center'));
            echo \html_writer::span(get_string('qperpage', 'local_questions'),  'mr-2');
            echo \html_writer::div($singleselect);
        echo \html_writer::end_tag('div');

        echo \html_writer::start_tag('div');
        $this->create_new_question_form($category, $canadd);
            
        echo \html_writer::end_tag('div');
        // echo \html_writer::start_tag('div' , ['class' => 'col-md-7']);
        $systemcontext = context_system::instance();
        if(is_siteadmin() || has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:qmanager',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext)){
            $statuschange = true;  
        } else {
            $statuschange = false; 
        }
        // echo \html_writer::end_tag('div');
        echo \html_writer::end_tag('div');
       // $this->display_top_pagnation($OUTPUT->render($pagingbar));
        // This html will be refactored in the bulk actions implementation.
        echo \html_writer::start_tag('form', ['action' => $pageurl, 'method' => 'post', 'id' => 'questionsubmit']);
        echo \html_writer::start_tag('fieldset', ['class' => 'invisiblefieldset', 'style' => "display: block;"]);
        echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo \html_writer::input_hidden_params($this->baseurl);
        $this->display_questions($questions);
        $this->display_bottom_pagination($OUTPUT->render($pagingbar), $totalnumber, $perpage, $pageurl);
        echo \html_writer::end_tag('fieldset');
        echo \html_writer::end_tag('form');
    }
    /**
     * Display the questions.
     *
     * @param array $questions
     */
    protected function display_questions($questions1): void {
        global $DB, $USER,$OUTPUT,$USER,$CFG,$PAGE;
        $res ='';
        $ques_added_count = 0;
        foreach($questions1 as $questions){
            $questionstatus = "SELECT qstatus 
                               FROM  {local_qb_questionreview} 
                               WHERE questionid = $questions->id";
            $getquestionstatus = $DB->get_field_sql($questionstatus);
            $systemcontext = context_system::instance();
            $assigned_reviewer  ='';
            define('QUESTION_PREVIEW_MAX_VARIANTS', 100);
            $question = question_bank::load_question($questions->id);
            $maxvariant = min($question->get_num_variants(), QUESTION_PREVIEW_MAX_VARIANTS);
            $options = new question_preview_options($question);
            $options->load_user_defaults();
            $options->set_from_request();
            $quba = question_engine::make_questions_usage_by_activity(
                    'core_question_preview', context_user::instance($USER->id));
            $quba->set_preferred_behaviour($options->behaviour);
            $slot = $quba->add_question($question, $options->maxmark);

            if ($options->variant) {
                $options->variant = min($maxvariant, max(1, $options->variant));
            } else {
                $options->variant = rand(1, $maxvariant);
            }
            $quba->start_question($slot, $options->variant);

           $transaction = $DB->start_delegated_transaction();
           question_engine::save_questions_usage_by_activity($quba);
           $transaction->allow_commit();
           $options->behaviour = $quba->get_preferred_behaviour();
           $options->maxmark = $quba->get_question_max_mark($slot);
           $qinfo =$quba->get_question($slot);
           $currentlang= current_language();
           $reviewersql="SELECT qb.*  
                          FROM {local_qb_questionreview} qb 
                          WHERE qb.questionid = :qbqid ";
           $reviewer = $DB->get_record_sql($reviewersql,['qbqid' => $questions->id]);
           $ques = new \stdClass();
           $ques->questionid = $questions->id;
            $systemcontext = context_system::instance();
            if(is_siteadmin() || has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:qcreater',$systemcontext) || has_capability('local/questions:qmanager',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext) || has_capability('local/questions:caneditanystatus', $systemcontext) || has_capability('local/questions:candeleteanystatus', $systemcontext) || has_capability('local/questions:canchangestatus', $systemcontext)){
                $statuschange = true;
            }else{
               $statuschange = false;
            }
            $reviewerstatussql="SELECT qstatus 
                                FROM  {local_qb_questionreview}
                                WHERE questionid = $questions->id";
            $reviewerstatus = $DB->get_field_sql($reviewerstatussql);
            $questionstate = ($reviewerstatus !="publish" && $reviewerstatus != "underreview" && $reviewerstatus != "readytoreview");

            if((is_siteadmin() && $questionstate) || (has_capability('local/questions:qcreater',$systemcontext) && $questionstate) || (has_capability('local/questions:qreviewer',$systemcontext) && $questionstate) || (has_capability('local/questions:qmanager',$systemcontext) && $questionstate) || (has_capability('local/questions:testcenterexpert',$systemcontext) && $questionstate) || has_capability('local/questions:canchangestatus', $systemcontext) && $questionstate){
                $qcreaterstatuschange = true;
           }else{
                $qcreaterstatuschange = false;
           }
           $editdeleteenable = false;
           if (is_siteadmin() || has_capability('local/questions:caneditanystatus', $systemcontext) || has_capability('local/questions:candeleteanystatus', $systemcontext) || (has_capability('local/questions:qcreater',$systemcontext) && ($reviewerstatus !="publish" && $reviewerstatus != "underreview" && $reviewerstatus != "readytoreview" ))) {
               $editdeleteenable = true;
           }
           // $deleteatanystatus = false;
           // if (is_siteadmin() || has_capability('local/questions:candeleteanystatus', $systemcontext)) {
           //     $deleteatanystatus = true;
           // }
           if((is_siteadmin() && $reviewerstatus == "readytoreview") || (has_capability('local/questions:qreviewer',$systemcontext) && $reviewerstatus == "readytoreview") || (has_capability('local/questions:qmanager',$systemcontext) && $reviewerstatus == "readytoreview") || (has_capability('local/questions:testcenterexpert',$systemcontext) && $reviewerstatus == "readytoreview") || (has_capability('local/questions:canchangestatus', $systemcontext) && $reviewerstatus == "readytoreview")){
            $qreviewerstatuschange = true;
           }else{
            $qreviewerstatuschange = false;
            }
            $statereviewer = ($reviewerstatus == "publish" || $reviewerstatus == "reject");
            if((is_siteadmin() && $statereviewer) || (has_capability('local/questions:qreviewer',$systemcontext) && $statereviewer) || (has_capability('local/questions:qmanager',$systemcontext) && $statereviewer) || (has_capability('local/questions:testcenterexpert',$systemcontext) && $statereviewer) || (has_capability('local/questions:canchangestatus', $systemcontext) && $statereviewer)){
            $changetodraftstatus = true;
            $qcreaterstatuschange = false;
           }else{
            $changetodraftstatus = false;
            }

            if((is_siteadmin() && $reviewerstatus == "underreview" && $reviewerstatus != "publish") || (has_capability('local/questions:qreviewer',$systemcontext) && $reviewerstatus == "underreview" && $reviewerstatus !="publish") || (has_capability('local/questions:qmanager',$systemcontext) && $reviewerstatus == "underreview" && $reviewerstatus !="publish") || (has_capability('local/questions:testcenterexpert',$systemcontext) && $reviewerstatus == "underreview" && $reviewerstatus !="publish") && (has_capability('local/questions:canchangestatus', $systemcontext) && $reviewerstatus == "underreview" && $reviewerstatus !="publish")){
                $qreviewerstatusdraft = true;
            }else{
                $qreviewerstatusdraft = false;
            }
            // if(is_siteadmin() || (has_capability('local/questions:canreviewself',$systemcontext) && $questions->createdby == $USER->id)){
            if (is_siteadmin() || has_capability('local/questions:canreviewself',$systemcontext) || (!has_capability('local/questions:canreviewself',$systemcontext) && $questions->createdby != $USER->id)) {
                $canreviewself = true;
                $canreviewselfstatus = true;
            // }else if(is_siteadmin() || (!has_capability('local/questions:canreviewself',$systemcontext) && $questions->createdby != $USER->id)){
            // $canreviewself = true;
            // $canreviewselfstatus = true;
            } else {
                $canreviewself = false;
                $canreviewselfstatus = false;
            }

            //$question2 =  $quba->render_question($slot, $options, $displaynumber).$dropdown;
            $question2 =  $quba->render_question($slot, $options);
            if($reviewer->qstatus && $reviewer->reviewdby){
              $questionstatusname = get_string($reviewer->qstatus, 'local_questions');
            }else{
               $questionstatusname =  get_string('draft', 'local_questions');
            }
            $qcategory = $qinfo->category.','.$systemcontext->id;

           $action_icons = false;
           $qreviewstatussql = "SELECT qstatus 
                                FROM {local_qb_questionreview} q 
                                WHERE q.questionid = $question->id 
                                AND q.questionbankid= $qinfo->category";       
            $qreviewstatus = $DB->get_field_sql($qreviewstatussql);
            if(is_siteadmin()
                || (has_capability('local/questions:qcreater',$systemcontext) && $qreviewstatus != 'publish')
                || (has_capability('local/questions:qreviewer',$systemcontext) && $qreviewstatus != 'publish')
                || (has_capability('local/questions:qmanager',$systemcontext) && $qreviewstatus != 'publish')
                || (has_capability('local/questions:testcenterexpert',$systemcontext) && $qreviewstatus != 'publish')
                || has_capability('local/questions:caneditanystatus', $systemcontext)
                || has_capability('local/questions:canchangestatus', $systemcontext)){
                $action_icons = true;
                $edit_res = $this->edit_question_url($question->id);
            }
            // if(is_siteadmin() || has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext) && $qreviewstatus != 'publish' && !has_capability('local/questions:qcreater',$systemcontext)){
            if(is_siteadmin() || has_capability('local/questions:candeleteanystatus', $systemcontext)){
                $returnurl = "/local/questions/questionbank_view.php?courseid=1&cat=".$qcategory;
                $deletequestionurl =  new moodle_url('/question/bank/deletequestion/delete.php');
                $deleteparams = array(
                    'deleteselected' => $question->id,
                    'courseid' => 1,
                    'q' . $question->id => 1,
                    'sesskey' => sesskey(),
                    'returnurl'=>"/local/questions/questionbank_view.php?&courseid=1&cat=".$qcategory,
                    'deleteall'=>1
                );
                $url = new moodle_url($deletequestionurl,$deleteparams);

                $delete_res = $url;
            }
            $returnurl = "/local/questions/questionbank_view.php?courseid=1&cat=$qinfo->category&category=$qinfo->category";
            $duplicate_params = [
                'returnurl' => $returnurl,
                'id' => $question->id,
                'courseid' => 1,
                'makecopy' => true
            ];
            $duplicate_url = '';
            $questionpreviewurl =  new moodle_url('/question/bank/previewquestion/preview.php');
            $questionpreviewparams = array(
                    'id' => $question->id,
                    'courseid' => 1,
                    'sesskey' => sesskey()
                     );
            $questionpreview = new moodle_url($questionpreviewurl,$questionpreviewparams);
            if(is_siteadmin() || has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext) || has_capability('local/questions:qmanager',$systemcontext)){
            $adminstatus=true;
            }else{
                $adminstatus=false;
            }
            $getdifficultylevelsql="SELECT difficulty_level 
                                    FROM  {local_questions_courses} 
                                    WHERE questionid = $question->id";
            $getdifficultylevel = $DB->get_field_sql($getdifficultylevelsql);
            if($getdifficultylevel == 1 ){
                $difficultylevel= get_string('high','customfield_difficultylevel');
            }else if($getdifficultylevel == 2){
                $difficultylevel= get_string('medium','customfield_difficultylevel');
            }else if($getdifficultylevel == 3){
                $difficultylevel = get_string('low','customfield_difficultylevel');
            }else{
                $difficultylevel = false;
            }
            $markssql = "SELECT defaultmark,qtype 
                         FROM  {question} 
                         WHERE id = $question->id";
            $marks = $DB->get_field_sql($markssql);
            $marks=round($marks,2);
            $qtypesql = "SELECT qtype,defaultmark 
                         FROM  {question} 
                         WHERE id = $question->id";
            $qtypename = $DB->get_field_sql($qtypesql);
            switch($qtypename){
            case "multichoice":
            $qtype = get_string('multichoice', 'local_questions');
            break;
            case "truefalse":
            $qtype = get_string('truefalse', 'local_questions');
            break;
            case "match":
            $qtype = get_string('match', 'local_questions');
            break;
            case "numerical":
            $qtype = get_string('numerical', 'local_questions');
            break;
            case "essay":
            $qtype = get_string('essay', 'local_questions');
            break;
            case "description":
            $qtype = get_string('description', 'local_questions');
            break;
            case "multianswer":
            $qtype = get_string('multianswer', 'local_questions');
            break;
            case "gapselect":
            $qtype = get_string('gapselect', 'local_questions');
            break;
            }

            $sql = "SELECT courseid,topicid 
                    FROM {local_questions_courses}  
                    WHERE questionid =". $questions->id;
            $gettopicnames= $DB->get_record_sql($sql);
            if($gettopicnames->topicid){
                $topicnamesql = "SELECT id, name
                                 FROM {local_units}  
                                 WHERE id = $gettopicnames->topicid";                
                $topicname = $DB->get_record_sql($topicnamesql);
                if($topicname){
                $topicnames = $topicname->name;
                }
                else{
                  $topicnames=false;     
                }
            }else{
            $topicnames=false;    
            }
            $rejectreasonexist = false;
            $params = [];
            $params['qid'] = $questions->id;
            $params['qbankeid'] = $questions->questionbankentryid;
            $rejectreasonexistsql = "SELECT *
                                       FROM {local_rejected_questions}
                                      WHERE questionid = :qid
                                        AND questionbankentryid = :qbankeid";
            $rejectreasonexist = $DB->record_exists_sql($rejectreasonexistsql, $params);
            $data = [
                'status'=> $questionstatusname,
                'questionid'=>$questions->id,
                'questionname'=>$questions->name,
                'qcreaterstatuschange'=>$qcreaterstatuschange,
                'editdeleteenable' => $editdeleteenable,
                'qreviewerstatuschange'=>$qreviewerstatuschange,
                'qreviewerstatusdraft'=>$qreviewerstatusdraft,
                'changetodraftstatus'=>$changetodraftstatus,
                'canreviewself' =>$canreviewself,
                'canreviewselfstatus'=>$canreviewselfstatus,
                'statuschange'=>$statuschange,
                'qbid'=>$qinfo->category,
                'question' => $question2,
                "action_icons" => $action_icons,
                "edit" =>$edit_res,
                "delete" =>$delete_res,
                "duplicate" =>$duplicate_url,
                "adminstatus"=>$adminstatus,
                "questionbankentryid" => $questions->questionbankentryid,
                "difficultylevel"=>$difficultylevel,
                "marks"=>$marks,
                "qtype"=>$qtype,
                "questionpreview" => $questionpreview,
                "topicname"=>$topicnames,
                "qnumber"=>$questions->idnumber,
                "rejectreasonexist" => $rejectreasonexist,
                // "rejecteddata" => $rejecteddata,
            ];
            $res .= $OUTPUT->render_from_template('local_questions/questions_display',['displayquestions'=>$data]); 
            if (!empty($this->cm->id)) {
                $this->returnparams['cmid'] = $this->cm->id;
            }
            if (!empty($this->course->id)) {
                $this->returnparams['courseid'] = $this->course->id;
            }
            if (!empty($this->returnurl)) {
                $this->returnparams['returnurl'] = $this->returnurl;
            }
            $experts ='';
    }
     if(empty($res)){
     echo $OUTPUT->render_from_template('local_questions/questions_display',['displayquestions'=>$data]);
     }
        echo $res;
        echo \html_writer::end_tag('div');

    }
    public function get_ques_statuses(){
        $systemcontext = context_system::instance();
        $qstatus = [
            'draft' => get_string('draft', 'local_questions'),
            'underreview' => get_string('underreview', 'local_questions'),
            'readytoreview' => get_string('readytoreview', 'local_questions'),
            'reject' => get_string('reject', 'local_questions'),
            'publish' => get_string('publish', 'local_questions')
        ];
        // $splitData =statusinfo($qstatus);
        // return $splitData;
    }
      public function statusinfo($data) {
        $x = 0;//Counter to ensure accuracy
        //Array of Keys
        $retArray = array();//Array of Values
      foreach($data as $key => $value)
      { 
        $ret = new \stdClass();
        $ret->key = $key;
        $ret->value = $value;
        $retArray[] = $ret;
        //$x++;
      }

      return $retArray;
    }

    protected function build_query(): void {
        global $USER;
        // Get the required tables and fields.
        $systemcontext = context_system::instance();
        $joins = [];
        $fields = ['qv.status', 'qc.id as categoryid', 'qv.version', 'qv.id as versionid', 'qbe.id as questionbankentryid'];
        if (!empty($this->requiredcolumns)) {
            foreach ($this->requiredcolumns as $column) {
                $extrajoins = $column->get_extra_joins();
                foreach ($extrajoins as $prefix => $join) {
                    if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                        throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                    }
                    $joins[$prefix] = $join;
                }
                $fields = array_merge($fields, $column->get_required_fields());
            }
        }

        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            //$sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
            $sorts[] = " q.id DESC ";
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $tests = ['q.parent = 0', $latestversion];
        $this->sqlparams = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        $joins['um'] ="JOIN {user} um ON um.id = q.modifiedby";
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        if(!is_siteadmin() && has_capability('local/questions:qcreater',$systemcontext)){
            $sql.= " AND q.createdby = $USER->id";
         }
         // if(!is_siteadmin() && has_capability('local/questions:testcenterexpert',$systemcontext)){
         //    $sql.= " AND q.createdby = $USER->id";
         // }
         //  if(!is_siteadmin() && has_capability('local/questions:qmanager',$systemcontext)){
         //    $sql.= " AND q.createdby = $USER->id";
         // }

         /* Dec 12 Changes For question review filter starts*/
         //if(!is_siteadmin() && (has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:qmanager',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext) || has_capability('local/questions:publishedquestions',$systemcontext))){
            $sql.= " LEFT JOIN {local_qb_questionreview} lqqr ON lqqr.questionid = q.id ";
            $sql.= " LEFT JOIN {question_versions} quv ON quv.questionid = q.id ";
            $sql.= " LEFT JOIN {question_bank_entries} qbes ON quv.questionbankentryid = qbes.id ";
        // }
            /* Dec 12 Changes For question review filter ends*/
        if (!empty($this->filterparams)) {
           $sql.= " LEFT JOIN {local_questions_courses} lqc ON lqc.questionid = q.id  WHERE 1=1 ";
        }
        if(!is_siteadmin() && (has_capability('local/questions:qreviewer',$systemcontext) || has_capability('local/questions:qmanager',$systemcontext) || has_capability('local/questions:testcenterexpert',$systemcontext) )){
            $sql.= " AND (lqqr.qstatus='publish' OR lqqr.qstatus= 'readytoreview' OR lqqr.qstatus='underreview' OR lqqr.qstatus='reject' OR lqqr.qstatus = 'draft') ";
            
         }
         if(!is_siteadmin() && has_capability('local/questions:publishedquestions',$systemcontext)){
            $sql.= " AND lqqr.qstatus='publish' ";
         }
         if (!empty($this->filterparams['qstatus'])) {
            $qstatuses = $this->filterparams['qstatus'];
            $sql.= " AND lqqr.qstatus = '$qstatuses' ";
        }
  
          if (!empty($this->filterparams['questionid'])) {

            $questionids = explode(',', $this->filterparams['questionid']);
        
            if(!empty($questionids)){
                $qidquery = array();
                foreach ($questionids as $qids) {
                    $qids= (int)$qids;
                    $qidquery[] = " CONCAT(',',quv.questionbankentryid,',') LIKE CONCAT('%,',$qids,',%') ";
                }
                $qidparamas =implode('OR',$qidquery);
                $sql .= '  AND ('.$qidparamas.') ';
            }
         }
           if (!empty($this->filterparams['qidentifier'])) {
            $qidentifierid = $this->filterparams['qidentifier'];
            $sql.= " AND  qbes.idnumber  = '$qidentifierid' ";
         }
         if (!empty($this->filterparams['goal'])) {
            $goalid = (int) $this->filterparams['goal'];
            $sql.= " AND  lqc.goalid  = $goalid ";
        }
        if (!empty($this->filterparams['board'])) {
            $boardid = (int) $this->filterparams['board'];
            $sql.= " AND  lqc.boardid  = $boardid ";
        }
        if (!empty($this->filterparams['class'])) {
            $classid = (int) $this->filterparams['class'];
            $sql.= " AND  lqc.classid  = $classid ";
        }
         if (!empty($this->filterparams['subject']) && $this->filterparams['subject'] !=1) {
            $subjectid = (int) $this->filterparams['subject'];
            $sql.= " AND  lqc.courseid  = $subjectid ";
        }
         if (!empty($this->filterparams['topic'])) {
            $topicid = (int) $this->filterparams['topic'];
            $sql.= " AND  lqc.topicid  = $topicid ";
        }
         if (!empty($this->filterparams['chapter'])) {
            $chapterid = (int) $this->filterparams['chapter'];
            $sql.= " AND  lqc.chapterid  = $chapterid ";
        }
        if (!empty($this->filterparams['unit'])) {
            $unitid = (int) $this->filterparams['unit'];
            $sql.= " AND  lqc.unitid  = $unitid ";
        }
        if (!empty($this->filterparams['concept'])) {
            $conceptid = (int) $this->filterparams['concept'];
            $sql.= " AND  lqc.conceptid  = $conceptid ";
        }
        if (!empty($this->filterparams['source'])) {
            $sourceid = (int) $this->filterparams['source'];
            $sql.= " AND  lqc.source  = $sourceid ";
        }
        if (!empty($this->filterparams['difficulty'])) {
            $difficultyid = (int) $this->filterparams['difficulty'];
            $sql.= " AND  lqc.difficulty_level  = $difficultyid ";
        }
        if (!empty($this->filterparams['cognitive'])) {
            $cognitiveid = (int) $this->filterparams['cognitive'];
            $sql.= " AND  lqc.cognitive_level  = $cognitiveid ";
        }
        if(!empty($this->filterparams['uploadfrom'])){
            $sql .= " AND lqc.timecreated >= ".$this->filterparams['uploadfrom']." ";
        }
        if(!empty($this->filterparams['uploadto'] )){
            $sql .=" AND lqc.timecreated <= ".($this->filterparams['uploadto']+86400)." ";
        }
        $condition = (!empty($this->filterparams)) ? ' AND ' : ' WHERE ';
        $sql .= $condition . implode(' AND ', $tests);
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
       
    }

    protected function load_page_questions($page, $perpage): \moodle_recordset {
        global $DB;

        $questions = $DB->get_recordset_sql($this->loadsql, $this->sqlparams, $page * $perpage, $perpage);
        if (empty($questions)) {
            $questions->close();
            // No questions on this page. Reset to page 0.
            $questions = $DB->get_recordset_sql($this->loadsql, $this->sqlparams, 0, $perpage);
        }
        return $questions;
    }


}
