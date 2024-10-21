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

if (file_exists($CFG->dirroot . '/local/costcenter/lib.php')) {
    require_once($CFG->dirroot . '/local/costcenter/lib.php');
}
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

use \local_onlineexams\form\custom_onlineexams_form as custom_onlineexams_form;
//use \local_courses\form\custom_courseevidence_form as custom_courseevidence_form;


defined('MOODLE_INTERNAL') || die();


/**
 * Serve the new course form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_onlineexams_output_fragment_custom_onlineexams_form($args)
{
    global $DB, $CFG, $PAGE;
    $args = (object) $args;
    $context = $args->context;
    $renderer = $PAGE->get_renderer('local_onlineexams');
    $courseid = $args->courseid;
    $o = '';
    $sql = "SELECT *
              FROM {course_categories}
             WHERE idnumber LIKE :idnumber";
    $yearbookv2category = $DB->get_record_sql($sql, ['idnumber' => 'yearbookv2']);
    if ($yearbookv2category) {
        if ($courseid) {
            $course = get_course($courseid);
            $course = course_get_format($course)->get_course();
            $coursecontext = context_course::instance($courseid);
        }
    } else {
        $sql = "SELECT max(sortorder) lastsortorder
                  FROM {course_categories}";
        $lastsortorder = $DB->get_field_sql($sql);
        $newsort = $lastsortorder / 10000;
        $newsortorder = ($newsort + 1) * 10000;
        $catdata = new stdClass();
        $catdata->name = 'Yearbook v2';
        $catdata->idnumber = 'yearbookv2';
        $catdata->description = '';
        $catdata->descriptionformat = 0;
        $catdata->parent = 0;
        $catdata->sortorder = $newsortorder;
        $catdata->coursecount = 0;
        $catdata->visible = 1;
        $catdata->visibleold = 1;
        $catdata->timemodified = time();
        $catdata->depth = 1;
        $catdata->path = '/'.$newsort + 1;
        $catdata->theme = '';
        $category = $DB->insert_record('course_categories', $catdata);
        $catcontext = context_coursecat::instance($category);
    }

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        if(is_object($serialiseddata) && !empty($serialiseddata)){
        $serialiseddata = serialize($serialiseddata);
        }
        parse_str($serialiseddata, $formdata);
    }
    $get_coursedetails = $DB->get_record('course', array('id' => $course->id));
    if ($get_coursedetails->format == 'singleactivity') {
        $moduleinfoSql = "SELECT q.id, q.attempts,q.timelimit,q.graceperiod,q.overduehandling,q.browsersecurity,q.grademethod, gi.grademax, gi.gradepass ,q.timeopen,q.timeclose
            FROM {quiz} as q 
            JOIN {grade_items} as gi ON gi.iteminstance = q.id AND gi.itemtype ='mod' AND gi.itemmodule = 'quiz' 
            WHERE q.course=:courseid ";
        $moduleinfo = $DB->get_record_sql($moduleinfoSql, array('courseid' => $courseid));
        $maxgrade = round($moduleinfo->grademax, 2);
        $gradepass = round($moduleinfo->gradepass, 2);
        $attempts = $moduleinfo->attempts;
        $course->gradepass = $gradepass;
        $course->grademethod = $moduleinfo->grademethod;
        $course->maxgrade = $maxgrade;
        $course->attempts = $attempts;
        $course->timeopen = $moduleinfo->timeopen;
        $course->timeclose = $moduleinfo->timeclose;
        $course->timelimit = $moduleinfo->timelimit;
        $course->overduehandling =$moduleinfo->overduehandling;
        $course->graceperiod =$moduleinfo->graceperiod;
        $course->browsersecurity = $moduleinfo->browsersecurity;
    }
    if (!empty($course) && empty(array_filter($formdata))) {
        if ($course->open_module == 'year_book') {
            $course->examtype = 1;
        } else if ($course->open_module == 'online_exams') {
            $course->examtype = 0;
        } else {
            $course->examtype = 2;
        }
        // $course->examtype = ($course->open_module == 'year_book' ? 1 : 0);
        $formdata = clone $course;
        $formdata = (array)$formdata;
    }
    // if ($courseid > 0) {
    //     $data = $DB->get_record('course', array('id' => $courseid));
    // }
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true, 'autosave' => false);
    $overviewfilesoptions = course_overviewfiles_options($course);
    if ($courseid) {
        // Add context for editor.
        $editoroptions['context'] = $coursecontext;
        $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
        $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
        if ($overviewfilesoptions) {
            file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
        }
    } else {
        // Editor should respect category context if course context is not set.
        $editoroptions['context'] = $catcontext;
        $editoroptions['subdirs'] = 0;
        $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
        if ($overviewfilesoptions) {
            file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
        }
    }
    if ($formdata['open_points'] > 0) {
        $formdata['open_enablepoints'] = true;
    }
    $params = array(
        'course' => $course,
        'category' => $category->id,
        'editoroptions' => $editoroptions,
        'get_coursedetails' => $get_coursedetails,
        'form_status' => $args->form_status,
    );
    $mform = new custom_onlineexams_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $formdata['shortname_static'] = $formdata['shortname'];
    $formdata['summary_editor'] = $formdata['summary'];

    $mform->set_data($formdata);

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata) > 2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass, 'form-status' => $k);
    }
    $formstatusview = new \local_onlineexams\output\form_status($formstatus);
    $o .= $renderer->render($formstatusview);     
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

/**
 * function get_listof_courses
 * @todo all courses based  on costcenter / department
 * @param object $stable limit values
 * @param object $filterdata filterdata
 * @return  array courses
 */

