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


require_once('../../config.php');
require_once($CFG->dirroot . '/local/onlineexams/filters_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$jsonparam    = optional_param('jsonparam', '', PARAM_RAW);
//$examtype = optional_param('examtype', 'online_exams', PARAM_RAW);
$formattype = optional_param('formattype', 'table', PARAM_TEXT);
$name = optional_param('testname', '', PARAM_INT);
$type = optional_param('testtype', '', PARAM_INT);
$formattype_url = 'table';
$display_text = get_string('listtype','local_onlineexams');
$display_icon = get_string('listicon','local_onlineexams');

// if ($examtype == 'year_book') {
//     $examtype_url = 'online_exams';
//     $display_text1 = get_string('gotoonline_exams','local_onlineexams');
//     $display_icon1 = get_string('onlineicon','local_onlineexams');
// } else {
//     $examtype_url = 'year_book';
//     $display_text1 = get_string('gotoyearbook','local_onlineexams');
//     $display_icon1 = get_string('yearicon','local_onlineexams');
// }
require_login();
global $DB;
$context = context_system::instance();
// if(!has_capability('local/onlineexams:view', $context) && !has_capability('local/onlineexams:manage', $context) ){
//     print_error("You don't have permissions to view this page.");
// }
$PAGE->set_pagelayout('standard');

$PAGE->set_context($context);
$PAGE->set_url('/local/onlineexams/index.php');
if ( $examtype == 'online_exams' ) {
    $PAGE->set_title(get_string('onlineexams','local_onlineexams'));
    $PAGE->set_heading(get_string('manage_onlineexams','local_onlineexams'));
} else {
    if(is_siteadmin() ||(has_capability('moodle/course:create', $context))){
        $PAGE->set_title(get_string('manage_tests','local_onlineexams'));
        $PAGE->set_heading(get_string('manage_tests','local_onlineexams'));
    }else{
        $PAGE->set_title(get_string('mytests','local_onlineexams'));
        $PAGE->set_heading(get_string('mytests','local_onlineexams'));
    }
    }
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_onlineexams/onlineexamsAjaxform', 'load', array());
$PAGE->requires->js_call_amd('local_onlineexams/onlineexams', 'load', array());
$PAGE->requires->js_call_amd('local_onlineexams/renderusers', 'load', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('manage_tests','local_onlineexams'));

$renderer = $PAGE->get_renderer('local_onlineexams');
$filterparams = $renderer->get_catalog_onlineexams(true,$formattype);
    $formdata = new stdClass();
    $formdata->testname = $name;
    $formdata->testname = $type;
$extended_menu_links = '';  
$extended_menu_links = '<div class="course_contextmenu_extended">
            <ul class="course_extended_menu_list">';
$thisfilters = array('name','type');
$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams),'post', '', null, true, null);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/onlineexams/index.php');
} else{
    $filterdata =  $mform->get_data();
    if($filterdata){
        $collapse = false;
    } else{
        $collapse = true;
    }
}
if(empty($filterdata) && !empty($jsonparam)){
    $filterdata = json_decode($jsonparam);
    foreach($thisfilters AS $filter){
        if(empty($filterdata->$filter)){
            unset($filterdata->$filter);
        }
    }
    $mform->set_data($filterdata);
}
if(!empty($name)|| !empty($type)){   
    $formdata = new stdClass();
    $formdata->testname[] = $name;
    $formdata->testname[] = $type;
    $mform->set_data($formdata);
echo '<span id="global_filter" class="hidden" data-filterdata='.json_encode($formdata).'></span>';

}         
$categoryid = $DB->get_field('course_categories', 'id', ['idnumber' => 'yearbookv2']);
if(is_siteadmin() ||(
        has_capability('moodle/course:create', $context)&& has_capability('moodle/course:update', $context))){
        $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                                    <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_newonlineexams','local_onlineexams').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_onlineexams/onlineexamsAjaxform\').init({contextid:1, component:\'local_onlineexams\', callback:\'custom_onlineexams_form\', form_status:0, plugintype: \'local\', pluginname: \'onlineexams\'}) })(event)">
                                        <span class="createicon">
                                        <i class="icon fa fa-desktop"></i>
                                        <i class="fa fa-plus createiconchild" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </div></li>';
if($categoryid) {

    $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer"><a class="pull-right course_extended_menu_itemlink" href="'.$CFG->wwwroot. '/local/onlineexams/oecategoryenrol.php?id='.$categoryid.'" title="User enrollment"><span class="createicon"> <i class="fa fa-user icon" aria-hidden="true"></i></span></a></div></li>';
}

}

$extended_menu_links .= '
        </ul>
    </div>';

echo $OUTPUT->header();
echo $extended_menu_links;

$display_url = new moodle_url('/local/onlineexams/index.php');
$display_url1 = new moodle_url('/local/onlineexams/index.php');

//    if($examtype_url){
//    // $display_url1->param('examtype',$examtype_url);
//    }
   if($formattype_url){
    $display_url->param('formattype', $formattype_url);
   // $display_url->param('examtype',$examtype); 
   } 

    // $displaytype_div1 = '<div class="col-12 d-inline-block">';
    // $displaytype_div1 .= '<a class="btn btn-outline-secondary pull-left" href="' . $display_url1 . '">';
    // $displaytype_div1 .= '<span class="'.$display_icon1.'"></span>' . $display_text1;
    // $displaytype_div1 .= '</a>';
    // $displaytype_div1 .= '</div>';
    // echo $displaytype_div1;
if(is_siteadmin() ||(has_capability('local/onlineexams:manage', $context))){
    echo '<a class="btn-link btn-sm" data-toggle="collapse" data-target="#local_onlineexams-filter_collapse" aria-expanded="false" aria-controls="local_onlineexams-filter_collapse">
        <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
      </a>';
}
echo  '<div class="collapse '.$show.'" id="local_onlineexams-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
                $mform->display();
echo        '</div>
        </div>';
$filterparams['submitid'] = 'form#filteringform';
$filterparams['filterdata'] = json_encode($formdata);
echo $OUTPUT->render_from_template('local_onlineexams/global_filter', $filterparams);
echo $renderer->get_catalog_onlineexams(false,$formattype);

echo $OUTPUT->footer();
