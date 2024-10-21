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
 * local_units
 * @package local_units
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/local/questions/lib.php');
require_once($CFG->dirroot . '/local/units/filters_form.php');
global $CFG, $PAGE, $OUTPUT, $DB;
$PAGE->requires->jquery();
require_login();
$goalid      = optional_param('goalid','', PARAM_INT);
$boardid      = optional_param('boardid', '', PARAM_INT);
$classid      = optional_param('classid', '', PARAM_INT);
$subjectid      = optional_param('subjectid', '', PARAM_INT);
$topicid      = optional_param('topicid', '', PARAM_INT);
$systemcontext = \context_system::instance();
require_capability('local/questions:questionhierarchy', $systemcontext);
$PAGE->set_url(new moodle_url('/local/units/index.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('questionshierarchy', 'local_units'));
$PAGE->set_heading(get_string('questionshierarchy', 'local_units'));
$PAGE->navbar->add(get_string('questionshierarchy', 'local_units'));
echo $OUTPUT->header();

////Filter Form Starts
$email        = null;
$filterlist = array('goal','board','classes','courses');
$unitsrender = $PAGE->get_renderer('local_units');

$pageurl = new moodle_url('/local/units/index.php');
$PAGE->set_url($pageurl);
$mform = new filters_form($pageurl->out(), array('filterlist'=>$filterlist,'filterparams' => $filterdataparams, 'action' => 'action'),'get');
if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else {
    $filterdata =  $mform->get_data();
    if($filterdata){
    $collapse = false;
    } else{
    $collapse = true;
    }
  if($filterdata){
  $classid =  !empty($filterdata->class) ? $filterdata->class : null;
  $goalid =  !empty($filterdata->goal) ? $filterdata->goal : null;
  $boardid =  !empty($filterdata->board) ? $filterdata->board : null;
  $subjectid =  !empty($filterdata->subject) ? $filterdata->subject : null;

  if($goalid){
  $pageurl->param('goalid', $goalid);  
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
  redirect($pageurl->out());
 }
  $filterparams = array(
  'goal' =>$goalid,
  'board' =>$boardid,
  'class' => $classid, 
  'subject' => $subjectid, 
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
$unitsdata = (new local_units\controller)->get_units();
$filterdataparams = $unitsrender->get_units_view($filterparams,true);
$filtercontent = $OUTPUT->render_from_template('theme_horizon/global_filter', $filterdataparams);
echo $OUTPUT->render_from_template('local_units/form', ['filter_content' => $filtercontent]);
echo $unitsrender->get_units_view($filterparams);
echo $OUTPUT->footer();