function get_listof_onlineexams($stable, $filterdata,$options)
{
    global $CFG, $DB, $OUTPUT, $USER;
    $options=json_decode($options);
    $core_component = new core_component();    
    require_once($CFG->dirroot . '/course/renderer.php');
    require_once($CFG->dirroot . '/enrol/locallib.php');
    $autoenroll_plugin_exist = $core_component::get_plugin_directory('enrol', 'auto');
    if (!empty($autoenroll_plugin_exist)) {
        require_once($CFG->dirroot . '/enrol/auto/lib.php');
    }
    $chelper = new coursecat_helper();
    $context =  context_system::instance();
    $formsql = '';
    if ( is_siteadmin() || has_capability( 'local/onlineexams:manage', $context)) {
        $selectsql = "SELECT c.id ,c.fullname, c.shortname, c.category, c.summary, c.format , c.visible, c.open_module FROM {course} AS c";
        $countsql  = "SELECT count(c.id) FROM {course} AS c ";
    } else {
        $selectsql =" SELECT c.id ,c.fullname, c.shortname, c.category, c.summary, c.format , c.visible, c.open_module FROM {course} AS c";
        $countsql  = " SELECT count(c.id) FROM {course} AS c ";
        $formsql = " JOIN {enrol} AS e ON  e.courseid = c.id AND (e.enrol = 'manual' OR e.enrol = 'self' OR e.enrol='auto')";
        $formsql .= " JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = $USER->id";
     }
    $formsql .= " JOIN {course_categories} AS cc ON cc.id = c.category ";
    $formsql .= " AND c.id > 1  ";
    $formsql .= " AND c.open_coursetype = 1 ";
    $params = array();
    if (isset($filterdata->search_query) && trim($filterdata->search_query) != '') {
        $formsql .= " AND (c.fullname LIKE :search OR c.shortname LIKE :searchcode) ";       
        $params['search'] = '%' . trim($filterdata->search_query) . '%';
        $params['searchcode'] = '%' . trim($filterdata->search_query) . '%';

    } else {
        $searchparams = array();
    }

    if (!empty($filterdata->type)) {
        $filtercategories = explode(',', $filterdata->type);
        list($filtercategoriessql, $filtercategoriesparams) = $DB->get_in_or_equal($filtercategories, SQL_PARAMS_NAMED, 'type', true, false);
        $params = array_merge($params, $filtercategoriesparams);
        $formsql .= " AND c.open_module $filtercategoriessql ";
    }
    if (!empty($filterdata->onlineexams)) {
        $filteronlineexams = explode(',', $filterdata->onlineexams);
        list($filteronlineexamssql, $filteronlineexamsparams) = $DB->get_in_or_equal($filteronlineexams, SQL_PARAMS_NAMED, 'onlineexams', true, false);
        $params = array_merge($params, $filteronlineexamsparams);
        $formsql .= " AND c.id $filteronlineexamssql ";
    }

    $totalonlineexams = $DB->count_records_sql($countsql . $formsql, $params);

    $formsql .= " ORDER BY c.id DESC";
    $onlineexams = $DB->get_records_sql($selectsql . $formsql, $params, $stable->start, $stable->length);
    $ratings_plugin_exist = $core_component::get_plugin_directory('local', 'ratings');
    $onlineexamslist = array();
    $employeerole = $DB->get_field('role', 'id', array('shortname' => 'student'));
    if(is_siteadmin() || has_capability('local/onlineexams:manage', $context)){
        $is_siteadmin = TRUE;
    }
    if (!empty($onlineexams)) {
        $count = 0;
        foreach ($onlineexams as $key => $course) {
            $course = (array)$course;
            $course = (object)$course;
            $course_in_list = new core_course_list_element($course);
            $context =  \context_course::instance($course->id);
            // $departmentcount = 1;
            // $subdepartmentcount = 1;

            $params = array('courseid' => $course->id, 'employeerole' => $employeerole);
            // $enrolledusersssql = " SELECT COUNT(u.id) as ccount
            //                     FROM {course} c
            //                     JOIN {context} AS cot ON cot.instanceid = c.id AND cot.contextlevel = 50
            //                     JOIN {role_assignments} as ra ON ra.contextid = cot.id
            //                     JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1
            //                                     AND u.deleted = 0 AND u.suspended = 0
            //                     WHERE c.id = :courseid AND ra.roleid = :employeerole AND c.open_coursetype = 1 ";
            // $enrolled_count =  $DB->count_records_sql($enrolledusersssql, $params);

            // $enrolledusers = get_enrolled_users($context, 'enrol/category:synchronised');
            $enrolledusers = get_enrolled_users($context, 'mod/quiz:attempt');
            $enrolled_count = count($enrolledusers);

            $completedusersssql = " SELECT COUNT(u.id) as ccount
                                FROM {course} c
                                JOIN {context} AS cot ON cot.instanceid = c.id AND cot.contextlevel = 50
                                JOIN {role_assignments} as ra ON ra.contextid = cot.id
                                JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {course_modules} as cm ON cm.course = c.id 
                                JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND u.id = cmc.userid
                                WHERE c.id = :courseid AND ra.roleid = :employeerole AND cmc.completionstate > 0 AND c.open_coursetype = 1 ";
            $completed_count = $DB->count_records_sql($completedusersssql, $params);

            $coursename = $course->fullname;
            $shortname = $course->shortname;
            // $course_module = ($course->open_module == 'online_exams') ? get_string('online_exams', 'local_onlineexams') :  get_string('yearbook', 'local_onlineexams');
            if ($course->open_module == 'online_exams') {
                $course_module = get_string('online_exams', 'local_onlineexams');
            } else if ($course->open_module == 'year_book') {
                $course_module = get_string('yearbook', 'local_onlineexams');
            } else {
                $course_module = get_string('yearbookmocktest', 'local_onlineexams');
            }
            $format = $course->format;

            if (strlen($coursename) > 35) {
                $coursenameCut = clean_text(substr($coursename, 0, 35)) . "...";
                $onlineexamslist[$count]["coursenameCut"] = strip_tags_custom($coursenameCut);
            }
            $catname = $categoryname;
            $catnamestring = strlen($catname) > 12 ? clean_text(substr($catname, 0, 12)) . "..." : $catname;
            $displayed_names = '<span class="pl-10 ' . $course->coursetype . '">' . $course->coursetype . '</span>';

           // if ($ratings_plugin_exist) {
           //      require_once($CFG->dirroot . '/local/ratings/lib.php');
           //      $ratingenable = True;
           //      $avgratings = get_rating($course->id, 'local_onlineexams');
           //      $rating_value = $avgratings->avg == 0 ? 'N/A' : $avgratings->avg/*/2*/;
           //  } else {
           //      $ratingenable = False;
           //      $rating_value = 'N/A';
           //  }
           //  $classname = '\local_tags\tags';
           //  if (class_exists($classname)) {
           //      $tags = new $classname;

           //      $tagstring = $tags->get_item_tags($component = 'local_onlineexams', $itemtype = 'onlineexams', $itemid = $course->id, $contextid = context_course::instance($course->id)->id, $arrayflag = 0, $more = 0);
           //      $tagstringtotal = $tagstring;
           //      if ($tagstring == "") {
           //          $tagstring = 'N/A';
           //      } else {
           //          $tagstring = strlen($tagstring) > 35 ? clean_text(substr($tagstring, 0, 35)) . '...' : $tagstring;
           //      }
           //      $tagenable = True;
           //  } else {
           //      $tagenable = False;
           //      $tagstring = '';
           //      $tagstringtotal = $tagstring;
           //  }

            // if ($course->open_skill > 0) {
            //     $skill = $DB->get_field('local_skill', 'name', array('id' => $course->open_skill));
            //     if ($skill) {
            //         $skillname = $skill;
            //     } else {
            //         $skillname = 'N/A';
            //     }
            // } else {
            //     $skillname = 'N/A';
            // }
            $onlineexamslist[$count]["coursename"] = $coursename;
            $onlineexamslist[$count]["shortname"] =  $shortname;
            // $onlineexamslist[$count]["skillname"] = $skillname;
            // $onlineexamslist[$count]["ratings_value"] = $rating_value;
            // $onlineexamslist[$count]["ratingenable"] = $ratingenable;
            // $onlineexamslist[$count]["tagstring"] =$tagstring;
            // $onlineexamslist[$count]["tagstringtotal"] = $tagstringtotal;
            // $onlineexamslist[$count]["tagenable"] = $tagenable;
            $onlineexamslist[$count]["catname"] = $catname;
            $onlineexamslist[$count]["catnamestring"] = $catnamestring;
            $onlineexamslist[$count]["enrolled_count"] = $enrolled_count;
            $onlineexamslist[$count]["courseid"] = $course->id;
            $onlineexamslist[$count]["completed_count"] = $completed_count;
            // $onlineexamslist[$count]["points"] = $course->open_points != NULL ? $course->open_points : 0;
            $onlineexamslist[$count]["coursetype"] = $displayed_names;
            // $onlineexamslist[$count]["course_class"] = $course->visible ? 'active' : 'inactive';
            // $onlineexamslist[$count]["grade_view"] = ((has_capability(
            //     'local/onlineexams:grade_view',
            //     $context
            // ) || is_siteadmin()) && has_capability('local/onlineexams:manage', $context)) ? true : false;

            // $onlineexamsummary = $chelper->get_course_formatted_summary(
            //     $course_in_list,
            //     array('overflowdiv' => false, 'noclean' => false, 'para' => false)
            // );
            // $summarystring = strlen($onlineexamsummary) > 100 ? substr($onlineexamsummary, 0, 100) . "..." : $onlineexamsummary;            
            // $onlineexamslist[$count]["onlineexamsummary"] = strip_tags_custom($summarystring);
            // $onlineexamslist[$count]["fullonlineexamsummary"] = strlen($onlineexamsummary) > 100 ? strip_tags_custom(clean_text($onlineexamsummary)) : null;
            $onlineexamslist[$count]["format"] = $format;
            $onlineexamslist[$count]["course_module"] = $course_module;

            $course =  (array)$course;
            $course = (object)$course;
            $quizmoduleid = $DB->get_field('modules','id',['name'=>'quiz']);
            $coursequizmoduels = $DB->count_records('course_modules',['module'=>$quizmoduleid,'course'=>$course->id]);
            if($coursequizmoduels > 1) {
                $onlineexamslist[$count]["marks"] = '';
                $onlineexamslist[$count]["noofquestion"] = '';
                $onlineexamslist[$count]["timelimit"] = '';
            } else {
                $quizid = $DB->get_field('course_modules','instance',['module'=>$quizmoduleid,'course'=>$course->id]);

                $timelimit =$DB->get_field('quiz','timelimit',['id'=>$quizid]);
                $marks =$DB->get_field('grade_items','grademax',['courseid'=>$course->id,'iteminstance'=>$quizid]);
                $onlineexamslist[$count]["marks"] = ($marks) ? floor($marks):'';
                $onlineexamslist[$count]["noofquestion"] = $DB->count_records('quiz_slots',['quizid'=>$quizid]);
                $onlineexamslist[$count]["timelimit"] = gmdate("H:i:s",$timelimit);

            }

            //course image
            // if (file_exists($CFG->dirroot . '/local/includes.php')) {
            //     require_once($CFG->dirroot . '/local/includes.php');
            //     $includes = new user_course_details();
            //     $courseimage = $includes->course_summary_files($course);
            //     if (is_object($courseimage)) {
            //         $onlineexamslist[$count]["courseimage"] = $courseimage->out();
            //     } else {
            //         $onlineexamslist[$count]["courseimage"] = $courseimage;
            //     }
            // }

            $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual', 'courseid' => $course->id));

            if (has_capability('local/onlineexams:enrol', $context) && has_capability('local/onlineexams:manage', $context)) {
                $onlineexamslist[$count]["enrollusers"] = $CFG->wwwroot . "/local/onlineexams/onlineexamsenrol.php?id=" . $course->id . "&enrolid=" . $enrolid;
           }
            // $onlineexamslist[$count]["enrolledusers"] = $CFG->wwwroot . "/local/courses/enrolledusers.php?id=" . $course->id."&module=onlineexam";
            if (has_capability('local/onlineexams:view', $context) || is_enrolled($context)) {
                $onlineexamslist[$count]["courseurl"] = $CFG->wwwroot . "/course/view.php?id=" . $course->id;
            } else {
                $onlineexamslist[$count]["courseurl"] = "#";
            }

            // if ($departmentcount > 1 && !(is_siteadmin())) {
            //     $onlineexamslist[$count]["grade_view"] = false;
            //     $onlineexamslist[$count]["request_view"] = false;
            // }


            if (has_capability('local/onlineexams:update', $context) && has_capability('local/onlineexams:manage', $context)) {
                if($options->viewType=='table'){
                $courseedit = html_writer::link('javascript:void(0)', html_writer::tag('i', '', array('class' => 'fa fa-pencil ')), array('title' => get_string('edit'), 'alt' => get_string('edit'), 'data-action' => 'createcoursemodal', 'data-value' => $course->id, 'onclick' => '(function(e){ require("local_onlineexams/onlineexamsAjaxform").init({contextid:' . $context->id . ', component:"local_onlineexams", callback:"custom_onlineexams_form", form_status:0, plugintype: "local", pluginname: "onlineexams", courseid: ' . $course->id . ' }) })(event)'));
                }else{
                    $courseedit = html_writer::link('javascript:void(0)', html_writer::tag('i', '', array('class' => 'fa fa-pencil ')) . get_string('edit'), array('title' => get_string('edit'), 'alt' => get_string('edit'), 'data-action' => 'createcoursemodal', 'class' => 'createcoursemodal dropdown-item', 'data-value' => $course->id, 'onclick' => '(function(e){ require("local_onlineexams/onlineexamsAjaxform").init({contextid:' . $context->id . ', component:"local_onlineexams", callback:"custom_onlineexams_form", form_status:0, plugintype: "local", pluginname: "onlineexams", courseid: ' . $course->id . ' }) })(event)'));
                }
                $onlineexamslist[$count]["editcourse"] = $courseedit;
                
                if ($course->visible) {
                    $icon = 't/hide';
                    $string = get_string('make_active', 'local_onlineexams');
                   $title = get_string('make_inactive', 'local_onlineexams');
                } else {
                    $icon = 't/show';
                    $string = get_string('make_inactive', 'local_onlineexams');
                    $title = get_string('make_active', 'local_onlineexams');
                }
                $params = json_encode(array('coursename' => $coursename, 'onlineexamstatus' => $course->visible));
                if($options->viewType=='table'){
                    $status = $course->visible == 1 ? 'inactive' : 'active'; 
                $image = $OUTPUT->pix_icon($icon, $title, 'moodle', array('class' => 'iconsmall', 'title' => ''));
               // $onlineexamslist[$count]["update_status"] .= html_writer::link("javascript:void(0)", $image, array('data-fg' => "d", 'data-method' => 'course_update_status', 'data-plugin' => 'local_onlineexams', 'data-params' => $params, 'data-id' => $course->id));
                $onlineexamslist[$count]["update_status"] .= html_writer::link('javascript:void(0)', $image, 
                array('title' => get_string('delete'), 'id' => "onlineexams_inactive_confirm_" . $course->id, 'onclick' => '(function(e){ require(\'local_onlineexams/onlineexamsAjaxform\').inactiveConfirm({action:\'course_update_status\' , 
                    id: ' . $course->id . ', name:"' . $coursename . '", onlineexamsstatus: ' .$course->visible.', status: "' .$status.'"  }) })(event)'));
            }else{
                $image = $OUTPUT->pix_icon($icon, $title, 'moodle', array('class' => 'iconsmall', 'title' => '')) . $title;
                $onlineexamslist[$count]["update_status"] .= html_writer::link("javascript:void(0)", $image, array('class' => ' make_inactive dropdown-item', 'data-fg' => "d", 'data-method' => 'course_update_status', 'data-plugin' => 'local_onlineexams', 'data-params' => $params, 'data-id' => $course->id));
            }
                if (!empty($autoenroll_plugin_exist)) {
                    $autoplugin = enrol_get_plugin('auto');
                    $instance = $autoplugin->get_instance_for_course($course->id);
                    if ($instance) {
                        if ($instance->status == ENROL_INSTANCE_DISABLED) {

                            $onlineexamslist[$count]["auto_enrol"] = $CFG->wwwroot . "/enrol/auto/edit.php?courseid=" . $course->id . "&id=" . $instance->id;
                        }
                    }
                }
            }

            // if ($departmentcount > 1 && !(is_siteadmin())) {
            //     $onlineexamslist[$count]["editcourse"] = '';
            //     $onlineexamslist[$count]["update_status"] = '';
            //     $onlineexamslist[$count]["auto_enrol"] = '';
            // }

            if (has_capability('local/onlineexams:delete', $context) && has_capability('local/onlineexams:manage', $context)) {
                if($options->viewType=='table'){
                $deleteactionshtml = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => "onlineexams_delete_confirm_" . $course->id, 'onclick' => '(function(e){ require(\'local_onlineexams/onlineexamsAjaxform\').deleteConfirm({action:\'deleteonlineexams\' , id: ' . $course->id . ', name:"' . $coursename . '" }) })(event)'));
                }else{
                $deleteactionshtml = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')) . get_string('delete'), array('class' => "dropdown-item delete_icon", 'title' => get_string('delete'), 'id' => "onlineexams_delete_confirm_" . $course->id, 'onclick' => '(function(e){ require(\'local_onlineexams/onlineexamsAjaxform\').deleteConfirm({action:\'deleteonlineexams\' , id: ' . $course->id . ', name:"' . $coursename . '" }) })(event)'));
                }
                $onlineexamslist[$count]["deleteaction"] = $deleteactionshtml;
            }

            if ($departmentcount > 1 && !(is_siteadmin())) {
                $onlineexamslist[$count]["deleteaction"] = '';
            }

            // if (has_capability('local/onlineexams:grade_view', $context) && has_capability('local/onlineexams:manage', $context)) {
            //     $onlineexamslist[$count]["grader"] =  $CFG->wwwroot . "/grade/report/grader/index.php?id=" . $course->id;
            // }

            // if ($departmentcount > 1 && !(is_siteadmin())) {
            //     unset($onlineexamslist[$count]["grader"]);
            // }

            // if (has_capability('local/onlineexams:report_view', $context) && has_capability('local/onlineexams:manage', $context)) {
            //     $onlineexamslist[$count]["activity"] = $CFG->wwwroot . "/report/outline/index.php?id=" . $course->id;
            // }
            // if ($departmentcount > 1 && !(is_siteadmin())) {
            //     unset($onlineexamslist[$count]["activity"]);
            // }


           // if ((has_capability('local/request:approverecord', 1) || is_siteadmin())) {
                // $onlineexamslist[$count]["requestlink"] = $CFG->wwwroot . "/local/request/index.php?courseid=" . $course->id;
            //}

            // if ($departmentcount > 1 && !(is_siteadmin())) {
            //     unset($onlineexamslist[$count]["requestlink"]);
            // }

            $quiz = $DB->get_record('quiz', array("course" => $course->id));
            $gradeitem = $DB->get_record('grade_items', array('iteminstance' => $quiz->id, 'itemmodule' => 'quiz', 'courseid' => $course->id));
				$gradepass = $gradeitem->gradepass;
                $grademax = $gradeitem->grademax;           
            $onlineexamslist[$count]["examfromdate"] =($quiz->timeopen > 0) ?  date('d-m-Y h:i:s A', ($quiz->timeopen)) : 'N/A';
            $onlineexamslist[$count]["is_siteadmin"] =$is_siteadmin;
            // $onlineexamslist[$count]["examtodate"] = ($quiz->timeclose > 0) ? date('d-m-Y h:i:s A', ($quiz->timeclose)) : 'N/A';
            // $onlineexamslist[$count]["passgrade"] = ($gradepass) ? round($gradepass, 2) : 'N/A';
            // $onlineexamslist[$count]["maxgrade"] = ($grademax > 0) ? round($grademax,2) : 'N/A';
             $systemcontext = context_system::instance();
           if (has_capability('local/onlineexams:candeleteonlineexams', $systemcontext)) {
               $onlineexamslist[$count]["deleteenable"] = true;
           }
            $onlineexamslist[$count] = array_merge($onlineexamslist[$count], array(
                "actions" => (((has_capability(
                    'local/onlineexams:enrol',
                    $context
                ) || has_capability(
                    'local/onlineexams:update',
                    $context
                ) || has_capability(
                    'local/onlineexams:delete',
                    $context
                )
                // || has_capability(
                //     'local/onlineexams:grade_view',
                //     $context
                // ) || has_capability(
                //     'local/onlineexams:report_view',
                //     $context
                // )
            ) || is_siteadmin()) && has_capability('local/onlineexams:manage', $context)) ? true : false,
                "enrol" => ((has_capability(
                    'local/onlineexams:enrol',
                    $context
                )  || is_siteadmin()) && has_capability('local/onlineexams:manage', $context)) ? true : false,
                "update" => ((has_capability(
                    'local/onlineexams:update',
                    $context
                ) || is_siteadmin()) && has_capability('local/onlineexams:manage', $context) && has_capability('moodle/course:update', $context)) ? true : false,
                "delete" => ((has_capability(
                    'local/onlineexams:delete',
                    $context
                ) || is_siteadmin()) && has_capability('local/onlineexams:manage', $context) && has_capability('moodle/course:delete', $context)) ? true : false,
                // "report_view" => ((has_capability('local/onlineexams:report_view', $context) || is_siteadmin()) && has_capability('local/onlineexams:manage', $context)) ? true : false,
                "actions" => 1,
                "enrolled" => true
            ));


            $count++;
        }
        $nocourse = false;
        $pagination = false;
    } else {
        $nocourse = true;
        $pagination = false;
    }

    $onlineexamsContext = array(
        "hascourses" => $onlineexamslist,
        "nocourses" => $nocourse,
        "is_siteadmin"=>$is_siteadmin,
        "totalcourses" => $totalonlineexams,
        "length" => count($onlineexamslist),

    );
    return $onlineexamsContext;
}
// function local_onlineexams_leftmenunode()
// {
//     global $DB, $USER;
//     $categorycontext = (new \local_onlineexams\lib\accesslib())::get_module_context();
//     $coursecatnodes = '';
//     if (has_capability('local/onlineexams:view', $categorycontext) || has_capability('local/onlineexams:manage', $categorycontext) || is_siteadmin()) {
//         $coursecatnodes .= html_writer::start_tag('li', array('id' => 'id_leftmenu_browseonlineexams', 'class' => 'pull-left user_nav_div browseonlineexams'));
//         $onlineexams_url = new moodle_url('/local/onlineexams/index.php');
//         $onlineexams = html_writer::link($onlineexams_url, '<i class="fa fa-desktop"></i><span class="user_navigation_link_text">' . get_string('manage_onlineexams', 'local_onlineexams') . '</span>', array('class' => 'user_navigation_link'));
//         $coursecatnodes .= $onlineexams;
//         $coursecatnodes .= html_writer::end_tag('li');
//     }
//     return array('6' => $coursecatnodes);
// }

