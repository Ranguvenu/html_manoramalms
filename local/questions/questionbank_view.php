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
use qbank_previewquestion\question_preview_options;
use qbank_editquestion\editquestion_helper;
use local_questions\local\view;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/question/editlib.php');
require_once($CFG->dirroot . '/local/questions/lib.php');
require_once($CFG->dirroot . '/local/questions/filters_form.php');
global $PAGE,$DB;
$PAGE->add_body_class('questioncreation_page');
$lastchanged      = optional_param('lastchanged', -1, PARAM_INT);
$courseid      = optional_param('courseid', 1, PARAM_INT);
//$cat      = optional_param('cat', '3,1', PARAM_RAW);
$qperpage      = optional_param('qperpage', 5, PARAM_INT);
$goalid      = optional_param('goalid','', PARAM_INT);
$boardid      = optional_param('boardid', '', PARAM_INT);
$classid      = optional_param('classid', '', PARAM_INT);
$subjectid      = optional_param('subjectid', '', PARAM_INT);
$topicid      = optional_param('topicid', '', PARAM_INT);
$chapterid      = optional_param('chapterid', '', PARAM_INT);
$unitid      = optional_param('unitid', '', PARAM_INT);
$conceptid      = optional_param('conceptid', '', PARAM_INT);
$source      = optional_param('source', '', PARAM_INT);
$qstatus      = optional_param('qstatus', '', PARAM_RAW);
$cognitive      = optional_param('cognitive', '', PARAM_INT);
$difficulty      = optional_param('difficulty', '', PARAM_INT);
$qid      = optional_param('questionid', '', PARAM_RAW);    
$qidentifier      = optional_param('qidentifier', '', PARAM_RAW);    
$cmid      = optional_param('cmid', '', PARAM_INT);    
$uploadfrom      = optional_param('uploadfrom', '', PARAM_INT); 
$uploadto      = optional_param('uploadto', '', PARAM_INT);
//$cat      = optional_param('cat', -1, PARAM_RAW);
 $systemcontext = context_system::instance();
$pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
        $cat = $pcategory.','.$systemcontext->id;
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('questions', '/local/questions/questionbank_view.php?courseid=1&cat='.$cat);
$pageurl = new moodle_url('/local/questions/questionbank_view.php?courseid='.$courseid.'&cat='.$cat);
$PAGE->set_url($pageurl);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_capability('local/questions:questionallow', $systemcontext);
$PAGE->set_pagelayout('standard');
$title = get_string('viewquestions', 'local_questions');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('pluginname', 'local_questions'));
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_questions/rejectsubmission', 'init', []);
$PAGE->requires->js_call_amd('local_questions/rejectedreasondata', 'init', []);
require_login();
echo $OUTPUT->header();


