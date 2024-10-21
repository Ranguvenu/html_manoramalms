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
 * Package details view
 *
 * @package   local_packages
 * @copyright 2023, MOODLE India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_packages\local\packages as packages;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');


function logo_url($itemid = 0,$farea = null) {
    global $DB;
    $context = context_system::instance();
    if ($itemid > 0) {
        $sql = "SELECT * FROM {files} WHERE itemid = :logo AND component ='local_goals'  AND filearea = '$farea' AND filename != '.' ORDER BY id DESC";
        $classimagerecord = $DB->get_record_sql($sql, array('logo' => $itemid), 1);
    }
    $logourl = "";
    if (!empty($classimagerecord)) {
        $logo = \moodle_url::make_pluginfile_url($classimagerecord->contextid, $classimagerecord->component,$classimagerecord->filearea, $classimagerecord->itemid, $classimagerecord->filepath,
        $classimagerecord->filename);
        $logourl = $logo->out();
    }
    return $logourl;
}

/**
 * [users_enrolled_info description]
 * @param $userid
 * @return array
 */
function users_enrolled_info() {
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
        
    $category = get_category($USER->id);
    $count = 0;
    $params = array();
    $cids = [];
    $enrolled =  enrol_get_users_courses($USER->id, $onlyactive = false, $fields = null, $sort = null);
    foreach ($category as $cat) {
          
        $courses = [];
        $percentage_of_completion = 0;
        $modules_in_course = 0;
        $completed_moduleincourse = 0;
        $total_completed_activity1 = [];
        foreach ($enrolled as $ecourse) {
            $check = core_completion\progress::get_course_progress_percentage($ecourse, $USER->id);
            if ($ecourse->category == $cat->id) {
                $course_completed = 0;
                $courseparams = [];
                $cids[] = $ecourse->id;
                $courseparams['id'] =  $ecourse->id;
                $courseparams['fullname'] = $ecourse->fullname;
                $courseparams['shortname'] = $ecourse->shortname;
                $courseparams['progress'] = round($check);
                // $courseparams['startdate'] = date("jS M Y", $ecourse->startdate);
                $courseparams['startdate'] = $ecourse->startdate;
                $courseendate=$DB->get_field('course','enddate',['id'=>$ecourse->id]);
                // $courseparams['enddate'] = date("jS M Y", $courseendate);
                $courseparams['enddate'] = $courseendate;
                $courseimg = course_summary_files($ecourse);
                $courseimg = $courseimg->out();
                $courseparams['image_url'] = $courseimg;
                $courses[] = $courseparams;
            }
        }
        $categoryprogress = get_category_progress($cat->id,$USER->id);

        $sql = "SELECT ue.timeend
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = ? ";
        if (!empty($cids)) {
            $courseids = implode(',', $cids);
            $sql .= " AND e.courseid IN ($courseids)";
        }
        $timeenddate = $DB->get_fieldset_sql($sql, [$USER->id]);
        if (!empty($timeenddate)) {
            $expirydateinunix = max($timeenddate);
            // $expirydate = date("jS M Y", $expirydateinunix);
            $expirydate = $expirydateinunix;
        } else {
            $expirydate = 0;
        }
        $hierarchyimage = $DB->get_field('local_hierarchy', 'image', ['categoryid' => $cat->id]);
        if ($hierarchyimage) {
            $catimg_url = $hierarchyimage;
        } else {
            $catimg = $OUTPUT->image_url('cat', 'block_mycourses');
            $catimg_url = $catimg->out();
        }
        $params[$count]['courses'] = $courses;
        $params[$count]['id'] = $cat->id;
        $params[$count]['name'] = $cat->name;
        $params[$count]['progress'] = round($categoryprogress);
        $params[$count]['count'] = $cat->coursecount;
        $params[$count]['validtill'] = $expirydate;
        $params[$count]['img_url'] = $catimg_url;
        // $params[$count]['enddate'] = userdate(time());
        $params[$count]['enddate'] = $expirydate;
        $count++;
        }
        $data = array(
            "categorydetails" => $params,
        );
    return $data;
}

/**
 * [get_category description]
 * @param $userid
 * @return $category array
 */