function add_onlineexam_quiz($validateddata, $examid)
{
    global $DB;
    //quiz module
    $quiz = new stdClass();
    $quiz->modulename = 'quiz';
    $quiz->add = 'quiz';
    $quiz->module = $DB->get_field('modules', 'id', array('name' => 'quiz'));    
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->quizpassword = '';
    $quiz->subnet = '';
    $quiz->visible = 1;
    $quiz->section = 0;
    $quiz->course = $examid->id;
    $quiz->timeopen = $validateddata->timeopen;
    $quiz->timeclose = $validateddata->timeclose;
    $quiz->timelimit = $validateddata->timelimit;
    $quiz->grademethod = $validateddata->grademethod;
    $quiz->grade = $validateddata->maxgrade;
    $quiz->gradepass = $validateddata->gradepass;
    $quiz->name = $validateddata->fullname;
    $quiz->attempts = $validateddata->attempts;
    $quiz->graceperiod = ($validateddata->graceperiod) ? $validateddata->graceperiod : 0;
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
    if (!empty($validateddata->summary_editor['text']))
        $quiz->introeditor['text'] = $validateddata->summary_editor['text'];
    else
        $quiz->introeditor['text'] = $validateddata->fullname;

    $quiz->introeditor['format'] = $validateddata->summary_editor['format'];
    $quiz->completion = 2;
    $quiz->completionusegrade = 1;
    $quiz->completionpassgrade = 1;
    return $quiz;
}

