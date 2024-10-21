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
require_once($CFG->dirroot . '/local/onlineexams/filters_form.php');
global $CFG, $DB, $USER, $PAGE, $OUTPUT, $SESSION;

$view = optional_param('view', 'page', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$lastitem = optional_param('lastitem', 0, PARAM_INT);
$countval = optional_param('countval', 0, PARAM_INT);
$enrolid      = required_param('enrolid', PARAM_INT);
$course_id      = optional_param('id', 0, PARAM_INT);
$roleid       = optional_param('roleid', -1, PARAM_INT);
$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$submit_value = optional_param('submit_value', '', PARAM_RAW);
$add = optional_param('add', array(), PARAM_RAW);
$remove = optional_param('remove', array(), PARAM_RAW);
$sesskey = sesskey();
$context = context_course::instance($course->id, MUST_EXIST);


$categorycontext =  context_system::instance();;

require_login();

if ($view == 'ajax') {
  if(is_string($_GET["options"])){
    $options = json_decode($_GET["options"], false);
  }else{
    $options = $_GET["options"];  
  }
  $select_from_users = course_enrolled_users($type, $course_id, $options, false, $offset1 = -1, $perpage = 50, $countval);
  echo json_encode($select_from_users);
  exit;
}

$canenrol = has_capability('local/onlineexams:enrol', $categorycontext);
// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.
// if (!$canenrol) {
// No need to invent new error strings here...
require_capability('local/onlineexams:enrol', $categorycontext);
require_capability('local/onlineexams:unenrol', $categorycontext);
require_capability('local/onlineexams:manage', $categorycontext);

// }

if ($roleid < 0) {
  $roleid = $instance->roleid;
}

if (!$enrol_manual = enrol_get_plugin('manual')) {
  throw new coding_exception('Can not instantiate enrol_manual');
}

$instancename = $enrol_manual->get_instance_name($instance);

$PAGE->set_context($context);
$PAGE->set_url('/local/onlineexams/onlineexamsenrol.php', array('id' => $course_id, 'enrolid' => $instance->id));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('manage_onlineexams', 'local_onlineexams'), new moodle_url('/local/onlineexams/index.php'));
$PAGE->navbar->add(get_string('userenrolments', 'local_onlineexams'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js_call_amd('local_onlineexams/cardPaginate', 'load', array());
$PAGE->set_title($enrol_manual->get_instance_name($instance));
$data_submitted = data_submitted();

if (!$add && !$remove) {
  $PAGE->set_heading($course->fullname);
}

navigation_node::override_active_url(new moodle_url('/local/mass_enroll/mass_enroll.php', array('id' => $course->id)));


echo $OUTPUT->header();
if ($course) {

  require_once($CFG->dirroot . '/local/onlineexams/filters_form.php');
  $datasubmitted = data_submitted();
  $filterlist =array('user','email');
  $filterparams = array('options' => null, 'dataoptions' => null);
  $mform = new filters_form($PAGE->url, array('filterlist'=>$filterlist, 'enrolid' => $enrolid, 'courseid' => $course_id, 'filterparams' => $filterparams, 'action' => 'user_enrolment')+(array)$datasubmitted);
 
  $email        = null;
  $uname        = null;

  if($mform->is_cancelled()){
    redirect($PAGE->url);
  }else{


  $filterdata =  $mform->get_data();
  if($filterdata){
    $collapse = false;
    $show = 'show';
  } else{
    $collapse = true;
    $show = '';
  }
  
  $email = !empty($filterdata->email) ? implode(',', (array)$filterdata->email) : null;
  $uname = !empty($filterdata->fullname) ? implode(',', (array)$filterdata->fullname) : null;
}
$options = array('context' => $context->id, 'courseid' => $course_id, 'email' => $email,  'fullname' => $uname);

  echo '<a class="btn-link btn-sm" title="'.get_string('filter').'" href="javascript:void(0);" data-toggle="collapse" data-target="#local_learningplanenrol-filter_collapse" aria-expanded="false" aria-controls="local_learningplanenrol-filter_collapse">
          <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
        </a>';
  echo  '<div class="collapse '.$show.'" id="local_learningplanenrol-filter_collapse">
              <div id="filters_form" class="card card-body p-2">';
                  $mform->display();
  echo        '</div>
          </div>';

  // Create the user selector objects.
  $dataobj = $course_id;
  $fromuserid = $USER->id;
  if ($add and confirm_sesskey()) {
    $type = 'onlineexam_enrol';
    if ($submit_value == "Add_All_Users") {
      $options = (array)json_decode($_REQUEST["options"], false);
      $userstoassign = array_flip(course_enrolled_users('add', $course_id, (array)$options, false, $offset1 = -1, $perpage = -1));
    } else {
      $userstoassign = $add;
    }
    if (!empty($userstoassign)) {
      $capabilities = ['enrol/manual:manage', 'enrol/manual:enrol'];
      $loggedinroleid = $USER->access['rsw']['currentroleinfo']['roleid'];
      if (has_capability('local/onlineexams:enrol', $context) && $roleid && !is_siteadmin()) {
        foreach ($capabilities as $capability) {
          if (!has_capability($capability, $context)) {
            assign_capability($capability, CAP_ALLOW, $loggedinroleid, $context->id, true);
          }
        }
      }
      $progress = 0;
      $progressbar = new \core\progress\display_if_slow(get_string('enrollusers', 'local_onlineexams', $course->fullname));
      $progressbar->start_html();
      $progressbar->start_progress('', count($userstoassign) - 1);

      foreach ($userstoassign as $key => $adduser) {
        $progressbar->progress($progress);
        $progress++;
        $timeend = 0;
        $timestart = 0;

        $enrol_manual->enrol_user($instance, $adduser, $roleid, $timestart, $timeend);
        //$notification = new \local_onlineexams\notification();
        $course = $DB->get_record('course', array('id' => $dataobj));        
      //  $course->costcenter = explode('/',$course->open_path)[1];
        $user = core_user::get_user($adduser);
        // $notificationdata = $notification->get_existing_notification($course, $type);
        // if($notificationdata)
        //     $notification->onlineexams_notification($type, $user, $fromuser, $course);
      }
      $progressbar->end_html();
      $result = new stdClass();
      $result->changecount = $progress;
      $result->course = $course->fullname;
      if (has_capability('local/onlineexams:enrol', $context) && $roleid && !is_siteadmin()) {
        foreach ($capabilities as $capability) {
          unassign_capability($capability, $loggedinroleid, $context->id);
        }
      }
      echo $OUTPUT->notification(get_string('enrolluserssuccess', 'local_onlineexams', $result), 'success');
      $button = new single_button($PAGE->url, get_string('click_continue', 'local_onlineexams'), 'get', true);
      $button->class = 'continuebutton';
      echo $OUTPUT->render($button);
      echo $OUTPUT->footer();
      die();
    }
  }
  if ($remove && confirm_sesskey()) {
    $type = 'onlineexam_unenroll';
    if ($submit_value == "Remove_All_Users") {
      $options = (array)json_decode($_REQUEST["options"], false);
      $userstounassign = array_flip(course_enrolled_users('remove', $course_id, (array)$options, false, $offset1 = -1, $perpage = -1));
    } else {
      $userstounassign = $remove;
    }
    if (!empty($userstounassign)) {
      $capabilities = ['enrol/manual:manage', 'enrol/manual:unenrol'];
      $loggedinroleid = $USER->access['rsw']['currentroleinfo']['roleid'];
      if (has_capability('local/onlineexams:enrol', $context) && $loggedinroleid && !is_siteadmin()) {
        foreach ($capabilities as $capability) {
          if (!has_capability($capability, $context)) {
            assign_capability($capability, CAP_ALLOW, $loggedinroleid, $context->id, true);
          }
        }
      }
      $progress = 0;
      $progressbar = new \core\progress\display_if_slow(get_string('un_enrollusers', 'local_onlineexams', $course->fullname));
      $progressbar->start_html();
      $progressbar->start_progress('', count($userstounassign) - 1);
      foreach ($userstounassign as $key => $removeuser) {
        $progressbar->progress($progress);
        $progress++;
        if ($instance->enrol == 'manual') {
          $manual = $enrol_manual->unenrol_user($instance, $removeuser);
          //\core\session\manager::kill_user_sessions($removeuser);
        }
        $data_self = $DB->get_record_sql("SELECT * FROM {user_enrolments} ue
                    JOIN {enrol} e ON ue.enrolid=e.id
                    WHERE e.courseid={$course_id} and ue.userid=$removeuser");
        $enrol_self = enrol_get_plugin('self');
        if ($data_self->enrol == 'self') {
          $self = $enrol_self->unenrol_user($data_self, $removeuser);
          //\core\session\manager::kill_user_sessions($removeuser);
        }
        $user = core_user::get_user($removeuser);
      }
      $progressbar->end_html();
      $result = new stdClass();
      $result->changecount = $progress;
      $result->course = $course->fullname;
      if (has_capability('local/onlineexams:enrol', $context) && $loggedinroleid && !is_siteadmin()) {
        foreach ($capabilities as $capability) {
          unassign_capability($capability, $loggedinroleid, $context->id);
        }
      }

      echo $OUTPUT->notification(get_string('unenrolluserssuccess', 'local_onlineexams', $result), 'success');
      $button = new single_button($PAGE->url, get_string('click_continue', 'local_onlineexams'), 'get', true);
      $button->class = 'continuebutton';
      echo $OUTPUT->render($button);
      die();
    }
  }

  $select_to_users = course_enrolled_users('add', $course_id, $options, false, $offset = -1, $perpage = 50);
  $select_to_users_total = course_enrolled_users('add', $course_id, $options, true, $offset1 = -1, $perpage = -1);

  $select_from_users = course_enrolled_users('remove', $course_id, $options, false, $offset1 = -1, $perpage = 50);
  $select_from_users_total = course_enrolled_users('remove', $course_id, $options, true, $offset1 = -1, $perpage = -1);

  $select_all_enrolled_users = '&nbsp&nbsp<button type="button" id="select_add" name="select_all" value="Select All" title="' . get_string('select_all', 'local_onlineexams') . '" class="btn btn-default">' . get_string('select_all', 'local_onlineexams') . '</button>';
  $select_all_enrolled_users .= '&nbsp&nbsp<button type="button" id="add_select" name="remove_all" value="Remove All" title="' . get_string('remove_all', 'local_onlineexams') . '" class="btn btn-default"/>' . get_string('remove_all', 'local_onlineexams') . '</button>';

  $select_all_not_enrolled_users = '&nbsp&nbsp<button type="button" id="select_remove" name="select_all" value="Select All" title="' . get_string('select_all', 'local_onlineexams') . '" class="btn btn-default"/>' . get_string('select_all', 'local_onlineexams') . '</button>';
  $select_all_not_enrolled_users .= '&nbsp&nbsp<button type="button" id="remove_select" name="remove_all" value="Remove All" title="' . get_string('remove_all', 'local_onlineexams') . '" class="btn btn-default"/>' . get_string('remove_all', 'local_onlineexams') . '</button>';

  $content = '<div class="bootstrap-duallistbox-container">';
  $content .= '<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-5 col-12 pull-left">
  <input type="hidden" name="id" value="' . $course_id . '"/>
  <input type="hidden" name="enrolid" value="' . $enrolid . '"/>
  <input type="hidden" name="sesskey" value="' . sesskey() . '"/>
  <input type="hidden" name="options"  value=\'' . json_encode($options) . '\' />
  <label>' . get_string('enrolled_users', 'local_onlineexams', $select_from_users_total) . '</label>' . $select_all_not_enrolled_users;
  $content .= '<select multiple="multiple" name="remove[]" id="bootstrap-duallistbox-selected-list_duallistbox_courses_users" class="dual_select">';
  foreach ($select_from_users as $key => $select_from_user) {
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
  <input type="hidden" name="id" value="' . $course_id . '"/>
  <input type="hidden" name="enrolid" value="' . $enrolid . '"/>
  <input type="hidden" name="sesskey" value="' . sesskey() . '"/>
  <input type="hidden" name="options"  value=\'' . json_encode($options) . '\' />
  <label> ' . get_string('availablelist', 'local_onlineexams', $select_to_users_total) . '</label>' . $select_all_enrolled_users;
  $content .= '<select multiple="multiple" name="add[]" id="bootstrap-duallistbox-nonselected-list_duallistbox_courses_users" class="dual_select">';
  foreach ($select_to_users as $key => $select_to_user) {
    $content .= "<option value='$key'>$select_to_user</option>";
  }
  $content .= '</select>';
  $content .= '</div></form>';
  $content .= '</div>';

}

if ($course) {
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
                   //alert('end reached');
                    var selected_list_request = $.ajax({
                        method: 'GET',
                        url: M.cfg.wwwroot + '/local/onlineexams/onlineexamsenrol.php',
                        data: {id:'$course_id',sesskey:'$sesskey', type:type,view:'ajax',countval:countval,enrolid:'$enrolid', options: $myJSON},
                        dataType: 'html'
                    });
                    var appending_selected_list = '';
                    selected_list_request.done(function(response){
                    //console.log(response);
                    response = jQuery.parseJSON(response);
                    //console.log(response);

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
