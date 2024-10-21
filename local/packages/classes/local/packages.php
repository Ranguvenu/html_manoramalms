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
namespace local_packages\local;
use context_course;
use core_course_category;
use dml_exception;
use core_course\external\course_summary_exporter;

require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot . '/mod/zoom/lib.php');
require_once($CFG->dirroot.'/mod/zoom/locallib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->dirroot.'/course/dnduploadlib.php');
require_once($CFG->dirroot.'/course/externallib.php');

use \core_availability\tree;
use \availability_group\condition;
use moodle_exception;
use stdClass;
use html_writer;
use moodle_url;
use context_system;
use local_packages\local\packages as package;
use local_goals\local\goals as goal;
use \core_external\external_api;
use core_course_external;

require_once($CFG->dirroot . '/local/packages/lib.php');

/**
 * Packages library file
 */
class packages {

    /**
     * Package details overview
     * @param [type] $pacakgeid [description]
     * @param [type]  $api       [description]
     */
    public function packagedetails_overview($packagerecord, $api = false) {
        global $DB, $CFG, $OUTPUT;
        $systemcontext = context_system::instance();
        if (!$packagerecord) {
            if(!isloggedin()) {
                redirect($CFG->wwwroot.'/local/packages/package.php');
            } else {
                redirect($CFG->wwwroot.'/local/packages/index.php');
            }            
        }
        if (!empty($packagerecord->image)) {
            $packageimageurl = $packagerecord->image;
        } else {
            $packageImage = $OUTPUT->image_url('package', 'local_packages');
            $packageimageurl = $packageImage->out();
        }
        $querysql = "SELECT * FROM {local_packagecourses} WHERE hierarchyid = $packagerecord->id";
        $clonedcourserecords= $DB->get_records_sql($querysql);
        $clonedcourseinfo = array();
        $localpackagerecord = $DB->get_record('local_packagecourses',['hierarchyid'=>$packagerecord->id]);
        foreach($clonedcourserecords as $subjectrecord) {
            $clonedcourse = $DB->get_record('course',['id'=>$subjectrecord->courseid]);
            $parentcourse = $DB->get_record('local_subjects',['courseid'=>$subjectrecord->parentcourseid]);
           // $courseimage = course_summary_exporter::get_course_image($course);
           // $courseimageurl =(!$courseimage) ? $OUTPUT->get_generated_image_for_id($subjectrecord->courseid) :$courseimage ;
            if (!empty($parentcourse->image)) {
                $subjectimageurl = logo_url($parentcourse->image,'subjectimage');
            } else {
                $subjectImage = $OUTPUT->image_url('subject', 'local_packages');
                $subjectimageurl = $subjectImage->out();
            }
            $totaltopics = $DB->count_records_sql('SELECT COUNT(id) FROM {course_sections} WHERE course=:courseid AND section > 0',['courseid'=>$subjectrecord->courseid]);
            $totalsessions = $DB->count_records_sql('SELECT COUNT(id) FROM {local_package_sessions} WHERE courseid=:courseid AND packageid =:packageid',['courseid'=>$subjectrecord->courseid,'packageid'=>$packagerecord->id]);
            $clonedcourseinfo[] = [
                'subjectid' => $subjectrecord->id,
                'classessid' => $packagerecord->id,
                'courseid' => $subjectrecord->courseid,
                'subjectname' => $clonedcourse->fullname,
                'shortname' => $clonedcourse->shortname,
                'subjectimageurl'=>$subjectimageurl,
                'totaltopics'=>$totaltopics,
                'totalsessions'=>$totalsessions,
                'courseviewurl'=>$CFG->wwwroot.'/course/view.php?id='.$subjectrecord->courseid,
            ];
        }

        $ccourses = $DB->get_fieldset_sql('SELECT courseid FROM {local_packagecourses} WHERE hierarchyid = '.$packagerecord->id.'');
        $courses = implode(',',$ccourses);

        $querysql = "SELECT * FROM {local_batches} WHERE hierarchy_id = ".$packagerecord->id;
        $batchrecords= $DB->get_records_sql($querysql);
        $batchinfo = array();
        foreach($batchrecords as $batchrecord) {
           // $batchimagesample = $OUTPUT->get_generated_image_for_id($batchrecord->id);
            $batchimagesample = $OUTPUT->image_url('batches', 'local_packages');
            $batchinfo[] = [
                'batchid' => $batchrecord->id, 
                'batchname' => $batchrecord->name, 
                'batchcode' => $batchrecord->code,
                'batchimage'=>$batchimagesample,
                'enrolldate'=>userdate($batchrecord->enrol_start_date,get_string('strftimedatemonthabbr', 'core_langconfig')).' - '.userdate($batchrecord->enrol_end_date,get_string('strftimedatemonthabbr', 'core_langconfig')),
                'duration'=>$batchrecord->duration.' '.get_string('days'),
                // 'groupenrolledusers'=>count_enrolled_users(context_course::instance($batchrecord->courseid),'', $batchrecord->groupid,false),
                'studentlimit'=>$batchrecord->studentlimit,
            ];
        }

        $package_type = ($localpackagerecord->package_type == '1') ? get_string('course_only','local_packages'):get_string('test_only','local_packages');
        $validity = ($localpackagerecord->validity_type == '1') ? $localpackagerecord->validity.' '.get_string('days'):userdate($localpackagerecord->validity,get_string('strftimedatemonthabbr', 'core_langconfig')).','.get_string('midnight','local_packages');
        $packagedate = 
        [   'packagename' => $packagerecord->name,
            'packagecode' => $packagerecord->code,
            'packageview' => true,
            'subjectsview' => true,
            'startdate' => userdate($localpackagerecord->startdate,get_string('strftimedatemonthabbr', 'core_langconfig')),
            'enddate' =>  userdate($localpackagerecord->enddate,get_string('strftimedatemonthabbr', 'core_langconfig')),
            'package_type'=>$package_type,
            'validity'=>$validity,
            'clonedcourseinfo' => $clonedcourseinfo,
            'batchinfo' => $batchinfo,
            'packagedescription' => format_text($packagerecord->description,FORMAT_HTML),
            'packageimageurl' => $packageimageurl,
            'packageid' => $packagerecord->id,
            'packagecourses'=>COUNT($clonedcourserecords),
            'packagebatches'=>COUNT($batchrecords),
        ];
        if ($api) {
            return $packagedate;
        } else {
           echo $OUTPUT->render_from_template('local_packages/packagedetails_overview', $packagedate);
        }
    }
    /**
     * Package classess
     * @param [type] $pacakgeid [description]
     * @param [type]  $api       [description]
     */
    public function get_packages($stable, $filterdata) {
        global $DB,$OUTPUT;
        $selectsql = "SELECT lh.* FROM {local_hierarchy} lh "; 
        $countsql = "SELECT COUNT(lh.id) 
            FROM {local_hierarchy} lh ";
        $formsql = " WHERE 1=1 AND depth = 4 ";
        if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
            $formsql .= "   AND 
                            (
                              lh.name LIKE :namesearch OR 
                              lh.code LIKE :codesearch
                            )";
            $searchparams = array(
                'namesearch' => '%'.trim  ($filterdata->search_query).'%'
                ,'codesearch' => '%'.trim($filterdata->search_query).'%'
            );
        }else{
            $searchparams = array();
        }
        $params = array_merge($searchparams);
        $totalpackages = $DB->count_records_sql($countsql.$formsql, $params);
        $formsql .=" ORDER BY lh.id DESC";
        $packages = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);
        $listofpackages = array();
        $count = 0;
        foreach($packages as $package) {
            $localpackagerecord = $DB->get_record('local_packagecourses',['hierarchyid'=>$package->id]);
            $ccourses = $DB->get_fieldset_sql('SELECT courseid FROM {local_packagecourses} WHERE hierarchyid = '.$package->id.'');
            $courses = implode(',',$ccourses);
            $listofpackages[$count]['packageid'] = $package->id;
            $listofpackages[$count]['name'] = $package->name;
            $listofpackages[$count]['code'] = $package->code;
            $listofpackages[$count]['startdate'] = userdate($localpackagerecord->startdate,get_string('strftimedatemonthabbr', 'core_langconfig'));
            $listofpackages[$count]['enddate'] = userdate($localpackagerecord->enddate,get_string('strftimedatemonthabbr', 'core_langconfig'));
            if (!empty($package->image)) {
                $packageImageUrl = $package->image;
            } else {
                $packageImage = $OUTPUT->image_url('package', 'local_packages');
                $packageImageUrl = $packageImage->out();
            }
            $listofpackages[$count]['image'] = $packageImageUrl;
            $listofpackages[$count]['description'] = $package->description;
            $listofpackages[$count]['timecreated'] = userdate($package->timecreated,get_string('strftimedatemonthabbr', 'core_langconfig')) ;
            $listofpackages[$count]['subjects'] = COUNT(explode(',',$courses));
            $count++;
        }
        $packagesContext = array(
            "packages" => $listofpackages,
            "totalpackages" => $totalpackages,
            "length" => COUNT($listofpackages),
        );
        return $packagesContext;

   }
    public static function get_batches($batches,$batchid = 0,$packageid = 0,$courseid = 0) {
        global $DB;
        if(!empty($batches)){
            list($batchsql, $batchparams) = $DB->get_in_or_equal($batches);
            $sql = " SELECT id,name FROM {groups} WHERE id $batchsql ";
            $batches = $DB->get_records_sql_menu($sql, $batchparams);
        }
        if($batchid > 0) {
            $sql = " SELECT lb.id,lb.name FROM {groups}  AS lb  JOIN {local_package_sessions} lps ON lps.batchid = lb.id WHERE lps.id =$batchid ";
            $batches = $DB->get_records_sql_menu($sql);
        }
        return $batches;
    }
    public function get_listof_batches($query = null,$packageid = 0,$courseid = 0) {
        global $DB;
        $fields = array("idnumber",'name');
        $likesql = array();
        $i = 0;
        foreach ($fields as $field) {
            $i++;
            $likesql[] = $DB->sql_like($field, ":queryparam$i", false);
            $sqlparams["queryparam$i"] = "%$query%";
        }
        $sqlfields = implode(" OR ", $likesql);
        $concatsql = " AND ($sqlfields) ";
        $displayname = "concat(name,' ','(',idnumber,')')";
        $sql = "SELECT id,$displayname AS fullname
        FROM {groups} 
        WHERE courseid = $courseid AND id NOT IN (SELECT batchid FROM {local_package_sessions} WHERE packageid = $packageid AND courseid =$courseid) AND id $concatsql ";
        
        $order = " ORDER BY id ASC limit 50";
        $data = $DB->get_records_sql($sql.$order,$sqlparams);
        $return = array_values(json_decode(json_encode(($data)), true));
        return $return;
    }
    public static function get_timeselector() {
        for ($i = 0; $i <= 24; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += 1) {
            $minutes[$i] = sprintf("%02d", $i);
        }
        return compact('hours', 'minutes');
    }
    public static function get_teachers($teachers,$batchid = 0) {
        global $DB;
        if(!empty($teachers)){
            list($batchsql, $batchparams) = $DB->get_in_or_equal($teachers);
            $sql = " SELECT id,CONCAT(firstname,' ',lastname)  FROM {user} WHERE id $batchsql ";
            $teachers = $DB->get_records_sql_menu($sql, $batchparams);
        }
        if($batchid > 0) {
            $sql = " SELECT u.id,CONCAT(u.firstname,' ',u.lastname) FROM {user} u JOIN {local_package_sessions} lps ON lps.teacher = u.id WHERE lps.id =$batchid  AND u.deleted  = 0 ";
            $teachers = $DB->get_records_sql_menu($sql);
        }
        return $teachers;
    }
    public function get_listof_teachers($query = null,$packageid = 0,$courseid = 0) {
        global $DB;
        $fields = array("idnumber",'firstname','lastname','email');
        $likesql = array();
        $i = 0;
        foreach ($fields as $field) {
            $i++;
            $likesql[] = $DB->sql_like($field, ":queryparam$i", false);
            $sqlparams["queryparam$i"] = "%$query%";
        }
        $sqlfields = implode(" OR ", $likesql);
        $concatsql = " AND ($sqlfields) ";
        $sql = "SELECT id,CONCAT(firstname,' ',lastname) AS fullname
        FROM {user} 
        WHERE id > 2 ";
        $order = " ORDER BY id ASC limit 50";
        $data = $DB->get_records_sql($sql.$order,$sqlparams);
        $return = array_values(json_decode(json_encode(($data)), true));
        return $return;
    }
    public function add_update_session($data) {
        global $DB,$USER;
        $sdata = new stdClass();
        $batchid = is_array($data->batch)?implode(',',$data->batch):$data->batch;
        $data->batchid = $batchid;
        $data->batchname = $DB->get_field('groups','name',['id'=>(int)$batchid]);
        $groupsectionid = $DB->get_field('local_coursegroup_section','sectionid',['courseid'=>$data->courseid,'groupid'=>(int)$batchid]);
        $start = $data->startdate+($data->starttime['hours'] * 3600) + ($data->starttime['minutes'] * 60);
        $end = $data->startdate+($data->endtime['hours'] * 3600) + ($data->endtime['minutes'] * 60);
        $data->duration =abs($start-$end);
        $zoommeetingid = ($data->id > 0) ? $DB->get_field('local_package_sessions','sessionid',['id'=>$data->id]) : 0 ;
        $sectionid = ($data->id > 0) ? $DB->get_field('local_package_sessions','sectionid',['id'=>$data->id]) : $groupsectionid ;
        $sessiondata = ($data->id > 0) ? $this->updatezoom($data,$zoommeetingid,$sectionid) : $this->createzoom($data,$groupsectionid);
        if($sessiondata){
            if($data->id > 0) {
              $sdata->id =$data->id; 
            }
            //batch id is the group id
            $sdata->batchid= is_array($data->batch)?implode(',',$data->batch):$data->batch;
            $sdata->teacher= is_array($data->teacher)?implode(',',$data->teacher):$data->teacher;
            $sdata->startdate = $data->startdate;
            $sdata->enddate = $data->enddate;
            $sdata->schedulecode = $data->schedulecode;
            $sdata->starttime = ($data->starttime['hours'] * 3600) + ($data->starttime['minutes'] * 60);
            $sdata->endtime = ($data->endtime['hours'] * 3600) + ($data->endtime['minutes'] * 60);
            $sdata->usercreated =$USER->id; 
            $sdata->timecreated =time(); 
            $sdata->courseid = $data->courseid;
            $sdata->sessionid = $sessiondata['sessionid'];
            $sdata->sectionid = $sessiondata['sectionid'];
            $sdata->packageid = $data->packageid;
            try{
                if($data->id > 0) {
                   $record = $DB->update_record('local_package_sessions', $sdata); 
                   return $record;
                } else {
                   $createdid =  $DB->insert_record('local_package_sessions', $sdata); 
                   return $createdid;
                }
            } catch(dml_exception $e){
                print_r($e);
            }
        }
    }
    public function createzoom($data,$sectionid) {
        global $DB;
        $startdate = $data->startdate;
        $enddate = $data->enddate;
        $zoom = new stdClass();
        $start_time = $data->startdate + ($data->starttime['hours'] * 3600) + ($data->starttime['minutes'] * 60);
        $zoom->modulename = 'zoom';
        $zoom->host_id = zoom_get_user_id();
        $zoom->course = (int) $data->courseid;
        $zoom->showdescription = 0;
        $zoom->name =  $data->batchname;
        $zoom->intro = $data->batchname;
        $zoom->introformat = 1;
        $zoom->type = 1;
        $zoom->start_time = $start_time;
        $zoom->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $zoom->timezone = date_default_timezone_get();
        $section =$DB->get_record('course_sections',['id'=>$sectionid]);
        $zoom->section =$section->section;
        if($enddate > $startdate) {
            $zoom->recurring = 1;
            $zoom->recurrence_type = 1;
            $zoom->repeat_interval = 1;
        }
        $zoom->duration =  $data->duration;
        $zoom->visible = 1;
        $zoom->grade = 0;
        $json = \core_availability\tree::get_root_json(array(
            \availability_group\condition::get_json((int)$data->batchid)), \core_availability\tree::OP_AND, false);
        $zoom->availability = json_encode($json);
        $zoom->option_jbh = 0;
        $zoom->option_waiting_room = 1;
        $zoom->end_times = 1;
        $zoom->end_date_time = $data->enddate + ($data->endtime['hours'] * 3600) + ($data->endtime['minutes'] * 60);
        $zoom->end_date_option = 1;
        $zoom->option_mute_upon_entry = 1;
        $zoom->option_waiting_room = 1;
        $zoom->requirepasscode = 1;
        $zoom->monthly_repeat_option = null;
        $moduleinfo =  create_module($zoom); 
        $cdata = new stdClass;
        $cdata->availability = json_encode($json);
        course_update_section((int)$data->courseid,$section,$cdata);
        return['sessionid'=>$moduleinfo->instance,'sectionid'=>$sectionid];
    }

    public function updatezoom($data,$zoommeetingid,$sectionid){
        global $DB, $USER;
        $startdate = $data->startdate;
        $enddate = $data->enddate;
        $zoom = $DB->get_record('zoom', ['id' => $zoommeetingid]);
        $start_time = $data->startdate + ($data->starttime['hours'] * 3600) + ($data->starttime['minutes'] * 60);
        $zoom->course = $data->courseid;
        $zoom->instance = $zoommeetingid;
        $zoom->name = $data->batchname;
        $zoom->intro = $data->batchname;
        $zoom->introformat = 1;
        $zoom->type = 1;
        $zoom->start_time = $start_time;
        $zoom->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $zoom->timezone = date_default_timezone_get();
        if($enddate > $startdate) {
            $zoom->recurring = 1;
            $zoom->recurrence_type = 1;
            $zoom->repeat_interval = 1;
        }
        $zoom->duration =  $data->duration;
        $zoom->visible = 1;
        $zoom->grade = 0;
        $json = \core_availability\tree::get_root_json(array(
            \availability_group\condition::get_json((int)$data->batchid)), \core_availability\tree::OP_AND, false);
        $zoom->availability = json_encode($json);
        $zoom->option_jbh = 0;
        $zoom->option_waiting_room = 1;
        $zoom->end_times = 1;
        $zoom->end_date_time = $data->enddate + ($data->endtime['hours'] * 3600) + ($data->endtime['minutes'] * 60);
        $zoom->end_date_option = 1;
        $zoom->option_mute_upon_entry = 1;
        $zoom->option_waiting_room = 1;
        $zoom->requirepasscode = 1;
        $zoom->monthly_repeat_option = null;
        zoom_update_instance($zoom);
        return['sessionid'=>$zoommeetingid,'sectionid'=>$sectionid];

    }
    public function sessions_data($stable, $filterdata) {
        global $DB,$USER;
        $selectsql = "SELECT lps.*,gp.name AS batchname FROM {local_package_sessions} lps 
                    LEFT JOIN {groups} gp ON gp.id = lps.batchid "; 
        $countsql = "SELECT COUNT(lps.id) 
            FROM {local_package_sessions} lps
            LEFT JOIN {groups} gp ON gp.id = lps.batchid ";
        $formsql = " WHERE lps.courseid = $stable->courseid AND lps.packageid = $stable->packageid ";
        if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
            $formsql .= "   AND 
                            (
                              lps.schedulecode LIKE :schedulecodesearch OR 
                              gp.name LIKE :batchnamesearch
                            )";
            $searchparams = array(
                'schedulecodesearch' => '%'.trim  ($filterdata->search_query).'%'
                ,'batchnamesearch' => '%'.trim($filterdata->search_query).'%'
            );
        }else{
            $searchparams = array();
        }
        $params = array_merge($searchparams);
        $totalsessions = $DB->count_records_sql($countsql.$formsql, $params);
        $formsql .=" ORDER BY lps.id DESC";
        $sessions = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);
        $listofsessions = array();
        $count = 0;
        foreach($sessions as $session) {
            $listofsessions[$count]['id'] = $session->id;
            $listofsessions[$count]['packageid'] = $session->packageid;
            $listofsessions[$count]['sessionid'] = $session->sessionid;
            $listofsessions[$count]['batchid'] = $session->batchid;
            $listofsessions[$count]['courseid'] = $session->courseid;
            $listofsessions[$count]['batchname'] = $session->batchname;
            $listofsessions[$count]['schedulecode'] = $session->schedulecode;
            $listofsessions[$count]['startdate'] = userdate($session->startdate,get_string('strftimedatemonthabbr', 'core_langconfig')) ;
            $listofsessions[$count]['enddate'] = userdate($session->enddate,get_string('strftimedatemonthabbr', 'core_langconfig'));
            $starttime = gmdate("h:i",$session->starttime);
            $endttime = gmdate("h:i",($session->endtime));
            $starttimemeridian = gmdate('a',$session->starttime); 
            $endtimemeridian = gmdate('a',($session->endtime)); 
            $startmeridian = ($starttimemeridian == 'am')?  get_string('am','local_packages'):get_string('pm','local_packages');
            $endmeridian = ($endtimemeridian == 'am')?  get_string('am','local_packages'):get_string('pm','local_packages');
            $listofsessions[$count]['starttime'] =$starttime.' '.$startmeridian;
            $listofsessions[$count]['endtime'] =$endttime .' '.$endmeridian;
            $count++;
        }
        $sessionsContext = array(
            "sessions" => $listofsessions,
            "totalsessions" => $totalsessions,
            "length" => COUNT($listofsessions),
        );
        return $sessionsContext;

   }
   public function set_session_data($id, $ajaxdata=false) {
        global $DB;
        $data = $DB->get_record('local_package_sessions', ['id' => $id], '*', MUST_EXIST);
        $row['id'] = $data->id;
        $row['startdate'] = $data->startdate;
        $row['enddate'] = $data->enddate;
        $row['starttime'] = $data->starttime;
        $row['endtime'] = $data->endtime;
        $row['schedulecode'] = $data->schedulecode;
        $row['teacher'] = $data->teacher;
        $dur_min = $data->starttime/60;
        if($dur_min){
            $hours = floor($dur_min / 60);
            $minutes = ($dur_min % 60);
        }
        $row['starttime[hours]'] = $hours;
        $row['starttime[minutes]'] = $minutes;
        if($data->endtime > 0) {
            $dur__min = $data->endtime/60;
            if($dur__min){
                $hours = floor($dur__min / 60);
                $minutes = ($dur__min % 60);
            } 
            $row['endtime[hours]'] = $hours;
            $row['endtime[minutes]'] = $minutes;

        } else {
            $dur = $data->duration/60;
                if($dur){
                    $hours = floor($dur / 60);
                    $minutes = ($dur % 60);
                } 
            $row['endtime[hours]'] = $row['starttime[hours]'] + $hours;
            $row['endtime[minutes]'] = $row['starttime[minutes]']+ $minutes;
        }
        return $row; 
    }
    public function remove_session($id) {
        global $DB;
        $record = $DB->get_record('local_package_sessions', ['id' => $id]);
        $sections = $DB->get_record('course_sections',['id' => $record->sectionid]);
        try{
            $transaction = $DB->start_delegated_transaction();
            course_delete_section($record->courseid, $sections);
            $this->delete_session($record->sessionid,$record->courseid);
            $DB->delete_records('local_package_sessions',['id' => $id]);
            $transaction->allow_commit();
           return true;
        } catch(moodle_exception $e){
          $transaction->rollback($e);
          return false;
        }
    }

    public function delete_session($zoomid,$courseid) {
        global $DB;
        $mod_name = 'zoom';
        $moduleid = $DB->get_field_sql("SELECT id FROM {modules} WHERE name = '$mod_name'");
        $cmidsql = 'SELECT com.id FROM {course_modules} as com JOIN {course_sections} as cos ON com.section = cos.id AND com.course = cos.course WHERE  cos.course = '.$courseid.'  AND com.module = '.$moduleid.' AND com.instance = '.$zoomid.'';
        $cmid = (int)$DB->get_field_sql($cmidsql);
        if($cmid){
            course_delete_module($cmid);
        }
        
    }
    public function createorupdate_batchcourse($bdata) {
        global $DB,$USER;
        $hierarchy = $DB->get_record('local_hierarchy', ['code' => $bdata->packagecode]);
        $hierarchyid = $hierarchy->id;
        $bdata->hierarchyid = $hierarchyid;
        $parentid = $hierarchy->categoryid;
        $batch = self::createbatch($bdata);
        $bdata->batchid = $batch;
        self::insert_packagecourses($bdata);
        if ($bdata->packagetype > 0) { // Test Type Package
            $course = self::createcourse($bdata);

            $returndata = [
                'id'=>$course->id,
                'fullname'=>$course->fullname,
                'shortname'=>$course->shortname,
                'description' => $course->summary ? format_text($course->summary,FORMAT_HTML) : '',
                'parentcourseid'=>0,
                'logo' => '',
            ];

            $recordid = $DB->get_field('local_packagecourses', 'id', ['hierarchyid' => $hierarchyid, 'parentcourseid' => 0, 'batchid' => $batch]);
            $DB->update_record('local_packagecourses', ['id' => $recordid, 'courseid' => $course->id]);

            $batch_course = new stdClass();
            $batch_course->batchid = $batch;
            $batch_course->courseid = $course->id;
            $batch_course->timecreated = time();
            $batch_course->timemodified = time();
            $batch_course->usermodified = time();
            $DB->insert_record('local_batch_courses', $batch_course);

            return $returndata;
        }
        // Course Type Package
        $courseid = $bdata->courses;
        if(!empty($courseid)){
            $course = $DB->get_record('course', ['id' => $courseid]);

            if ($course) {

                $newcourse['fullname'] = $course->fullname;
                $newcourse['shortname'] = $bdata->code.'_'.$courseid. '_' .$course->shortname;
                $newcourse['categoryid'] = $parentid;
                $newcourse['visible'] = true;
                $options = array(
                            array('name' => 'blocks', 'value' => 1),
                            array('name' => 'activities', 'value' => 1),
                            array('name' => 'filters', 'value' => 1),
                            array('name' => 'users', 'value' => 0)
                        );
    
                $clonedcourse = core_course_external::duplicate_course(
                    $courseid, 
                    $newcourse['fullname'], 
                    $newcourse['shortname'],
                    $newcourse['categoryid'], 
                    $newcourse['visible'], 
                    $options
                );

                if(!empty($bdata->old_id)) {
                    $DB->update_record('course', ['id' => $clonedcourse['id'], 'idnumber' => "BAT_".$bdata->old_id]);
                }

                $hierarchyid = $DB->get_field('local_hierarchy', 'id', ['categoryid' => $parentid]);

                $recordid = $DB->get_field('local_packagecourses', 'id', ['hierarchyid' => $hierarchyid, 'parentcourseid' => $courseid, 'batchid' => $batch]);
                $DB->update_record('local_packagecourses', ['id' => $recordid, 'courseid' => $clonedcourse['id']]);
    
                $batch_course = new stdClass();
                $batch_course->batchid = $batch;
                $batch_course->courseid = $clonedcourse['id'];
                $batch_course->timecreated = time();
                $batch_course->timemodified = time();
                $batch_course->usermodified = time();
                $batch_course_data = $DB->insert_record('local_batch_courses', $batch_course);

    
                $ccfullname = $DB->get_field('course','fullname',['id'=>$clonedcourse['id']]);
                // $parentcourseimage = $DB->get_field('local_subjects','image',['courseid'=>$courseid]);
                // $subjectimageurl = !empty($parentcourseimage) ? $parentcourseimage : '';
                
                $returndata=[
                    'id'=>$clonedcourse['id'],
                    'fullname'=>$ccfullname,
                    'shortname'=>$clonedcourse['shortname'],
                    'description'=>format_text($course->summary,FORMAT_HTML),
                    'parentcourseid'=>$courseid,
                    'logo'=>'',
                ];
            }


            return $returndata;
        }
        
    }
    public function createbatch($bdata) {
        global $DB;
        $hierarchy = $DB->get_record('local_hierarchy', ['code' => $bdata->packagecode]);
        $hierarchyid = $hierarchy->id;
        $bdata->hierarchyid = $hierarchyid;

        $data = new stdClass();
        $data->name = $bdata->name;
        $data->code = $bdata->code;
        $data->enrol_start_date = strtotime($bdata->startdate);
        $data->enrol_end_date = strtotime($bdata->enddate);
        $data->duration = $bdata->duration;
        $data->studentlimit = $bdata->studentlimit;
        $data->provider = $bdata->provider;
        $data->hierarchy_id = $hierarchyid;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->usermodified = time();

        $batch = $DB->insert_record('local_batches', $data);

        return $batch;
    }

    public function createcourse($data) {
        global $DB;
        $courseconfig = get_config('moodlecourse');
        $parentid = $DB->get_field('course_categories', 'id', ['idnumber' => $data->packagecode]);
        
        $courserecord = new stdClass();
        $courserecord->category = $parentid;
        $courserecord->fullname = $data->name;
        $courserecord->idnumber = !empty($data->old_id) ? "BAT_".$data->old_id : 0;
        $courserecord->shortname = $data->code;
        $courserecord->summary = NULL;
        $courserecord->summary_format = true;
        $courserecord->startdate = time();
        $courserecord->enddate = strtotime(date('Y-m-d', strtotime('+1 years')));
        $courserecord->timecreated = time();
        $courserecord->timemodified = time();

        // Apply course default settings
        $courserecord->format             = $courseconfig->format;
        $courserecord->newsitems          = $courseconfig->newsitems;
        $courserecord->showgrades         = $courseconfig->showgrades;
        $courserecord->showreports        = $courseconfig->showreports;
        $courserecord->maxbytes           = $courseconfig->maxbytes;
        $courserecord->groupmode          = $courseconfig->groupmode;
        $courserecord->groupmodeforce     = $courseconfig->groupmodeforce;
        $courserecord->visible            = $courseconfig->visible;
        $courserecord->visibleold         = $courserecord->visible;
        $courserecord->lang               = $courseconfig->lang;
        $courserecord->enablecompletion   = $courseconfig->enablecompletion;
        $courserecord->numsections        = $courseconfig->numsections;

        try {
            $courseinfo = create_course($courserecord);
        } catch (dml_exception $e) {
            print_r($e);
        }
        self::create_coursemodule($data, $courseinfo->id);

        return $courseinfo;
    }
    public function create_coursemodule($data, $courseid)
    {
        global $DB;
        $moduleinfo = new stdClass();
        $moduleinfo->name = $data->name;
        $moduleinfo->modulename = 'quiz';
        $moduleinfo->gradepass = 100;
        $moduleinfo->gradecat = $DB->get_field('course_categories', 'id', ['idnumber' => 'exams']);;
        $moduleinfo->grade = 100;
        $moduleinfo->section = 1;
        $moduleinfo->course = $courseid;
        $moduleinfo->visible = 1;
        $moduleinfo->introeditor['text'] = '';
        $moduleinfo->introeditor['format'] = FORMAT_HTML;
        $moduleinfo->quizpassword = '';
        $moduleinfo->completion = 2;
        $moduleinfo->completiongradeitemnumber = 0;
        $moduleinfo->preferredbehaviour = 'deferredfeedback';
        $moduleinfo->hidden = 0;
        $moduleinfo->overduehandling = 'autosubmit';

        $moduleinfo->attemptimmediately = 1;
        $moduleinfo->correctnessimmediately = 1;
        $moduleinfo->marksimmediately = 1;
        $moduleinfo->specificfeedbackimmediately = 1;
        $moduleinfo->generalfeedbackimmediately = 1;
        $moduleinfo->rightanswerimmediately = 1;
        $moduleinfo->overallfeedbackimmediately = 1;
        $moduleinfo->attemptopen = 1;
        $moduleinfo->correctnessopen = 1;
        $moduleinfo->marksopen = 1;
        $moduleinfo->specificfeedbackopen = 1;
        $moduleinfo->generalfeedbackopen = 1;
        $moduleinfo->rightansweropen = 1;
        $moduleinfo->overallfeedbackopen = 1;
        
        $moduleinfo->questionsperpage = 1;
        $moduleinfo->shuffleanswers = 1;

        $moduleinfo->timeopen = 0;
        $moduleinfo->timeclose = 0;
        $moduleinfo->timelimit = $data->duration;

        $quiz = create_module($moduleinfo);

        return $quiz->id;
    }
    public function create_package($pdata) {
        global $DB,$USER;
        $returndata= array();
        $categoryid = $DB->get_field('local_hierarchy', 'categoryid', ['id' => $pdata->classid]);
        $category = array(
            'name' => $pdata->name, 
            'idnumber' => $pdata->code, 
            'description' => $pdata->description,
            'parent' => $categoryid,
            'visible' => 1, 
            'depth' => 4,
            'timemodified' => time()
        );
        $category = core_course_category::create($category);
        $pdata->categoryid = $category->id;
        $pdata->valid_from = strtotime($pdata->valid_from);
        $pdata->valid_to = strtotime($pdata->valid_to);

        if($category) {
            $DB->insert_record('local_packages', $pdata);
            $hierarchyrecord = 
            [
                'categoryid' => $category->id,
                'parent' =>$pdata->classid,
                'depth' => 4,
                'name' => $pdata->name,
                'code' => $pdata->code,
                'image' => $pdata->imageurl,
                'description' => $pdata->description,
                'timecreated' => time(),
                'usercreated' => $USER->id
            ];
            $hierarchyid = $DB->insert_record('local_hierarchy', $hierarchyrecord);
        }
        return true;
    }
    public function update_package($pdata) {
        global $DB,$USER;
        $returndata= array();
        $coursecategory = $DB->get_field('course_categories','id',['idnumber' => $pdata->code]);
        $category = '';
        if($coursecategory ){
            $category = array(
                // 'id' => $DB->get_field('local_hierarchy', 'categoryid',  ['id' => $pdata->hierarchyid]),
                'id' => $coursecategory,
                'name' => $pdata->name, 
                'description' => $pdata->description,               
                'visible' => 1, 
                'depth' => 4,
                'timemodified' => time()
            );
            $default = core_course_category::get($coursecategory);
            $default->update($category);  
        }else {
            $categoryid = $DB->get_field('local_hierarchy', 'categoryid', ['id' => $pdata->classid]);
            $category = array(
                'name' => $pdata->name, 
                'idnumber' => $pdata->code, 
                'description' => $pdata->description,
                'parent' => $categoryid,
                'visible' => 1, 
                'depth' => 4,
                'timemodified' => time()
            );
            $category = core_course_category::create($category);
        }
        
        // $DB->delete_records('local_packagecourses', ['hierarchyid' => $pdata->hierarchyid]);

        if($category) {
         
            $hierarchyid = $DB->get_field('local_hierarchy', 'id', ['code' => $pdata->code]);
            if($hierarchyid){
                $hierarchyrecord = 
                [
                    'id' => $pdata->hierarchyid,
                    'parent' =>$pdata->classid,
                    'depth' => 4,
                    'name' => $pdata->name,
                    'code' => $pdata->code,
                    'image' => $pdata->imageurl,
                    'description' => $pdata->description,
                    'timemodified' => time(),
                    'usercreated' => $USER->id
                ];
                $hierarchyid = $DB->update_record('local_hierarchy', $hierarchyrecord);

            } else{

                $hierarchyrecord = 
                [
                    'categoryid' => $coursecategory ? $coursecategory: $category->id,
                    'parent' =>$pdata->classid,
                    'depth' => 4,
                    'name' => $pdata->name,
                    'code' => $pdata->code,
                    'image' => $pdata->imageurl,
                    'description' => $pdata->description,
                    'timecreated' => time(),
                    'usercreated' => $USER->id
                ];
                $hierarchyid = $DB->insert_record('local_hierarchy', $hierarchyrecord);
            }
            $packageid = $DB->get_field('local_packages', 'id', ['code' => $pdata->code]);
            if($packageid){
                $pdata->id =  $packageid ; 
                $pdata->valid_from = strtotime($pdata->valid_from);
                $pdata->valid_to = strtotime($pdata->valid_to);         
                $DB->update_record('local_packages', $pdata);

            }else{
                $pdata->valid_from = strtotime($pdata->valid_from);
                $pdata->valid_to = strtotime($pdata->valid_to);
                $DB->insert_record('local_packages', $pdata);
            }
        }

        return true;
    }
    public function insert_packagecourses($pdata) {
        global $DB, $USER;
        $courseid = $pdata->courses;
        $course = $DB->get_record('course',['id'=>$courseid]);

        $record = 
        [
            'goalid' => $pdata->goalid,
            'boardid' =>$pdata->boardid,
            'classid' =>$pdata->classid,
            'hierarchyid' => $pdata->hierarchyid,
            'batchid' => !empty($pdata->batchid) ? $pdata->batchid : 0,
            'lp_id' =>$pdata->packageid,
            'package_type' =>($pdata->package_type == 1) ? 2 : 1, // As per LMS Package type 1 = Course || 2 = test
            'validity_type' =>($pdata->validity_type == 'days') ? 1 :2,
            'validity' =>($pdata->validity_type == 'days')? $pdata->validity:strtotime($pdata->validity),
            'startdate' => strtotime($pdata->startdate),
            'enddate' => strtotime($pdata->enddate),
            'courseid' => 0,
            'parentcourseid' => !empty($courseid) ? $courseid : 0,
            'timecreated' => time(),
            'usercreated' => $USER->id
        ];
        $DB->insert_record('local_packagecourses', $record);
    }
    public function remove_batch($code) {
        global $DB;
        $batchid = $DB->get_field('local_batches', 'id', ['code' => $code]);
        $batchcourses = $DB->get_records('local_batch_courses', ['batchid' => $batchid]);
        foreach($batchcourses as $batchcourse) {
            $DB->delete_records('course', ['id' => $batchcourse->courseid]);
        }
        $DB->delete_records('local_batch_courses', ['batchid' => $batchid]);
        $DB->delete_records('local_batches', ['id' => $batchid]);

        return true;        
    }
    public function remove_package($code) {
        global $DB;
        $package = $DB->get_record('local_hierarchy', ['code' => $code]);
        $packagecourses = $DB->get_records('local_packagecourses', ['hierarchyid' => $package->id]);
        foreach($packagecourses as $packagecourse) {
            $DB->delete_records('course', ['id' => $packagecourse->courseid]);
        }

        $batches = $DB->get_records('local_batches', ['hierarchy_id' => $package->id]);
        if ($batches) {
            foreach($batches as $batch) {
                self::remove_batch($batch->code);
            }
        }

        $ccategory = new stdClass();
        $ccategory->id = $package->categoryid;
        $ccategory->idnumber = uniqid();
        $DB->update_record('course_categories', $ccategory);
        $DB->delete_records('local_hierarchy', ['id' => $package->id]);

        return true;
    }
    public function create_duplicatecourse($params) {
        global $DB, $USER;
        $course = $DB->get_record('course', ['id' => $params['parentcourseid']]);
        $hierarchy = $DB->get_record('local_hierarchy', ['code' => $params['package_code']]);
        $classid = $DB->get_field('local_subjects', 'classessid', ['code' => $params['subject_code']]);

        $newcourse['fullname'] = $params['name'];
        $newcourse['shortname'] = $params['code'];
        $newcourse['categoryid'] = $hierarchy->categoryid;
        $newcourse['visible'] = true;
        $options = array(
            array('name' => 'blocks', 'value' => 1),
            array('name' => 'activities', 'value' => 1),
            array('name' => 'filters', 'value' => 1),
            array('name' => 'users', 'value' => 0)
        );

        $clonedcourse = core_course_external::duplicate_course(
            $course->id, 
            $newcourse['fullname'], 
            $newcourse['shortname'],
            $newcourse['categoryid'], 
            $newcourse['visible'], 
            $options
        );

        $data = new stdClass();
        $data->id = $clonedcourse['id'];
        $data->idnumber = "BAT_".$params['old_id'];

        $DB->update_record('course', $data);

        $sql = "SELECT lc.id as classid, lb.id as boardid, (SELECT lg.id FROM {local_hierarchy} lg WHERE lg.id = lb.parent) as goalid
                  FROM {local_hierarchy} lb
                  LEFT JOIN {local_hierarchy} lc ON lc.parent = lb.id
                 WHERE lc.id = ".$classid;
        $masterdata = $DB->get_record_sql($sql);

        $record = 
        [
            'old_id' => $params['old_id'],
            'goalid' => $masterdata->goalid,
            'boardid' =>$masterdata->boardid,
            'classid' =>$masterdata->classid,
            'hierarchyid' => $hierarchy->id,
            'batchid' => 0,
            'package_type' => 1, // Package Type course.
            'courseid' => $clonedcourse['id'],
            'parentcourseid' => $course->id,
            'timecreated' => time(),
            'usercreated' => $USER->id
        ];

        $DB->insert_record('local_packagecourses', $record);

        $returndata = [
            'id' => $clonedcourse['id'],
            'fullname' => $clonedcourse['fullname'],
            'shortname' => $clonedcourse['shortname'],
            'description'=>format_text($course->summary, FORMAT_HTML),
            'parentcourseid'=>$course->id,
            'logo'=>'',
        ];

        return $returndata;
    }
}