function update_onlineexam_quiz($validateddata, $data, $formstatus)
{
    global $DB;
    //quiz module
    $quiz = new stdClass();
    $quiz->modulename = 'quiz';
    $quiz->add = 'quiz';
    $quiz->module = $DB->get_field('modules', 'id', array('name' => 'quiz'));
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->quizpassword = '';
    $quiz->subnet = '';
    $quiz->visible = 1;
    $quiz->section = 0;
    $courseid = is_object($data) ? $data->id  : $data['id'];
    $quiz->course = $courseid;
    $quizobject = $DB->get_record('quiz', array('course' => $courseid));
    $quiz->completion = 2;
    $quiz->completionusegrade = 1;
    $quiz->completionpassgrade = 1;
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
    $quiz->id = $quizobject->id;
    $quiz->name = $validateddata->fullname;
    if ($formstatus == 0) {
        if (!empty($validateddata->summary_editor['text']))
            $quiz->introeditor['text'] = $validateddata->summary_editor['text'];
        else
            $quiz->introeditor['text'] = $validateddata->fullname;

        $quiz->introeditor['format'] = $validateddata->summary_editor['format'];
        $quiz->gradepass = $validateddata->gradepass;
        $quiz->grademethod = $validateddata->grademethod;
        $quiz->grade = $validateddata->maxgrade;
        $quiz->attempts = $validateddata->attempts;
    // }
    // if ($formstatus == 1) {
        $quiz->name = $quizobject->name;
        $quiz->timeopen = $validateddata->timeopen;
        $quiz->timeclose = $validateddata->timeclose;
        $quiz->overduehandling = $validateddata->overduehandling;
        $quiz->browsersecurity = $validateddata->browsersecurity;
        $quiz->timelimit = $validateddata->timelimit;
        $quiz->introeditor['text'] = $quizobject->intro;
        $quiz->introeditor['format'] = $quizobject->introformat;
        $quiz->grademethod = $quizobject->grademethod;
        $quiz->graceperiod = ($validateddata->graceperiod) ? $validateddata->graceperiod : 0;
    }
    return $quiz;
}