function get_category($userid){
    global $DB;
    $category = $DB->get_records_sql("SELECT cc.id,cc.name,count(c.id) as coursecount FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packages} AS lp on lp.categoryid = cc.id 
        LEFT JOIN {local_hierarchy} AS lh on lh.categoryid = cc.id 

        WHERE ue.userid=$userid AND cc.visible=1 GROUP BY cc.id,cc.name  ");

    return $category;
}   
/**
 * [get_category_progress description]
 * @param $catid
 * @param $userid
 * @return $percentage_of_completion array
 */
function get_category_progress($catid,$userid) {

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $enrolled = $DB->get_records_sql("SELECT e.courseid,c.* FROM {user_enrolments} ue 
            JOIN {enrol} e ON e.id = ue.enrolid 
            JOIN {course} AS c ON c.id = e.courseid 
            JOIN {course_categories} AS cc ON c.category = cc.id 
            WHERE ue.userid=$userid AND cc.id=$catid");
    $coursetot = count($enrolled);
    $courses = [];
    $percentage_of_completion = 0;
    $modules_in_course = 0;
    $completed_moduleincourse = 0;
    foreach ($enrolled as $ecourse) {
        $courses[] = core_completion\progress::get_course_progress_percentage($ecourse, $userid);
        $total_cou = array_sum($courses);
        $percentage_of_completion = ($total_cou / $coursetot);
    }
 
    return $percentage_of_completion;
}

/**
 * [course_summary_files description Course images]
 * @param $courserecord
 * @return $url
 */
function course_summary_files($courserecord){
    global $DB, $CFG, $OUTPUT;

    if ($courserecord instanceof stdClass) {
        $courserecord = new core_course_list_element($courserecord);
    }
    foreach ($courserecord->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        if($isimage){
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename());
        }else{
            $url = $OUTPUT->image_url('courseimg', 'block_mycourses');
        }
    }
    if(empty($url)){
        $url = $OUTPUT->image_url('courseimg', 'block_mycourses');
    }
    return $url;
}

/**
 * [recommended_coures_info description ]
 * @param $userid
 * @return $data
 */
function recommended_coures_info($userid){
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $context = context_system::instance();
    $recomended_course= get_recommended_courses($userid);
    $count = 0;
    $params = array();
    foreach($recomended_course as $courses){
        
        $params[$count]['id'] = $courses->id;
        $params[$count]['fullname'] = $courses->fullname;
        $params[$count]['shortname'] = $courses->shortname;
        $params[$count]['startdate'] = userdate($courses->startdate, get_string('strftimedate', 'langconfig'));
        $params[$count]['enddate'] = userdate($courses->enddate, get_string('strftimedate', 'langconfig'));

        $courseimg = course_summary_files($courses);
        $courseimg = $courseimg->out();       
        $params[$count]['image_url'] = $courseimg;
        $params[$count]['launch_url'] = local_packages_get_launch_url($courses->id);
        $count++;
        }
        $data = array(
            "rcursedetails" => $params,
            
        );
        
    return $data;
}
function local_packages_get_launch_url($courseid){
    global $DB;
    $packagetype = $DB->get_field('local_packagecourses', 'package_type', ['courseid' => $courseid]);
    if ($packagetype == 2) {
        $type = 'test';
    } else {
        $type = 'course';
    }
    return get_config('auth_lbssomoodle', 'laravel_site_url')."/ecommerce/lms/{$type}/{$courseid}";
}

/**
 * [get_recommended_courses description ]
 * @param $userid
 * @return $recomended
 */
function get_recommended_courses($userid){
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $myactivetags = $DB->get_fieldset_sql("SELECT DISTINCT(ti.tagid) FROM {tag_instance} AS ti JOIN {enrol} AS e on e.courseid = ti.itemid JOIN {user_enrolments} AS ue ON  e.id =ue.enrolid WHERE ue.userid = $userid ");
        

    if ($myactivetags) {
         list($tagsql, $tagparams) = $DB->get_in_or_equal($myactivetags, SQL_PARAMS_NAMED, 'tagids');
    } else {
        $tagsql = ' = 0 ';
        $tagparams = [];
    }
    $tagparams['userid'] = $userid;
    $tagparams['now'] = time();
    $recomended = $DB->get_records_sql("SELECT c.* FROM {course} AS c
        JOIN {course_categories} AS cc ON c.category = cc.id
        JOIN {tag_instance} AS ti ON ti.itemid = c.id AND ti.itemtype = 'course' AND ti.component = 'core'
        WHERE ti.tagid $tagsql AND c.enddate > :now AND c.id NOT IN (SELECT e.courseid FROM {enrol} AS e   
        JOIN {user_enrolments} AS ue ON  e.id =ue.enrolid WHERE ue.userid = :userid) ", $tagparams); 
        
    return  $recomended;
}
    

/**
 * [test_courses description ]
 * @param $userid
 * @return $testcourse_data
 */
function get_test_courses($userid){
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $count = 0;
    $enrolledcourses = get_enrolled_tests($userid);  
    $courseparams = array();
    foreach($enrolledcourses as $course){
        
        $courseparams[$count]['id'] =  $course->id;
        $courseparams[$count]['fullname'] = $course->fullname;
        $courseparams[$count]['shortname'] = $course->shortname;
        $courseparams[$count]['startdate'] = date("jS M Y",$course->startdate);
        $courseparams[$count]['enddate'] = date("jS M Y",$course->enddate);
        $courseimg = course_summary_files($course);
        $courseimg = $courseimg->out();       
        $courseparams[$count]['image_url'] = $courseimg;
        $count++;
    }
    $testcourse_data = array(
        "testcourses" => $courseparams,        
    );
            
    return  $testcourse_data; 
}
    
/**
 * [get_enrolled_tests description ]
 * @param $userid
 * @return $courses
 */
function get_enrolled_tests($userid){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
       
    $sql = "SELECT c.* FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packagecourses} AS lpc ON lpc.courseid = c.id
        WHERE ue.userid=:userid AND cc.visible=1 AND lpc.package_type = 2 ";//2 for test only packages
    $courses = $DB->get_records_sql($sql,['userid'=>$userid]);
    return $courses;
}
   
function due_activities_list(){
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $value = array();
    for ($i = 7; $i >= 1; $i--) {
 
        $ctime = time() - $i * DAYSECS;
       
        $calendar = \calendar_information::create($ctime, 0, null);
        list($data, $template) = calendar_get_view($calendar, 'day');
        $value = array_merge($data->events, $value);
    }
    $edata = [];
    $count = 0;
    foreach($value as $event){
        
        if($event->eventtype == 'open'){
            continue;
        }
        if ($event->modulename == 'quiz') {
            $quiz = attempted_quiz($event->instance,$event->course->id);
            if($quiz == 'true'){
                continue;
            } 
            else {
                $edata[] = $event;   
            }      
        }
        else if ($event->modulename == 'assign') {
            $assign = submitted_assignment($event->instance, $event->course->id);  
            if($assign == 'true'){
                continue;
            } 
            else {
                $edata[] = $event;  
            }        
        }
        else if ($event->modulename == 'zoom') {
            $zoom = attended_zoom($event->instance,$event->course->id);  
            if($zoom == 'true'){
                continue;
            } 
            else {
                $edata[] = $event;
            }
        }
        
        $count++;

    }
   
    $dueactivity = array(
        "dueactivity" =>  $edata,
    );
    
    return  $dueactivity; 
}

function attempted_quiz($e_moduleid, $courseid){
     
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    
    $sql = 'SELECT q.* FROM {quiz} q 
            WHERE q.course = :courseid 
            AND q.id IN (SELECT quiz FROM {quiz_attempts} qa WHERE qa.userid = :userid)';

    $quiz = $DB->get_records_sql($sql, ['userid' => $USER->id,'courseid' => $courseid]);

    if ($quiz) { 
        return true;
    }else{
        return false;
    }
   
}

function submitted_assignment($e_moduleid,$courseid){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $sql = 'SELECT a.* FROM {assign} a 
            WHERE a.course = :courseid 
            AND a.id IN (SELECT assignment FROM {assign_submission} asb WHERE asb.userid = :userid)';

    $assign = $DB->get_records_sql($sql, ['assignment_id' => $e_moduleid, 'userid' => $USER->id,'courseid' => $courseid]);
   
    if ($assign) { 
        return true;
    }else{
        return false;
    }

}

function attended_zoom($e_moduleid,$courseid){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $sql = 'SELECT z.* FROM {zoom} z
            JOIN {zoom_meeting_details} zd ON z.id = zd.zoomid
            WHERE z.course = :courseid 
            AND zd.id IN (SELECT detailsid FROM {zoom_meeting_participants} zp WHERE zp.userid = :userid)';

    $zoom = $DB->get_records_sql($sql, ['zoom_id' => $e_moduleid, 'userid' => $USER->id,'courseid' => $courseid]);
   
    if ($zoom) { 
        return true;
    }else{
        return false;
    }
}

