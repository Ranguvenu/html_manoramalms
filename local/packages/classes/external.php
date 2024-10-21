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
 * Goals hierarchy
 *
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_packages
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/filelib.php");
require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');
use local_packages\local\packages as packages;
//use local_packages\controller as controller;
use cache;
use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use context_course;
use core\external\exporter;
require_once("{$CFG->dirroot}/course/externallib.php");
require_once("{$CFG->dirroot}/local/packages/lib.php");
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
/**
 * local_packages_external [description]
 */
class local_packages_external extends external_api {
    public static function package_view_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
            VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }
 
    public static function package_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $CFG,$PAGE;
        $sitecontext = context_system::instance();
        $PAGE->set_context($sitecontext);
        $params = self::validate_parameters(
            self::package_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $data_object = (json_decode($dataoptions));
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $stable = new \stdClass();
        $stable->thead = true;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = (new packages)->get_packages($stable,$filtervalues);
        return [
            'totalcount' => $data['totalpackages'],
            'records' => $data['packages'],
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
            'nodata' => get_string('nopackages','local_packages')
        ];
    }

    /**
     * Returns description of method result value.
     */
    public static function  package_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of competencies in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'nodata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'packageid' => new external_value(PARAM_RAW, 'id', VALUE_OPTIONAL),
                        'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                        'code' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                        'startdate' => new external_value(PARAM_RAW, 'startdate', VALUE_OPTIONAL),
                        'enddate' => new external_value(PARAM_RAW, 'enddate', VALUE_OPTIONAL),
                        'image' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                        'description' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                        'timecreated' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                        'subjects' => new external_value(PARAM_RAW, 'subjects', VALUE_OPTIONAL),
                    )
                )
            ),
        ]);
    }

    public static function ajaxdatalist_parameters() {
        $query = new external_value(PARAM_RAW, 'search query');
        $type = new external_value(PARAM_ALPHANUMEXT, 'Type of data', VALUE_REQUIRED);
        $packageid = new external_value(PARAM_RAW, 'The package id', VALUE_OPTIONAL,0);
        $courseid = new external_value(PARAM_INT, 'The course id', VALUE_OPTIONAL,0);
        $params = array(
            'query' => $query,
            'type' => $type,
            'packageid' => $packageid,
            'courseid' => $courseid,
        );
        return new external_function_parameters($params);
    }
    public static function ajaxdatalist($query,$type,$packageid = 0,$courseid = 0) {
        global $PAGE;
        $params = array(    
            'query' => $query,
            'type' => $type,
            'packageid' => $packageid,
            'courseid' => $courseid,
        );
        $params = self::validate_parameters(self::ajaxdatalist_parameters(), $params);
        switch($params['type']) { 
            case 'batches':
                $data = (new packages)->get_listof_batches($params['query'],$params['packageid'],$params['courseid']);
            break;
            case 'teachers':
                $data = (new packages)->get_listof_teachers($params['query'],$params['packageid'],$params['courseid']);
            break; 
        }
        return ['status' => true, 'data' => $data];
     }
    public static function ajaxdatalist_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_RAW, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'))
                    )
                ) 
            )
        );
    }
    public static function sessionsinfo_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
            VALUE_DEFAULT, 0),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }
 
    public static function sessionsinfo($options, $dataoptions, $offset = 0, $limit = 0, $filterdata) {
        global $CFG,$PAGE;
        $sitecontext = context_system::instance();
        $PAGE->set_context($sitecontext);
        $params = self::validate_parameters(
            self::sessionsinfo_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $data_object = (json_decode($dataoptions));
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $stable = new \stdClass();
        $stable->thead = true;
        $stable->start = $offset;
        $stable->length = $limit;
        $stable->packageid = $data_object->packageid;
        $stable->courseid = $data_object->courseid;
        $data = (new packages)->sessions_data($stable,$filtervalues);
        $totalcount = $data['totalsessions'];
        return [
            'totalcount' => $data['totalsessions'],
            'records' => $data['sessions'],
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
            'nodata' => get_string('nosessions','local_packages')
        ];
    }

    /**
     * Returns description of method result value.
     */
    public static function  sessionsinfo_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of competencies in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'nodata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'packageid' => new external_value(PARAM_INT, 'packageid'),
                        'sessionid' => new external_value(PARAM_INT, 'sessionid'),
                        'batchid' => new external_value(PARAM_INT, 'batchid'),
                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                        'batchname' => new external_value(PARAM_RAW, 'batchname'),
                        'schedulecode' => new external_value(PARAM_RAW, 'schedulecode'),
                        'startdate' => new external_value(PARAM_RAW, 'startdate'),
                        'enddate' => new external_value(PARAM_RAW, 'enddate'),
                        'starttime' => new external_value(PARAM_RAW, 'starttime'),
                        'endtime' => new external_value(PARAM_RAW, 'endtime'),
                    )
                )
            ),
        ]);
    }

    public static function deletesession_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT,'id',0),
            )
        );
    }
    public static  function deletesession($id){
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::deletesession_parameters(),
            array('id'=>$id)
        );
        self::validate_context($systemcontext);
        if ($id) {
           (new packages)->remove_session($id);
        } else {
          throw new moodle_exception('Error in submission');
        }
        return true;    
    }
    public static function deletesession_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    
    public static function createorupdate_batch_parameters() {
        return new external_function_parameters([
            'goalid' => new external_value(PARAM_INT, 'Goal ID'),
            'boardid' => new external_value(PARAM_INT, 'Board ID'),
            'classid' => new external_value(PARAM_INT, 'Class ID'),
            'packageid' => new external_value(PARAM_INT, 'Packageid ID'),
            'batchid' => new external_value(PARAM_INT, 'Batch ID'),
            'name' => new external_value(PARAM_RAW, 'Batch Name'),
            'code' => new external_value(PARAM_RAW, 'Bathc Code'),
            'startdate' => new external_value(PARAM_RAW, 'Enroll Start date'),
            'enddate' => new external_value(PARAM_RAW, 'Enroll End date'),
            'duration' => new external_value(PARAM_INT, 'Duration'),
            'studentlimit' => new external_value(PARAM_INT, 'Student Limit'),
            'provider' => new external_value(PARAM_INT, 'provider'),
            'courses' =>  new external_value(PARAM_RAW, 'List Of Selected Courses'),
            'packagecode' =>  new external_value(PARAM_TEXT, 'Package code to get Category'),
            'packagetype' =>  new external_value(PARAM_TEXT, 'Package code to get Category'),
            'old_id' => new external_value(PARAM_RAW, 'old_id', 0),
        ]);
    }
    public static function createorupdate_batch($goalid, $boardid, $classid, $packageid, $batchid,$name,$code,$startdate,$enddate,$duration,$studentlimit,$provider,$courses, $packagecode, $packagetype, $old_id = 0) {
        global $DB, $PAGE, $CFG;
        $context = context_system::instance();   
        $params = self::validate_parameters(
            self::createorupdate_batch_parameters(),
            [
                'goalid' => $goalid,
                'boardid' => $boardid,
                'classid' => $classid,
                'packageid' => $packageid,
                'batchid' => $batchid,
                'name' => $name,
                'code' => $code,
                'startdate' => $startdate,
                'enddate' => $enddate,
                'duration' => $duration,
                'studentlimit' => $studentlimit,
                'provider' => $provider,
                'courses' => $courses,
                'packagecode' => $packagecode,
                'packagetype' => $packagetype,
                'old_id' => $old_id,
            ]
        );
       
        if ($batchid && $name && $code &&  $startdate && $enddate && $duration && $studentlimit && $provider) {
            $bdata = new stdClass();
            $bdata->goalid = $goalid;
            $bdata->boardid = $batchid;
            $bdata->classid = $classid;
            $bdata->packageid = $packageid;
            $bdata->batchid = $batchid;
            $bdata->name = $name;
            $bdata->code = $code;
            $bdata->startdate = $startdate;
            $bdata->enddate = $enddate;
            $bdata->duration = $duration*3600;
            $bdata->studentlimit = $studentlimit;
            $bdata->provider = $provider;
            $bdata->courses = $courses;
            $bdata->packagecode = $packagecode;
            $bdata->packagetype = $packagetype;
            $bdata->old_id = $old_id;
            $data[] = (new packages)->createorupdate_batchcourse($bdata);
        } else {
           throw new moodle_exception('Error in creation');
        }
        return  $data;  
    }
    public static function createorupdate_batch_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Cloned course Id'),
                    'fullname' => new external_value(PARAM_RAW, 'Cloned course Full Name'),
                    'shortname' => new external_value(PARAM_RAW, 'Cloned course shortname'),
                    'description' => new external_value(PARAM_RAW, 'Cloned Course Description', VALUE_OPTIONAL),
                    'parentcourseid' => new external_value(PARAM_RAW, 'Master Course Id'),
                    'logo' => new external_value(PARAM_RAW, 'Master Course Logo', VALUE_OPTIONAL),
                )
            ) 
        );
    }

    public static function packagecreation_parameters() {
        return new external_function_parameters([
            'goalid' => new external_value(PARAM_INT, 'Goal ID'),
            'boardid' => new external_value(PARAM_INT, 'Board ID'),
            'classid' => new external_value(PARAM_INT, 'Class ID'),
            'packageid' => new external_value(PARAM_INT, 'Package ID'),
            'name' => new external_value(PARAM_RAW, 'Package Name'),
            'code' => new external_value(PARAM_RAW, 'Package Code'),
            'description' => new external_value(PARAM_RAW, 'Package Description', NULL),
            'imageurl' => new external_value(PARAM_RAW, 'Image URL', ''),
            'startdate' => new external_value(PARAM_RAW, 'Enroll Start date'),
            'enddate' => new external_value(PARAM_RAW, 'Enroll End date'),
            'package_type' => new external_value(PARAM_RAW, 'Enroll End date'),
            'validity_type' => new external_value(PARAM_RAW, 'Enroll End date'),
            'validity' => new external_value(PARAM_RAW, 'Enroll End date'),
            'courses' =>  new external_value(PARAM_RAW, 'List Of Selected Courses', ''),

        ]);
    }
    public static function packagecreation($goalid,$boardid,$classid,$packageid,$name,$code,$description,$imageurl,$startdate,$enddate,$package_type,$validity_type,$validity,$courses='') {
        global $DB, $PAGE, $CFG;
        $context = context_system::instance();   
        $params = self::validate_parameters(
            self::packagecreation_parameters(),
            [
                'goalid' => $goalid,
                'boardid' => $boardid,
                'classid' => $classid,
                'packageid' => $packageid,
                'name' => $name,
                'code' => $code,
                'description'=>$description,
                'imageurl'=>$imageurl,
                'startdate' => $startdate,
                'enddate' => $enddate,
                'package_type' => $package_type,
                'validity_type' => $validity_type,
                'validity' => $validity,
                'courses' => $courses,
            ]
        );

        if ($goalid && $boardid && $classid && $packageid && $name && $code) {
            $bdata = new stdClass();
            $bdata->goalid = $goalid;
            $bdata->boardid = $boardid;
            $bdata->classid = $classid;
            $bdata->packageid = $bdata->lp_id = $packageid;
            $bdata->name = $name;
            $bdata->code = $code;
            $bdata->description = $description;
            $bdata->imageurl = $imageurl;
            $bdata->startdate = $bdata->valid_from = $startdate;
            $bdata->enddate = $bdata->valid_to = $enddate;
            $bdata->package_type = $package_type;
            $bdata->validity_type = $validity_type;
            $bdata->validity = $validity;
            $bdata->courses = $courses;
            $bdata->timecreated = time();

            $hierarchyid = $DB->get_field('local_hierarchy', 'id', ['code' => $code]);       
            $coursecategory =  $DB->get_field('course_categories', 'id', ['idnumber' => $code]);    
            

            if ($hierarchyid || $coursecategory ) {
               
                $bdata->hierarchyid = $hierarchyid;
                $data = (new packages)->update_package($bdata);
            } else {
                $data = (new packages)->create_package($bdata);
            }

        } else {
           throw new moodle_exception('Error in creation');
        }
        return true;
    
 
    }
    public static function packagecreation_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function delete_package_parameters(){
        return new external_function_parameters(
            array(
                'code' => new external_value(PARAM_TEXT, 'code'),
                'type' => new external_value(PARAM_TEXT, 'type'),
            )
        );
    }
    public static  function delete_package($code, $type){
        global $DB;
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::delete_package_parameters(),
           [
            'code' => $code,
            'type' => $type
           ]
        );
        self::validate_context($systemcontext);
        if ($code && $type == 'package') {
            (new packages)->remove_package($code);
        } else {
            (new packages)->remove_batch($code);
        }

        return true;    
    }
    public static function delete_package_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function check_course_enrollment_parameters(){
        return new external_function_parameters(
            array(
                'referenceid' => new external_value(PARAM_INT, 'referenceid'),
                'userid' => new external_value(PARAM_INT, 'userid'),
                'courseid' => new external_value(PARAM_INT, 'courseid'),
            )
        );
    }
    public static  function check_course_enrollment($referenceid, $userid, $courseid){
        global $DB;
        $params=self::validate_parameters(
            self::check_course_enrollment_parameters(),
           [
            'referenceid' => $referenceid,
            'userid' => $userid,
            'courseid' => $courseid
           ]
        );

        $context = context_course::instance($courseid);
        $status =  is_enrolled($context, $userid, '', true);
        
        return ['referenceid' => $referenceid, 'status' => $status];
    }
    public static function check_course_enrollment_returns() {
        return new external_function_parameters(
            array(
                'referenceid' => new external_value(PARAM_INT, 'referenceid'),
                'status' => new external_value(PARAM_RAW, 'status'),
            )
        );
    }

    public static function get_module_info_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', 0),
            )
        );
    }

    public static function get_module_info($courseid)
    {
        global $DB;

        $params = self::validate_parameters(
            self::get_module_info_parameters(),
            ['courseid' => $courseid]
        );

        $activitydata = [];

        if ($params['courseid'] > 0) {
            try {
                $params = self::validate_parameters(
                    self::get_module_info_parameters(),
                    ['courseid' => $courseid]
                );

                if ($params['courseid'] <= 0) {
                    throw new invalid_parameter_exception('Invalid course ID');
                }
                $activitydata = [];
                $modinfo = get_fast_modinfo($courseid);
                $activities = $modinfo->get_cms();

                $courseData = [];

                foreach ($activities as $activity) {
                    $sectionInfo = $activity->section;

                    $sectionId = is_object($sectionInfo) ? $sectionInfo->_id : $activity->section;
                    if ($sectionId) {
                        $sectionname = $DB->get_field('course_sections', 'name', array('id' => $sectionId));

                        $sectionname = $sectionname ? $sectionname : "";
                    }

                    $topicexists = false;
                    foreach ($courseData as &$courseTopic) {
                        if ($courseTopic['id'] == $sectionId) {
                            $topicexists = true;

                            $activitydata = (object)[
                                'id' => $activity->id,
                                'name' => $activity->name,
                                'type' => $activity->modname,
                            ];

                            $courseTopic['topics'][] = $activitydata;
                            break;
                        }
                    }

                    if (!$topicexists) {
                        $activitydata = (object)[
                            'id' => $activity->id,
                            'name' => $activity->name,
                            'type' => $activity->modname,
                        ];

                        $courseData[$sectionId] = [
                            'id' => $sectionId,
                            'name' => $sectionname,
                            'topics' => [$activitydata],
                        ];
                    }
                }
                return $courseData;
            } catch (Exception $e) {
                // Handle exceptions
                return $e->getMessage(); // You can modify this to return an error structure if needed.
            }
        }
    }

    public static function get_module_info_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'name' => new external_value(PARAM_TEXT, 'Section Name'),
                    'topics' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'Topic ID'),
                                'name' => new external_value(PARAM_TEXT, 'Topic Name'),
                                'type' => new external_value(PARAM_TEXT, 'Topic Type'),
                            )
                        )
                    ),
                )
            ),
        );
    }

    public static function mycourses_info_parameters() {
        return new external_function_parameters([
          
        ]);
    }

    public static function mycourses_info(){
        global $DB,$USER,$CFG;
        
        $params = self::validate_parameters(
                    self::mycourses_info_parameters(),
                    [
                    ]
                );
        
        $record = users_enrolled_info();
        
        return [
            'categories' => $record['categorydetails'],
        ];

    }

    public static function mycourses_info_returns(){
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'courses' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'Course id'),
                                    'fullname' => new external_value(PARAM_RAW, 'Course name'),
                                    'shortname' => new external_value(PARAM_RAW, 'Course name'),
                                    'progress' => new external_value(PARAM_INT, 'Course progress'),
                                    'startdate' => new external_value(PARAM_INT, 'startdate'),
                                    'enddate' => new external_value(PARAM_INT, 'Course enddate'),
                                    'image_url' => new external_value(PARAM_RAW, 'Course  image'),
                                )
                            )
                        ),
                    'id' => new external_value(PARAM_INT, 'category id'),
                    'name' => new external_value(PARAM_RAW, 'category name '),
                    'progress' => new external_value(PARAM_INT, 'category progress'),
                    'count' => new external_value(PARAM_INT, 'course count',VALUE_OPTIONAL),
                    'validtill' => new external_value(PARAM_INT, 'validtill'),
                    'img_url' => new external_value(PARAM_RAW, 'catimg_url'),
                    'enddate' => new external_value(PARAM_INT, 'enddate'),
                    )
                )
            )
        ]);
    }

    public static function recommended_courses_parameters() {
        return new external_function_parameters([]);
    }

    public static function recommended_courses(){
        global $DB,$USER,$CFG;
            if($USER->id){
               $record = recommended_coures_info($USER->id);
            }
        return [
            'courses' => $record['rcursedetails'],
        ];
       
    }

    public static function recommended_courses_returns(){
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Course id'),
                        'fullname' => new external_value(PARAM_RAW, 'Course fullname'),
                        'shortname' => new external_value(PARAM_RAW, 'Course shortname'),
                        'startdate' => new external_value(PARAM_RAW, 'startdate'),
                        'enddate' => new external_value(PARAM_RAW, 'Course enddate'),
                        'image_url' => new external_value(PARAM_RAW, 'Course  image'),
                        'launch_url' => new external_value(PARAM_RAW, 'Course launchurl'),
                    )
                )
            ),
        ]);
    }

    /**
     * [test_parameters description]
     * @param $userid
     */
    public static function test_courses_parameters() {
        return new external_function_parameters([]);
    }  

    /**
     * [test description get all the test courses]
     * @param $userid
     */
    public static function test_courses(){
        global $DB,$USER,$CFG;
            if($USER->id){
                $record = get_test_courses($USER->id);
            }

        return [
            'courses' => $record['testcourses'],
        ];
       
    }

    public static function test_courses_returns(){
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Course id'),
                        'fullname' => new external_value(PARAM_RAW, 'Course fullname'),
                        'shortname' => new external_value(PARAM_RAW, 'Course shortname'),
                        'startdate' => new external_value(PARAM_RAW, 'startdate'),
                        'enddate' => new external_value(PARAM_RAW, 'Course enddate'),
                        'image_url' => new external_value(PARAM_RAW, 'Course  image'),
                    )
                )
            ),
        ]);
    }
    
    /**
     * [get_due_activities description]
     * @param $userid
     */
    public static function due_activities_parameters() {
        return new external_function_parameters(
            []
        );
        
    }  

    /**
     * [get_due_activities description]
     * @param 
     */
    public static function due_activities(){
        global $DB,$USER,$CFG;
      
            $params = self::validate_parameters(
                        self::due_activities_parameters(),[]
                    );

        $data = due_activities_list();  
     
        return [
            'dueactivites' => $data['dueactivity']
        ];
       
    }

    public static function due_activities_returns() {
        $structure = \core_calendar\external\calendar_event_exporter::get_read_structure();
        
        return new external_single_structure([
            'dueactivites' => new external_multiple_structure(
                $structure,
             )
        ]);
    }

    /**
     * [get_packages_data_parameters description]
     * @param 
     */
    public static function get_packages_data_parameters() {
        return new external_function_parameters(
            [
            'code' => new external_value(PARAM_RAW, 'code'),
            'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL),
            'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL),
            ]
        );
        
    }  

    /**
     * [get_packages_data description]
     * @param $goalcode 
     * @param $perpage 
     * @param $page 
     */
    public static function get_packages_data($code, $perpage = 1, $page = 1){
        global $DB,$USER,$CFG;  
            $params = self::validate_parameters(
                        self::get_packages_data_parameters(),
                        [
                            'code' => $code,
                            'perpage' => $perpage,
                            'page' => $page,
                        ]
                    );        
            require_once($CFG->libdir.'/filelib.php');
            $curl = new \curl(['debug' => 0]);
            $curl->setHeader(['Content-Type: application/json']);
            $url = get_config('auth_lbssomoodle', 'laravel_site_url').get_config('local_packages', 'packagesurl'); 
            $response = $curl->post($url, json_encode(['goalcode' => $code, 'perpage' => $perpage, 'page' => $page]));
            $data = json_decode($response);
            if (isset ($data->meta->total)) {
                $data->total = $data->meta->total;
            } else {
                $data->total = 0;
            }
            $data->perpage = $perpage;
            $data->currentpage = $page;

        return $data;
    }
    /**
     * [get_packages_data_returns description]
     * @param 
     */
    public static function get_packages_data_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'page' => new external_value(PARAM_RAW, 'page'),
                        'title' => new external_value(PARAM_RAW, 'title'),
                        'thumbnail' => new external_value(PARAM_RAW, 'thumbnail'),
                        'pricing' => new external_single_structure(
                            array(
                                'actual_price' => new external_value(PARAM_RAW, 'actual_price', VALUE_OPTIONAL),
                                'selling_price' => new external_value(PARAM_RAW, 'selling_price', VALUE_OPTIONAL),
                            )
                        ),
                        'valid_from' => new external_value(PARAM_RAW, 'validfrom'),
                        'valid_to' => new external_value(PARAM_RAW, 'valid to'),
                    )
                )
            ),
            'total' => new external_value(PARAM_INT, 'total'),
            'perpage' => new external_value(PARAM_INT, 'perpage'),
            'currentpage' => new external_value(PARAM_INT, 'currentpage'),
        ]);
       
    }

    /**
     * [get_hierarchy_data_parameters description]
     * @param 
     */
    public static function get_hierarchy_data_parameters() {
        return new external_function_parameters(
            [
                'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL),
                'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL),
            ]
        );
        
    }  

    /**
     * [get_hierarchy_data description]
     * @param $code
     * @param $perpage
     * @param $page
     */
    public static function get_hierarchy_data($perpage =10, $page=1){
        global $DB, $CFG;  
        $params = self::validate_parameters(
            self::get_hierarchy_data_parameters(),
                [
                    'perpage' => $perpage,
                    'page' => $page,
                ]
        );        
            
            require_once($CFG->libdir.'/filelib.php');
            $curl = new \curl(['debug' => 0]);
            $curl->setHeader(['Content-Type: application/json']);
            $url = get_config('auth_lbssomoodle', 'laravel_site_url').get_config('local_packages', 'hierarchyurl');
            $response = $curl->post($url, json_encode(['depth' => 0, 'code' => explode(',', get_config('local_packages', 'displayablegoals')), 'perpage' => $perpage, 'page' => $page]));
            $dataresult = [];
            $perpage = $perpage;
            $total = 0;
            if (!empty(json_decode($response))) {
                $data = json_decode($response);
                $dataresult = $data->data;
                $perpage = $data->meta->per_page;
                $total = $data->meta->total;
            }
        
        return [
            // 'data' => $data->data,
            'data' => $dataresult,
            'perpage' => $perpage,
            'total' => $total,
        ];
    }
    /**
     * [get_hierarchy_data_returns description]
     * @param 
     */
    public static function get_hierarchy_data_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'title' => new external_value(PARAM_RAW, 'title'),
                        'code' => new external_value(PARAM_RAW, 'code'),
                    )
                ),
            ),
            'perpage' => new external_value(PARAM_INT, 'perpage'),
            'total' => new external_value(PARAM_INT, 'total'),
        ]); 
    }
    /**
     * [create_batchcourse_parameters]
     * @param 
     */
    public static function create_batchcourse_parameters() {
        return new external_function_parameters(
            [
                'old_id' => new external_value(PARAM_INT, 'old_id'),
                'subject_code' => new external_value(PARAM_RAW, 'subject_code'),
                'package_code' => new external_value(PARAM_RAW, 'package_code'),
                'parentcourseid' => new external_value(PARAM_RAW, 'parentcourseid'),
                'name' => new external_value(PARAM_RAW, 'name'),
                'code' => new external_value(PARAM_RAW, 'code'),
                'description' => new external_value(PARAM_RAW, 'description', ''),
                'thumbnail' => new external_value(PARAM_RAW, 'thumbnail', ''),
            ]
        );
    }  

    /**
     * [create_batchcourse]
     * @param $code
     * @param $perpage
     * @param $page
     */
    public static function create_batchcourse($old_id, $subject_code, $package_code, $parentcourseid, $name, $code, $description='', $thumbnail=''){
        global $DB,$USER,$CFG;  
        $params = self::validate_parameters(
            self::create_batchcourse_parameters(),
            [
                'old_id' => $old_id,
                'subject_code' => $subject_code,
                'package_code' => $package_code,
                'parentcourseid' => $parentcourseid,
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'thumbnail' => $thumbnail,
            ]
        );

        $data[] = (new packages)->create_duplicatecourse($params);
        
        return $data;
    }
    /**
     * [create_batchcourse_returns]
     * @param 
     */
    public static function create_batchcourse_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Cloned course Id'),
                    'fullname' => new external_value(PARAM_RAW, 'Cloned course Full Name'),
                    'shortname' => new external_value(PARAM_RAW, 'Cloned course shortname'),
                    'description' => new external_value(PARAM_RAW, 'Cloned Course Description', VALUE_OPTIONAL),
                    'parentcourseid' => new external_value(PARAM_RAW, 'Master Course Id'),
                    'logo' => new external_value(PARAM_RAW, 'Master Course Logo', VALUE_OPTIONAL),
                )
            ) 
        );
    }



    public static function quiz_attempt_submit_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'attemptid' => new external_value(PARAM_INT, 'quiz attempt id', 0),
                'cmid' => new external_value(PARAM_INT, 'course module id', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function quiz_attempt_submit($action, $attemptid, $cmid, $confirm) {
        global $COURSE, $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $systemcontext = context_system::instance();
        $params = self::validate_parameters(
            self::quiz_attempt_submit_parameters(),
            [
                'attemptid' => $attemptid,
                'cmid' => $cmid,
            ]
        );

        $result = [];
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
        $summarydata = [];
        $summarydata['attemptid'] = $params['attemptid'];
        $summarydata['cmid'] = $params['cmid'];
        $summarydata['id'] = $attemptobj->get_courseid();

        $attempted = [];
        $object = new stdClass();
        $answeredcount = 0;
        $wrongcount = 0;
        $unansweredcount = 0;
        $totalquetions = 0;
        $notvisited = 0;
        $objattempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);

        if ($objattempt) {
            $examname = $DB->get_field('quiz', 'name', ['id' => $objattempt->quiz]);
            $object->examname = $examname;
            $object->coursename = $COURSE->fullname;
            $object->attempt = $objattempt->attempt;
            $sql = " SELECT count(quesa.questionid) as cquestions ";
            $sql .= " FROM {question_attempts} quesa
                      JOIN {question_attempt_steps} qas ON qas.questionattemptid = quesa.id ";
            $caheresql = " WHERE qas.state LIKE 'complete' ";
            $cwheresql = " WHERE qas.state LIKE 'gradedright' ";
            $wwheresql = " WHERE qas.state LIKE 'gradedwrong' ";
            $uwheresql = " WHERE qas.state LIKE 'gaveup' ";
            $andsql = " AND quesa.questionusageid = ?
                        AND qas.userid = ? AND qas.sequencenumber = ?";
            $casql = $sql . $caheresql . $andsql;
            $csql = $sql . $cwheresql . $andsql;
            $wsql = $sql . $wwheresql . $andsql;
            $usql = $sql . $uwheresql . $andsql;

            $nsql = "SELECT count(*) as notvisited FROM {question_attempts} WHERE questionusageid = ? AND flagged = ?";
            $notvisited = $DB->get_record_sql($nsql, ['questionusageid' => $objattempt->uniqueid, 'flagged' => 0]);

            $totalsql = "SELECT COUNT(*) AS total_questions
                    FROM mdl_quiz_slots qs
                    WHERE qs.quizid = :quizid";

            $totalquetions = $DB->get_record_sql($totalsql, ['quizid' => $objattempt->quiz]);

            $answeredcount = $DB->count_records_sql($casql, [$objattempt->uniqueid, $USER->id, 1]);

            $result['answeredcount'] = $answeredcount;


            $result['totalquetions'] = $totalquetions->total_questions;
            $gettimesql = "SELECT qa.userid, qa.quiz, qa.uniqueid, qa.state, qa.timestart, qa.timefinish, q.timeclose, q.timelimit
                            FROM {quiz_attempts} qa
                            JOIN {quiz} q ON qa.quiz = q.id
                            WHERE qa.userid = :userid AND qa.quiz = :quiz AND qa.state = :state 
                            ORDER BY qa.id DESC
                            LIMIT 1;";

            $minutes = $DB->get_record_sql($gettimesql, ['userid' => $USER->id, 'quiz' => $objattempt->quiz, 'state' => 'inprogress' ]);

            $minutes = $DB->get_record_sql($gettimesql, ['userid' => $USER->id, 'quiz' => $objattempt->quiz, 'state' => 'inprogress']);

            if ($minutes->timelimit) {
                $difference = $minutes->timestart + $minutes->timelimit - time();

                // Calculate hours, minutes, and seconds
                $hours = floor($difference / (60 * 60));
                $difference %= (60 * 60);
                $minutes = floor($difference / 60);
                $seconds = $difference % 60;

                // Format the time
                $formatted_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $formatted_time = 'Open';
            }

            $result['unansweredcount'] = $totalquetions->total_questions - $answeredcount;
            $result['remainingtime'] = $formatted_time;
            $result['notvisited'] = $notvisited->notvisited;

        }

        if ($confirm) {
            return ['result' => $result];
        } else {
            $return = false;
        }
    }

    public static function quiz_attempt_submit_returns()
    {
        return new external_single_structure(
            array(
                'result' => new external_single_structure(
                    array(
                        'answeredcount' => new external_value(PARAM_INT, 'Answered Count'),
                        'unansweredcount' => new external_value(PARAM_INT, 'Unanswered Count'),
                        'totalquetions' => new external_value(PARAM_INT, 'totalquetions Count'),
                        'remainingtime' => new external_value(PARAM_RAW, 'remainingtime', 0),
                        'notvisited' => new external_value(PARAM_INT, 'quetions not visited'),
                        
                    )
                ),
            )
        );
    }
}