function learnerscript_onlineexams_list(){
    return 'Online Exams';
}


/**
* [course_enrolled_users description]
* @param  string  $type       [description]
* @param  integer $evaluationid [description]
* @param  [type]  $params     [description]
* @param  integer $total      [description]
* @param  integer $offset    [description]
* @param  integer $perpage    [description]
* @param  integer $lastitem   [description]
* @return [type]              [description]
*/
function course_enrolled_users($type = null, $course_id = 0, $params= array(), $total=0, $offset=-1, $perpage=-1, $lastitem=0){

    global $DB, $USER;
	$context =  context_system::instance();
    $course = $DB->get_record('course', array('id' => $course_id));
    $condition = ' ';
    $params['suspended'] = 0;
    $params['deleted'] = 0;
 
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.idnumber,')') as fullname";
    }else{
        $sql = "SELECT count(u.id) as total";
    }
    $sql.=" FROM {user} AS u WHERE  u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted ";
    // if($lastitem!=0){
    //    $sql.=" AND u.id > $lastitem ";
    // }
    if (!is_siteadmin()) {
        $sql .= $condition;
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['fullname'])) {
         $sql .=" AND u.id IN ({$params['fullname']})";
    }
    if ($type=='add') {
        $sql .= " AND u.id NOT IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self' OR e.enrol='auto' OR e.enrol='category')))";
    }elseif ($type=='remove') {
        $sql .= " AND u.id IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self' OR e.enrol='auto' OR e.enrol='category')))";
    }

    $order = " ORDER BY u.firstname  ASC ";

    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql.$order, $params, $lastitem, $perpage);
    }else{
        $availableusers = $DB->count_records_sql($sql, $params);
    }
    return $availableusers;
}



