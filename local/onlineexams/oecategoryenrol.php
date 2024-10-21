<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package local_onlineexams
 * @subpackage local_onlineexams
 */

ini_set('memory_limit', '-1');
define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once($CFG->dirroot . '/local/onlineexams/lib.php');
require_once($CFG->dirroot . '/local/onlineexams/category_filters_form.php');
require_once($CFG->libdir . '/accesslib.php');
global $CFG, $DB, $USER, $PAGE, $OUTPUT, $SESSION;

use context_coursecat;
use context_system;

$view = optional_param('view', 'page', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$lastitem = optional_param('lastitem', 0, PARAM_INT);
$countval = optional_param('countval', 0, PARAM_INT);
$categoryid      = optional_param('id', 0, PARAM_INT);
$roleid       = optional_param('roleid', -1, PARAM_INT);

if ($categoryid) {
  // Allow where category equals to id and idnumber yearbookv2.
  $categoryidexist = $DB->record_exists('course_categories', ['id' => $categoryid/*, 'idnumber' => 'yearbookv2'*/]);
  if ($categoryidexist) {
    $course_category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
  } else {
    throw new moodle_exception(get_string('invalidcategoryid', 'local_onlineexams'));
  }
} else {
  throw new moodle_exception(get_string('categoryidnotempty', 'local_onlineexams'));
}

$submit_value = optional_param('submit_value', '', PARAM_RAW);
$add = optional_param('add', array(), PARAM_RAW);
$remove = optional_param('remove', array(), PARAM_RAW);
$sesskey = sesskey();

$categorycontext =  context_system::instance();;
$categoty_context = context_coursecat::instance($categoryid);
require_login();

$canenrol = has_capability('local/onlineexams:enrol', $categorycontext);

require_capability('local/onlineexams:enrol', $categorycontext);
require_capability('local/onlineexams:unenrol', $categorycontext);
require_capability('local/onlineexams:manage', $categorycontext);

if ($roleid < 0) {
  $roleid = $instance->roleid;
}

if (!$enrol_manual = enrol_get_plugin('manual')) {
  throw new coding_exception('Can not instantiate enrol_manual');
}

$roleid = $DB->get_field('role', 'id', ['archetype' => 'student']);

$PAGE->set_context($context);
$PAGE->set_url('/local/onlineexams/oecategoryenrol.php', array('id' => $categoryid));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('manage_onlineexams', 'local_onlineexams'), new moodle_url('/local/onlineexams/index.php'));
$PAGE->navbar->add(get_string('usercategoryenrolments', 'local_onlineexams'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js_call_amd('local_onlineexams/cardPaginate', 'load', array());
$PAGE->set_title($enrol_manual->get_instance_name($instance));
$data_submitted = data_submitted();

if (!$add && !$remove) {
  $PAGE->set_heading($course_category->name);
}

echo $OUTPUT->header();

$datasubmitted = data_submitted();
$filterlist = array('user', 'email', 'phone');
$filterparams = array('options' => null, 'dataoptions' => null);
$mform = new category_filters_form($PAGE->url, array('filterlist' => $filterlist, 'categoryid' => $categoryid, 'filterparams' => $filterparams, 'action' => 'user_enrolment') + (array)$datasubmitted);

$email        = null;
$uname        = null;
$phone        = null;

if ($mform->is_cancelled()) {
  redirect($PAGE->url);
} else {


  $filterdata =  $mform->get_data();
  if ($filterdata) {
    $collapse = false;
    $show = 'show';
  } else {
    $collapse = true;
    $show = '';
  }

  $email = !empty($filterdata->email) ? implode(',', (array)$filterdata->email) : null;
  $uname = !empty($filterdata->fullname) ? implode(',', (array)$filterdata->fullname) : null;
  $phone = !empty($filterdata->phone) ? implode(',', (array)$filterdata->phone) : null;
}
$options = array('context' => $context->id, 'categoryid' => $categoryid, 'email' => $email,  'fullname' => $uname, 'phone' => $phone);

echo '<a class="btn-link btn-sm" title="' . get_string('filter') . '" href="javascript:void(0);" data-toggle="collapse" data-target="#local_learningplanenrol-filter_collapse" aria-expanded="false" aria-controls="local_learningplanenrol-filter_collapse">
        <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
      </a>';
echo  '<div class="collapse ' . $show . '" id="local_learningplanenrol-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
$mform->display();
echo        '</div>
        </div>';

$dataobj = $categoryid;
$fromuserid = $USER->id;
if (($add) && (confirm_sesskey())) {
  $type = 'onlineexam_enrol';
  if ($submit_value == "Add_All_Users") {
    $options = (array)json_decode($_REQUEST["options"], false);
    $userstoassign = category_enrol_users('data', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);
  } else {
    $userstoassign = $add;
  }
  if (!empty($userstoassign)) {
    $capabilities = ['enrol/manual:manage', 'enrol/manual:enrol'];
    $loggedinroleid = $USER->access['rsw']['currentroleinfo']['roleid'];

    $progress = 0;
    $progressbar = new \core\progress\display_if_slow(get_string('enrollusers', 'local_onlineexams', $course_category->name));
    $progressbar->start_html();
    $progressbar->start_progress('', count($userstoassign) - 1);

    foreach ($userstoassign as $key => $adduser) {
      $progressbar->progress($progress);
      $progress++;
      $timeend = 0;
      $timestart = 0;

      $userenroltocategory = role_assign($roleid, $adduser, $categoty_context->id);
      $category = $DB->get_record('course_categories', array('id' => $dataobj));
      $user = core_user::get_user($adduser);
    }
    $progressbar->end_html();
    $result = new stdClass();
    $result->changecount = $progress;
    $result->category = $category->name;

    echo $OUTPUT->notification(get_string('enrollcourseuserssuccess', 'local_onlineexams', $result), 'success');
    $button = new single_button($PAGE->url, get_string('click_continue', 'local_onlineexams'), 'get', true);
    $button->class = 'continuebutton';
    echo $OUTPUT->render($button);
    echo $OUTPUT->footer();
    die();
  }
}
if (($remove) && (confirm_sesskey())) {
  $type = 'onlineexam_unenroll';
  if ($submit_value == "Remove_All_Users") {
    $options = (array)json_decode($_REQUEST["options"], false);
    $userstounassign = category_unenrol_users('data', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);
  } else {
    $userstounassign = $remove;
  }
  if (!empty($userstounassign)) {
    $capabilities = ['enrol/manual:manage', 'enrol/manual:unenrol'];
    $loggedinroleid = $USER->access['rsw']['currentroleinfo']['roleid'];

    $progress = 0;
    $progressbar = new \core\progress\display_if_slow(get_string('un_enrollusers', 'local_onlineexams', $course_category->name));
    $progressbar->start_html();
    $progressbar->start_progress('', count($userstounassign) - 1);

    foreach ($userstounassign as $removeuser) {
      $progressbar->progress($progress);
      $progress++;

      $manual =  role_unassign($roleid, $removeuser, $categoty_context->id);
      $user = core_user::get_user($removeuser);
    }
    $category = $DB->get_record('course_categories', array('id' => $dataobj));
    $progressbar->end_html();

    $result = new stdClass();
    $result->changecount = $progress;
    $result->category = $course_category->name;
    echo $OUTPUT->notification(get_string('unenrollcategoryuserssuccess', 'local_onlineexams', $result), 'success');
    $button = new single_button($PAGE->url, get_string('click_continue', 'local_onlineexams'), 'get', true);
    $button->class = 'continuebutton';
    echo $OUTPUT->render($button);
    die();
  }
}

$unenrol_users = category_unenrol_users('data', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);

$enrol_users = category_enrol_users('data', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);

$select_from_users_total = category_unenrol_users('count', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);

$select_to_users_total = category_enrol_users('count', $categoty_context->id, $options, $offset1 = -1, $perpage = 50, $countval);

$content = '<div class="bootstrap-duallistbox-container">';
$content .= '<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-5 col-12 pull-left">
  <input type="hidden" name="id" value="' . $categoryid . '"/>
  <input type="hidden" name="sesskey" value="' . sesskey() . '"/>
  <input type="hidden" name="options"  value=\'' . json_encode($options) . '\' />
  <label>' . get_string('enrolled_users', 'local_onlineexams', $select_to_users_total) . '</label>' . $select_all_not_enrolled_users;
$content .= '<select multiple="multiple" name="remove[]" id="bootstrap-duallistbox-selected-list_duallistbox_courses_users" class="dual_select">';
foreach ($enrol_users as $key => $select_from_user) {
  $content .= "<option value='$key'>$select_from_user</option>";
}

$content .= '</select>';
$content .= '</div><div class="box3 col-md-2 col-12 pull-left actions"><button type="submit" class="custom_btn btn remove btn-default" disabled="disabled" title="' . get_string('remove_users', 'local_onlineexams') . '" name="submit_value" value="Remove Selected Users" id="user_unassign_all"/>
  ' . get_string('remove_selected_users', 'local_onlineexams') . '
  </button></form>

  ';

$content .= '<form  method="post" name="form_name" id="user_un_assign" class="form_class" ><button type="submit" class="custom_btn btn move btn-default" disabled="disabled" title="' . get_string('add_users', 'local_onlineexams') . '" name="submit_value" value="Add Selected Users" id="user_assign_all" />
  ' . get_string('add_selected_users', 'local_onlineexams') . '
  </button></div><div class="box1 col-md-5 col-12 pull-left">
  <input type="hidden" name="id" value="' . $categoryid . '"/>
  <input type="hidden" name="sesskey" value="' . sesskey() . '"/>
  <input type="hidden" name="options"  value=\'' . json_encode($options) . '\' />
  <label> ' . get_string('availablelist', 'local_onlineexams', $select_from_users_total) . '</label>' . $select_all_enrolled_users;
$content .= '<select multiple="multiple" name="add[]" id="bootstrap-duallistbox-nonselected-list_duallistbox_courses_users" class="dual_select">';

foreach ($unenrol_users as $key => $select_to_user) {
  $content .= "<option value='$key'>$select_to_user</option>";
}

$content .= '</select>';
$content .= '</div></form>';
$content .= '</div>';
if ($course_category) {


  $select_div = '<div class="row d-block">
  <div class="w-100 pull-left">' . $content . '</div>
</div>';
  echo $select_div;
  $myJSON = json_encode($options);
  echo "<script language='javascript'>

$( document ).ready(function() {
$('#select_remove').click(function() {
$('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', true);
$('.box3 .remove').prop('disabled', false);
$('#user_unassign_all').val('Remove_All_Users');

$('.box3 .move').prop('disabled', true);
$('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', false);
$('#user_assign_all').val('Add Selected Users');

});
$('#remove_select').click(function() {
$('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', false);
$('.box3 .remove').prop('disabled', true);
$('#user_unassign_all').val('Remove Selected Users');
});
$('#select_add').click(function() {
$('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', true);
$('.box3 .move').prop('disabled', false);
$('#user_assign_all').val('Add_All_Users');

$('.box3 .remove').prop('disabled', true);
$('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', false);
$('#user_unassign_all').val('Remove Selected Users');

});
$('#add_select').click(function() {
$('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', false);
$('.box3 .move').prop('disabled', true);
$('#user_assign_all').val('Add Selected Users');
});
$('#bootstrap-duallistbox-selected-list_duallistbox_courses_users').on('change', function() {
if(this.value!=''){
$('.box3 .remove').prop('disabled', false);
$('.box3 .move').prop('disabled', true);
}
});
$('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users').on('change', function() {
if(this.value!=''){
$('.box3 .move').prop('disabled', false);
$('.box3 .remove').prop('disabled', true);
}
});
jQuery(
function($)
{
$('.dual_select').bind('scroll', function()
{
if(Math.round($(this).scrollTop() + $(this).innerHeight())>=$(this)[0].scrollHeight)
{
  var get_id=$(this).attr('id');
  if(get_id=='bootstrap-duallistbox-selected-list_duallistbox_courses_users'){
      var type='remove';
      var total_users=$select_from_users_total;
  }
  if(get_id=='bootstrap-duallistbox-nonselected-list_duallistbox_courses_users'){
      var type='add';
      var total_users=$select_to_users_total;

  }
  var count_selected_list=$('#'+get_id+' option').length;

  var lastValue = $('#'+get_id+' option:last-child').val();
  var countval = $('#'+get_id+' option').length;
if(count_selected_list<total_users){

      var selected_list_request = $.ajax({
          method: 'GET',
          url: M.cfg.wwwroot + '/local/onlineexams/oecategoryenrol.php',
          data: {id:'$categoryid',sesskey:'$sesskey', type:type, view:'ajax',countval:countval, options: $myJSON},
          dataType: 'html'
      });
      var appending_selected_list = '';
      selected_list_request.done(function(response){
      response = jQuery.parseJSON(response);

      $.each(response, function (index, data) {

          appending_selected_list = appending_selected_list + '<option value=' + index + '>' + data + '</option>';
      });
      $('#'+get_id+'').append(appending_selected_list);
      });
  }
}
})
}
);

});
</script>";
}

$backurl = new moodle_url('/local/onlineexams/index.php');
$continue = '<div class="col-md-12 pull-left text-right mt-6">';
$continue .= $OUTPUT->single_button($backurl, get_string('continue'));
$continue .= '</div>';
echo $continue;
echo $OUTPUT->footer();