$PAGE->requires->js_call_amd('local_questions/questionBank', 'init', array(has_capability('local/questions:caneditanystatus', $systemcontext) || has_capability('local/questions:candeleteanystatus', $systemcontext)));
////Filter Form Strats
$email        = null;
$filterlist = array('questionid','qidentifier','goal','board','classes','courses','topics','chapter','unit','concept','difficulty','cognitive','source','qstatus','betweendate');
$filterparams = array('options'=>null, 'dataoptions'=>null);
//$mform = new filters_form($PAGE->url, array('filterlist'=>$filterlist,'filterparams' => $filterparams, 'action' => 'user_enrolment'));
$mform = new filters_form($pageurl->out(), array('filterlist'=>$filterlist,'filterparams' => $filterparams, 'action' => 'user_enrolment'));
if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else {
    $filterdata =  $mform->get_data();
    if($filterdata){
    $collapse = false;
    } else{
    $collapse = true;
    }
  //$search_query = !empty($filterdata->search_query) ? implode(',', $filterdata->search_query) : null;
  if($filterdata){
  $cmid=!empty($filterdata->cmid) ? $filterdata->cmid : null;
  $qid =  !empty($filterdata->questionid) ? $filterdata->questionid : null;
  $qidentifier =  !empty($filterdata->qidentifier) ? $filterdata->qidentifier : null;
  $classid =  !empty($filterdata->class) ? $filterdata->class : null;
  $goalid =  !empty($filterdata->goal) ? $filterdata->goal : null;
  $boardid =  !empty($filterdata->board) ? $filterdata->board : null;
  //$courseid =  !empty($filterdata->course) ? $filterdata->course : 1;
  $subjectid =  !empty($filterdata->subject) ? $filterdata->subject : null;
  $difficulty =  !empty($filterdata->difficulty) ? $filterdata->difficulty : null;
  $cognitive =  !empty($filterdata->cognitive) ? $filterdata->cognitive : null;
  $source =  !empty($filterdata->source) ? $filterdata->source : null;
  $qstatus =  !empty($filterdata->qstatus) ? $filterdata->qstatus : null;
  // $topicid =  !empty($filterdata->topic)  ? implode(',', $filterdata->topic) : null;
  $topicid =  !empty($filterdata->topic)  ? $filterdata->topic : null;
  $chapterid =  !empty($filterdata->chapter)  ? $filterdata->chapter : null;
  $unitid =  !empty($filterdata->unit)  ? $filterdata->unit : null;
  $conceptid =  !empty($filterdata->concept)  ? $filterdata->concept : null;
  $uploadfrom =  !empty($filterdata->uploadfrom) ? $filterdata->uploadfrom : null;
  $uploadto =  !empty($filterdata->uploadto) ? $filterdata->uploadto : null;
  if($goalid){
  $pageurl->param('goalid', $goalid);  
  }
   if($qid){
  $pageurl->param('questionid', $qid);  
  }
   if($qidentifier){
  $pageurl->param('qidentifier', $qidentifier);  
  }
  if($boardid){
   $pageurl->param('boardid', $boardid); 
  }
  if($classid){
  $pageurl->param('classid', $classid);
  }
  if($subjectid){
  $pageurl->param('subjectid', $subjectid);  
  }
  if($topicid){
  $pageurl->param('topicid', $topicid);
  }
  if($chapterid){
  $pageurl->param('chapterid', $chapterid);
  }
  if($unitid){
  $pageurl->param('unitid', $unitid);
  }
  if($conceptid){
  $pageurl->param('conceptid', $conceptid);
  }
  if($cognitive){
  $pageurl->param('cognitive', $cognitive);
  }
  if($difficulty){
  $pageurl->param('difficulty', $difficulty);
  }
  if($source){
  $pageurl->param('source', $source);
  }
   if($qstatus){
  $pageurl->param('qstatus', $qstatus);
  }
  if($uploadfrom){
  $pageurl->param('uploadfrom', $uploadfrom);
  }
  if($uploadto){
  $pageurl->param('uploadto', $uploadto);
  }
  if($cmid){
  $pageurl->param('cmid', $cmid);
  }

  redirect($pageurl->out());
 }
  //$context = context_system::instance();
  $filterparams = array(
  'questionid' =>$qid,
  'qidentifier' =>$qidentifier,
  'goal' =>$goalid,
  'board' =>$boardid,
  'class' => $classid, 
  //'course' => $courseid, 
  'subject' => $subjectid, 
  'topic' => $topicid,
  'chapter' => $chapterid,
  'unit' => $unitid,
  'concept' => $conceptid,
  'difficulty' => $difficulty,
  'cognitive' => $cognitive,
  'source' => $source,
  'qstatus' => $qstatus,
  'uploadfrom' => $uploadfrom ? $uploadfrom: null,
  'uploadto' => $uploadto? $uploadto: null
  );
  $mform->set_data($filterparams);
}
if($goalid){
    $collapse = false;
    $show = 'show';
} else{
    $collapse = true;
    $show = '';
}
echo '<div class="filter_wrap mb-4"><a class="filters_link" href="javascript:void(0);" data-toggle="collapse" data-target="#local_questions-filter_collapse" aria-expanded="false" aria-controls="local_questions-filter_collapse"><span class="mr-3 fs-normal">'
        .get_string('filters').'</span><i class="m-0 fa fa-sliders" aria-hidden="true"></i>
      </a>';
echo  '<div class="collapse '.$show.'" id="local_questions-filter_collapse">
            <div id="filters_form" class="px-5 py-3">';
                $mform->display();
echo        '</div>
        </div></div>';
// print_collapsible_region_start(' ', 'filters_form', ' '.' '.get_string('filters'), false, $collapse);
// $mform->display();
// print_collapsible_region_end();
////Filters Form Ends
$questionbank = new local_questions\local\view($contexts, $thispageurl, $COURSE, $cm ,$filterparams);
$questionbank->enablefilters =false;
$pagevars['qperpage'] = $qperpage;
$pagevars['goalid'] = $goalid;
$pagevars['boardid'] = $boardid;
$pagevars['classid'] = $classid;
// $pagevars['courseid'] = $courseid;
$pagevars['subjectid'] = $subjectid;
$pagevars['topicid'] = $topicid;
$pagevars['chapterid'] = $chapterid;
$pagevars['unitid'] = $unitid;
$pagevars['conceptid'] = $conceptid;
$pagevars['uploadfrom'] = $uploadfrom;
$pagevars['uploadto'] = $uploadto;
$questionbank->display($pagevars, 'questions');
echo $OUTPUT->footer();