function category_enrol_users($type, $categoryid, $params= array(), $offset=-1, $perpage=-1, $lastitem=0) {
    global $DB, $USER;
    $params['suspended'] = 0;
    $params['deleted'] = 0;
    $params['contextid'] = $categoryid;
    $roleid = $DB->get_field('role', 'id', ['archetype' => 'student']);
    $params['roleid'] = $roleid;

    $countsql = "SELECT count(u.id) as count ";

    $selectsql = " SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname ";

    $fromsql = " FROM {user} u
                JOIN {role_assignments} ra ON u.id = ra.userid AND ra.roleid = :roleid 
                JOIN {context} ctx ON ra.contextid = ctx.id
                JOIN {role} r ON ra.roleid = r.id
                
                WHERE ra.contextid = :contextid AND u.id > 2 AND deleted = :deleted AND suspended = :suspended";

    if (!empty($params['email'])) {
         $fromsql.=" AND u.id IN ({$params['email']})";
    }

    if (!empty($params['fullname'])) {
         $fromsql .=" AND u.id IN ({$params['fullname']})";
    }

    if (!empty($params['phone'])) {
         $fromsql .=" AND u.id IN ({$params['phone']})";
    }

    $order = " ORDER BY u.firstname  ASC";

    $availableusers = $DB->get_records_sql_menu($selectsql. $fromsql. $order, $params, $lastitem, $perpage);
    $usercount = $DB->count_records_sql($countsql . $fromsql, $params);

    if($type == 'data') {

      return $availableusers;
    }
    if($type == 'count') {
        return $usercount;
    }

}

function category_unenrol_users($type, $categorycontextid, $params= array(), $offset=-1, $perpage=-1, $lastitem=0) {
    global $DB, $USER;
    $params['suspended'] = 0;
    $params['deleted'] = 0;
    $params['contextid'] = $categorycontextid;
    

    $countsql = "SELECT COUNT(u.id) as count ";

    $roleid = $DB->get_field('role', 'id', ['archetype' => 'student']);
    $params['roleid'] = $roleid;
    $selectsql = "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname " ;

    $fromsql = " FROM {user} u WHERE u.id NOT IN (
            SELECT DISTINCT u.id
                FROM {user} u
                JOIN {role_assignments} ra ON u.id = ra.userid AND ra.roleid = :roleid 
                JOIN {context} ctx ON ra.contextid = ctx.id
                WHERE ra.contextid = :contextid AND u.id > 2  
        ) AND u.id > 2 AND deleted = :deleted AND suspended = :suspended ";
    if (!empty($params['email'])) {
         $fromsql.=" AND u.id IN ({$params['email']})";
    }

    if (!empty($params['fullname'])) {
         $fromsql .=" AND u.id IN ({$params['fullname']})";
    }
    if (!empty($params['phone'])) {
         $fromsql .=" AND u.id IN ({$params['phone']})";
    }
    $unenroledusers = $DB->get_records_sql_menu($selectsql. $fromsql. $order, $params, $lastitem, $perpage);
    $usercount = $DB->count_records_sql($countsql . $fromsql, $params);

    if($type == 'data') {
      return $unenroledusers;
    }
    if($type == 'count') {
        return $usercount;
    }
}

function strip_tags_custom($content){
    return mb_convert_encoding(clean_text(html_to_text($content)), 'UTF-8');
}
function name_filter($mform){
    global $DB;
    $sql = "SELECT id, fullname FROM {course} WHERE id > 1 AND (open_module = 'online_exams' OR open_module = 'year_book' OR open_module = 'year_book_mocktest')  AND open_coursetype = 1 ";

    if(is_siteadmin()){
       $onlineexamslist = $DB->get_records_sql_menu($sql);
    }

    $onlineexamslist = $DB->get_records_sql_menu($sql);

    $select = $mform->addElement('autocomplete', 'onlineexams', get_string('tests','local_onlineexams'), $onlineexamslist, array('placeholder' => get_string('tests','local_onlineexams')));
    $mform->setType('onlineexams', PARAM_RAW);
    $select->setMultiple(true);
}
function user_filter($mform){
    global $DB;
    $sql = "SELECT id, concat(firstname,' ',lastname) as fullname FROM {user} WHERE id > 2 ";

    if(is_siteadmin()){
       $onlineexamslist = $DB->get_records_sql_menu($sql);
    }

    $onlineexamslist = $DB->get_records_sql_menu($sql);

    $select = $mform->addElement('autocomplete', 'fullname', get_string('fullname','local_onlineexams'), $onlineexamslist, array('placeholder' => get_string('fullname','local_onlineexams')));
    $mform->setType('onlineexams', PARAM_RAW);
    $select->setMultiple(true);
}
function email_filter($mform){
    global $DB;
    $sql = "SELECT id, email FROM {user} WHERE id > 2 ";
    if(is_siteadmin()){
       $onlineexamslist = $DB->get_records_sql_menu($sql);
    }
    $onlineexamslist = $DB->get_records_sql_menu($sql);
    $select = $mform->addElement('autocomplete', 'email', get_string('email','local_onlineexams'), $onlineexamslist, array('placeholder' => get_string('email','local_onlineexams')));
    $mform->setType('onlineexams', PARAM_RAW);
    $select->setMultiple(true);
}
function type_filter($mform){
    // $select = $mform->addElement('autocomplete', 'type', get_string('type','local_onlineexams'), ['online_exams' => 'Mock Test', 'year_book' => 'Year Book'], array('placeholder' => get_string('tests_type','local_onlineexams')));
    $select = $mform->addElement('autocomplete', 'type', get_string('type','local_onlineexams'), [null => '', 'online_exams' => 'Mock Test', 'year_book' => 'Year Book Quiz', 'year_book_mocktest' => 'Year Book Mock Test'], array('placeholder' => get_string('tests_type','local_onlineexams')));
    $mform->setType('onlineexams', PARAM_RAW);
    $select->setMultiple(false);
}

function phone_filter($mform){
    global $DB;
    $sql = "SELECT id, phone1 as phone FROM {user} WHERE id > 2 AND phone1 <> '' ";
    if(is_siteadmin()){
       $onlineexamslist = $DB->get_records_sql_menu($sql);
    }
    $onlineexamslist = $DB->get_records_sql_menu($sql);
    $select = $mform->addElement('autocomplete', 'phone', get_string('phone','local_onlineexams'), $onlineexamslist, array('placeholder' => get_string('phone','local_onlineexams')));
    $mform->setType('onlineexams', PARAM_RAW);
    $select->setMultiple(true);
}
