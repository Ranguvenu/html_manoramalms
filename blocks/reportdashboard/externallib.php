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
 * LearnerScript Dashboard block plugin installation.
 *
 * @package    block_reportdashboard
 * @author     Arun Kumar Mukka
 * @copyright  2018 eAbyas Info Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once("$CFG->libdir/externallib.php");

use block_learnerscript\local\ls;
use block_learnerscript\local\reportbase;
use block_reportdashboard\local\reportdashboard;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_system as contextsystem;
global $CFG, $DB, $USER, $OUTPUT, $COURSE;
require_login();
class block_reportdashboard_external extends external_api {
    public static function userlist_parameters() {
        return new external_function_parameters(
            array(
                'term' => new external_value(PARAM_TEXT, 'The current search term in the search box', false, ''),
                '_type' => new external_value(PARAM_TEXT, 'A "request type", default query', false, ''),
                'query' => new external_value(PARAM_TEXT, 'Query', false, ''),
                'action' => new external_value(PARAM_TEXT, 'Action', false, ''),
                'userlist' => new external_value(PARAM_TEXT, 'Users list', false, ''),
                'reportid' => new external_value(PARAM_INT, 'Report ID', false, 0),
                'maximumSelectionLength' => new external_value(PARAM_INT, 'Maximum Selection Length to Search', false, 0),
                'setminimumInputLength' => new external_value(PARAM_INT, 'Minimum Input Length to Search', false, 2),
                'courses' => new external_value(PARAM_RAW, 'Course id of report', false)
            )
        );
    }
    public static function userlist($term, $_type, $query, $action, $userlist, $reportid, $maximumSelectionLength, $setminimumInputLength, $courses) {
        global $DB;
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE id > 2 AND deleted = 0 AND (firstname LIKE '%" . $term . "%' OR lastname LIKE '%" . $term . "%' OR username LIKE '%" . $term . "%' OR email LIKE '%" . $term . "%' )");
        $reportclass = (new ls)->create_reportclass($reportid);
        $reportclass->courseid = $reportclass->config->courseid;
        if ($reportclass->config->courseid == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($reportclass->config->courseid);
        }
        $data = array();
        $permissions = (isset($reportclass->componentdata['permissions'])) ? $reportclass->componentdata['permissions'] : array();
        $contextlevel = $_SESSION['ls_contextlevel'];
        $role = $_SESSION['role'];
        foreach ($users as $user) {
            if ($user->id > 2) {
                $rolewiseusers = "SELECT  u.*  
                                FROM {user} AS u
                                JOIN {role_assignments}  AS lra ON lra.userid = u.id 
                                JOIN {role} AS r ON r.id = lra.roleid
                                JOIN {context} AS ctx ON ctx.id  = lra.contextid
                                WHERE u.confirmed = 1 AND u.suspended = 0  AND u.deleted = 0 AND u.id = $user->id AND ctx.contextlevel = :contextlevel AND r.shortname = ':role'"; 
                if(isset($role) && ($role == 'manager' || $role == 'editingteacher' || $role == 'teacher' || $role == 'student') && ($contextlevel == CONTEXT_COURSE)){
                        if ($courses <> SITEID) {
                            $rolewiseusers .= " AND ctx.instanceid = :courses";
                        }
                }
                $params = ['contextlevel' => $contextlevel, 'role' => $role, 'courses' => $courses];
                $rolewiseuser = $DB->get_record_sql($rolewiseusers, $params);
                if (!empty($rolewiseuser)) {
                    $contextlevel = $_SESSION['ls_contextlevel'];
                    $userroles = (new ls)->get_currentuser_roles($rolewiseuser->i, $contextleveld);
                    $reportclass->userroles = $userroles;
                    if ($reportclass->check_permissions($rolewiseuser->id, $context)) {
                        $data[] = ['id' => $rolewiseuser->id, 'text' => fullname($rolewiseuser)];
                    }
                }
            } else {
                $userroles = (new ls)->get_currentuser_roles($user->id);
                $reportclass->userroles = $userroles;
                if ($reportclass->check_permissions($user->id, $context)) {
                    $data[] = ['id' => $user->id, 'text' => fullname($user)];
               }
           } 
        }
        $return = ['total_count' => count($data), 'items' => $data];
        $data = json_encode($return);
        return $data;
    }
    public static function userlist_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function reportlist_parameters() {
        return new external_function_parameters(
            array(
                'search' => new external_value(PARAM_RAW, 'Search value', false, ''),
            )
        );
    }
    public static function reportlist($search) {
        $context = context_system::instance();
        $search = 'admin';
        $sql = "SELECT id, name FROM {block_learnerscript} WHERE visible = 1 AND name LIKE '%$search%'";
        $params = ["'%" . $search ."%'"];
        $courselist = $DB->get_records_sql($sql, $params);
        $activitylist = array();
        foreach ($courselist as $cl) {
            global $CFG;
            if (!empty($cl)) {
                $checkpermissions = (new reportbase($cl->id))->check_permissions($USER->id, $context);
                if (!empty($checkpermissions) || has_capability('block/learnerscript:managereports', $context)) {
                    $modulelink = html_writer::link(new moodle_url('/blocks/learnerscript/viewreport.php',
                                array('id' => $cl->id)), $cl->name, array('id' => 'viewmore_id'));
                    $activitylist[] = ['id' => $cl->id, 'text' => $modulelink];
                }
            }
        }
        $termsdata = array();
        $termsdata['total_count'] = count($activitylist);
        $termsdata['incomplete_results'] = true;
        $termsdata['items'] = $activitylist;
        $return = $termsdata;
        $data = json_encode($return);
        return $data;
    }

    public static function reportlist_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function sendemails_parameters() {
        return new external_function_parameters(
            array(
                'reportid' => new external_value(PARAM_INT, 'Report ID', false, 0),
                'instance' => new external_value(PARAM_INT, 'Reprot Instance', false),
                'pageurl' => new external_value(PARAM_LOCALURL, 'Page URL', false, ''),
            )
        );

    }
    public static function sendemails($reportid, $instance, $pageurl) {
        global $CFG, $PAGE;
        $PAGE->set_context(context_system::instance());
        $pageurl = $pageurl ? $pageurl : $CFG->wwwroot . '/blocks/reportdashboard/dashboard.php';
        require_once($CFG->dirroot . '/blocks/reportdashboard/email_form.php');
        $emailform = new analytics_emailform($pageurl, array('reportid' => $reportid, 'AjaxForm' => true, 'instance' => $instance));
        $return = $emailform->render();
        $data = json_encode($return);
        return $data;
    }

    public static function sendemails_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function inplace_editable_dashboard_parameters() {
        return new external_function_parameters(
            array(
                'prevoiusdashboardname' => new external_value(PARAM_TEXT, 'The Prevoius Dashboard Name', false, ''),
                'pagetypepattern' => new external_value(PARAM_TEXT, 'The Page Patten Type', false, ''),
                'subpagepattern' => new external_value(PARAM_TEXT, 'The Sub Page Patten Type', false, ''),
                'value' => new external_value(PARAM_TEXT, 'The Dashboard Name', false, ''),
            )
        );
    }
    public static function inplace_editable_dashboard($prevoiusdashboardname, $pagetypepattern, $subpagepattern, $value) {
        global $DB, $PAGE;
        $explodepetten = explode('-', $pagetypepattern);
        $dashboardname = str_replace (' ', '', $value);
        if (strlen($dashboardname) > 30 || empty($dashboardname)) {
            return $prevoiusdashboardname;
        }
        $update = $DB->execute("UPDATE {block_instances} SET subpagepattern = '$dashboardname' WHERE subpagepattern = '$subpagepattern'");
        if ($update) {
            return $dashboardname;
        } else {
            return false;
        }
    }
    public static function inplace_editable_dashboard_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function addtiles_to_dashboard_is_allowed_from_ajax() {
        return true;
    }
    public static function addtiles_to_dashboard_parameters() {
        return new external_function_parameters(
            array(
                'role' => new external_value(PARAM_TEXT, 'Role', false),
                'dashboardurl' => new external_value(PARAM_TEXT, 'Created Dashboard Name', false),
                'contextlevel' => new external_value(PARAM_INT, 'contextlevel of role', false),
            )
        );
    }
    public static function addtiles_to_dashboard($role, $dashboardurl, $contextlevel) {
        global $PAGE, $CFG, $DB;
        $contextlevel = $_SESSION['ls_contextlevel'];
        $PAGE->set_context(context_system::instance());
        $context = context_system::instance();
        if ((has_capability('block/learnerscript:managereports', $context) ||
            has_capability('block/learnerscript:manageownreports', $context) ||
            is_siteadmin())) {
            require_once $CFG->dirroot . '/blocks/reportdashboard/reporttiles_form.php';
            $seturl = !empty($role) ? '/blocks/reportdashboard/dashboard.php?role='.$role.'&contextlevel='.$contextlevel : '/blocks/reportdashboard/dashboard.php';
            if($dashboardurl != ''){
                $seturl = !empty($role) ? '/blocks/reportdashboard/dashboard.php?role='.$role.'&contextlevel='.$contextlevel.'&dashboardurl='.$dashboardurl.'' :'/blocks/reportdashboard/dashboard.php?dashboardurl='.$dashboardurl.'';
            }
            $staticreports = $DB->get_records_sql("SELECT id FROM {block_learnerscript}
                                                WHERE type = 'statistics' AND visible = :visible AND global = :global", ['visible' => 1, 'global' => 1]);
            $reporttiles = new reporttiles_form($CFG->wwwroot.$seturl);
            $rolereports = (new ls)->listofreportsbyrole($coursels, true, $parentcheck);
            if(!empty($rolereports)){
                $return = $reporttiles->render();
            } else{
                $return = '<div class="alert alert-info">'.get_string('statisticsreportsnotavailable',  'block_reportdashboard').'</div>';
            }
        } else {
            $terms_data = array();
            $terms_data['error'] = true;
            $terms_data['type'] = 'Warning';
            $terms_data['cap'] = true;
            $terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
            $return = $terms_data;
        }
        $data = json_encode($return);
        return $data;
    }
    public static function addtiles_to_dashboard_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
    public static function addwidget_to_dashboard_is_allowed_from_ajax() {
        return true;
    }
    public static function addwidget_to_dashboard_parameters() {
        return new external_function_parameters(
            array(
                'role' => new external_value(PARAM_TEXT, 'Role', false),
                'dashboardurl' => new external_value(PARAM_TEXT, 'Created Dashboard Name', false),
                'contextlevel' => new external_value(PARAM_INT, 'contextlevel of role', false),
            )
        );
    }
    public static function addwidget_to_dashboard($role, $dashboardurl, $contextlevel) {
        global $PAGE, $CFG, $DB;
        $contextlevel = $_SESSION['ls_contextlevel'];
        $PAGE->set_context(context_system::instance());
        $context = context_system::instance();
        if ((has_capability('block/learnerscript:managereports', $context) ||
            has_capability('block/learnerscript:manageownreports', $context) ||
            is_siteadmin())) {
            $seturl = !empty($role) ? '/blocks/reportdashboard/dashboard.php?role='.$role.'&contextlevel='.$contextlevel : '/blocks/reportdashboard/dashboard.php';
            if($dashboardurl != ''){
                //$contextlevel = $_SESSION['ls_contextlevel'];
                $seturl = !empty($role) ? '/blocks/reportdashboard/dashboard.php?role='.$role.'&contextlevel='.$contextlevel.'&dashboardurl='.$dashboardurl.'' :'/blocks/reportdashboard/dashboard.php?dashboardurl='.$dashboardurl.'';
            }
            $coursels = false;
            $parentcheck = false;
            if ($dashboardurl == 'Course') {
                $coursels = true;
                $parentcheck = false;
            }
            require_once $CFG->dirroot . '/blocks/reportdashboard/reportselect_form.php';
            $reportselect = new reportselect_form($CFG->wwwroot.$seturl, array('coursels' => $coursels, 'parentcheck' => $parentcheck));
            $rolereports = (new ls)->listofreportsbyrole($coursels, false, $parentcheck);
            if(!empty($rolereports)) {
                $return = $reportselect->render();
            } else{
                $return = '<div class="alert alert-info">'.get_string('customreportsnotavailable',  'block_reportdashboard').'</div>';
            }
        } else {
            $terms_data = array();
            $terms_data['error'] = true;
            $terms_data['type'] = 'Warning';
            $terms_data['cap'] = true;
            $terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
            $return = $terms_data;
        }
        $data = json_encode($return);
        return $data;
    }
    public static function addwidget_to_dashboard_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function studentprofiledata_view_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'userid', ''),
            'packageid' => new external_value(PARAM_INT, 'packageid', '')
        ]);
    }

    public static function studentprofiledata_view($userid, $packageid) {
        global $CFG,$PAGE, $USER;
        $userid = $USER->id;
        $sitecontext = context_system::instance();
        $PAGE->set_context($sitecontext);
        $params = self::validate_parameters(
            self::studentprofiledata_view_parameters(),
            [
                'userid' => $userid,
                'packageid' => $packageid,
            ]
        );
        $reportrender = $PAGE->get_renderer('local_reportdashboards');
        $packageslist = $reportrender->get_packageslist($userid, $packageid);
          foreach ($packageslist as $package) {
            //echo '<pre>';print_r($package);exit;
            $coursewisedata = array();
            $coursewisedatalist = $reportrender->get_coursewisedata($userid, $package['id']);
            //echo '<pre>';print_r($coursewisedatalist);exit;
            foreach ($coursewisedatalist as $course) {
                $coursewisedata[] = ['courseid' => $course['id'],
                                    'coursename' => $course['coursename'],
                                    'coursepercentage' => $course['coursepercentage'],
                                    'averagegrade' => $course['averagegrade'],
                                    'reading' => $course['reading'],
                                    'practicetest' => $course['practicetest'],
                                    'userattendance' => $course['userattendance']
                                ];
            }

            $records['packages'][] = ['packageid' => $package['id'],
                                    'name' => $package['name'],
                                    'selectedpackage' => 1,
                                    'coursewisedata' => $coursewisedata
                                ];
        }
         return [ 'records' => $records];
    }

    /**
     * Returns description of method result value.
     */
    public static function  studentprofiledata_view_returns() {
        return new external_single_structure([
            'records' => new external_single_structure(
                    array(
                        'packages' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'packageid' => new external_value(PARAM_RAW, 'packageid', VALUE_OPTIONAL),
                                    'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                                    'selectedpackage' => new external_value(PARAM_RAW, 'selectedpackage', VALUE_OPTIONAL),
                                    'coursewisedata' => new external_multiple_structure(
                                         new external_single_structure(
                                            array(
                                                'courseid' => new external_value(PARAM_RAW, 'courseid'),
                                                 'coursename' => new external_value(PARAM_RAW, 'fullname'),
                                                 'coursepercentage' => new external_value(PARAM_RAW, 'coursepercentage'),
                                                 'averagegrade' => new external_value(PARAM_RAW, 'averagegrade'),
                                                 'reading' => new external_value(PARAM_RAW, 'reading'),
                                                 'practicetest' => new external_value(PARAM_RAW, 'practicetest'),
                                                 'userattendance' => new external_value(PARAM_RAW, 'userattendance'),
                                            )
                                    ), '', VALUE_OPTIONAL),
                                )
                                )
                            , '', VALUE_OPTIONAL),
                    )
                )
        ]);
    }

    public static function coursetabs_data_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
            'userid' => new external_value(PARAM_INT, 'userid', ''),
            'startdate' => new external_value(PARAM_RAW, 'startdate', ''),
            'duedate' => new external_value(PARAM_RAW, 'duedate', ''),
            'lsstartdate' => new external_value(PARAM_INT, 'lsstartdate', ''),
            'lsduedate' => new external_value(PARAM_INT, 'lsduedate', '')
        ]);
    }

    public static function coursetabs_data($courseid, $userid, $startdate, $duedate, $lsstartdate, $lsduedate) {
        global $DB, $PAGE, $USER;
        $PAGE->set_context(context_system::instance());
        $userid = $userid;
        if (!empty($courseid) && !empty($userid)) {
            $reportrender = $PAGE->get_renderer('block_reportdashboard');
            $coursedata = $reportrender->coursedata($courseid, $userid, $lsstartdate, $lsduedate);
            $coursedata['userid'] = $userid;
            $coursedata['courseid'] = $courseid;
            $coursedata['startdate'] = $startdate;
            $coursedata['duedate'] = $duedate;
            $coursedata['lsstartdate'] = $lsstartdate ? $lsstartdate : 0;
            $coursedata['lsduedate'] = $lsduedate ? $lsduedate : 0;
            return ['records'  => $coursedata];
        }
    }
    public static function coursetabs_data_returns() {

        return new external_single_structure([
            'records' => new external_single_structure([
                    'coursepercentage' => new external_value(PARAM_RAW, 'coursepercentage'),
                    'avgtestscore' => new external_value(PARAM_RAW, 'avgtestscore'),
                    'practicetest' => new external_value(PARAM_RAW, 'practicetest'),
                    'reading' => new external_value(PARAM_RAW, 'reading'),
                    'liveclass' => new external_value(PARAM_RAW, 'liveclass'),
                    'video' => new external_value(PARAM_RAW, 'video'),
                    'forumquestions' => new external_value(PARAM_RAW, 'forumquestions'),
                    'liveclasspercentage' => new external_value(PARAM_RAW, 'liveclasspercentage'),
                    'practicequestion' => new external_value(PARAM_RAW, 'practicequestion'),
                    'test' => new external_value(PARAM_RAW, 'test'),
                    'total' => new external_value(PARAM_RAW, 'total'),
                    'attended' => new external_value(PARAM_RAW, 'attended'),
                    'fullattended' => new external_value(PARAM_RAW, 'fullattended'),
                    'partiallyattended' => new external_value(PARAM_RAW, 'partiallyattended'),
                    'missedliveclass' => new external_value(PARAM_RAW, 'missedliveclass'),
                    'readingcompleted' => new external_value(PARAM_RAW, 'readingcompleted'),
                    'timespent' => new external_value(PARAM_RAW, 'timespent'),
                    'attempted' => new external_value(PARAM_RAW, 'attempted'),
                    'answered' => new external_value(PARAM_RAW, 'answered'),
                    'correct' => new external_value(PARAM_RAW, 'correct'),
                    'wrong' => new external_value(PARAM_RAW, 'wrong'),
                    'availabletests' => new external_value(PARAM_RAW, 'availabletests'),
                    'submitted' => new external_value(PARAM_RAW, 'submitted'),
                    'missed' => new external_value(PARAM_RAW, 'missed'),
                    'chapterwisereportid' => new external_value(PARAM_RAW, 'chapterwisereportid'),
                    'chapterwisereportinstance' => new external_value(PARAM_RAW, 'chapterwisereportinstance'),
                    'chapterwisereporttype' => new external_value(PARAM_RAW, 'chapterwisereporttype'),
                    'liveclassreportid' => new external_value(PARAM_RAW, 'liveclassreportid'),
                    'liveclassreportinstance' => new external_value(PARAM_RAW, 'liveclassreportinstance'),
                    'liveclassreporttype' => new external_value(PARAM_RAW, 'liveclassreporttype'),
                    'readingreportid' => new external_value(PARAM_RAW, 'readingreportid'),
                    'readingreportinstance' => new external_value(PARAM_RAW, 'readingreportinstance'),
                    'readingreporttype' => new external_value(PARAM_RAW, 'readingreporttype'),
                    'testscorereportid' => new external_value(PARAM_RAW, 'testscorereportid'),
                    'testscorereportinstance' => new external_value(PARAM_RAW, 'totalstudents'),
                    'testscorereporttype' => new external_value(PARAM_RAW, 'testscorereportinstance'),
                    'practicequestionsreportid' => new external_value(PARAM_RAW, 'practicequestionsreportid'),
                    'practicequestionsinstance' => new external_value(PARAM_RAW, 'practicequestionsinstance'),
                    'practicequestionstype' => new external_value(PARAM_RAW, 'practicequestionstype'),
                    'userid' => new external_value(PARAM_INT, 'userid'),
                    'courseid' => new external_value(PARAM_INT, 'courseid'), 
                    'startdate' => new external_value(PARAM_RAW, 'startdate'),
                    'duedate' => new external_value(PARAM_RAW, 'duedate'),
                    'lsstartdate' => new external_value(PARAM_INT, 'lsstartdate'),
                    'lsduedate' => new external_value(PARAM_INT, 'lsduedate'),

                ])
        ]);
    }
    public static function studentsdetails_view_parameters() {
        return new external_function_parameters([
            'batchid' => new external_value(PARAM_INT, 'batchid', ''),
        ]);
    }

    public static function studentsdetails_view($batchid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());

        if (!empty($batchid)) {
            $courseid = $DB->get_field_sql("SELECT courseid FROM
                        {local_batch_courses} WHERE batchid = :batchid",
                        ['batchid' => $batchid]);
        } else {
            $courseid = 0;
        }
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $studentsdetails = $reportrender->studentsdetailsview($courseid);
        return $studentsdetails;
    }

    public static function studentsdetails_view_returns() {
        return new external_single_structure([
            'totalstudents' => new external_value(PARAM_INT, 'totalstudents'),
            'activestudents' => new external_value(PARAM_RAW, 'activestudents'),
            'avgtimespent' => new external_value(PARAM_RAW, 'avgtimespent'),
            'completionrate' => new external_value(PARAM_INT, 'completionrate'),
            'avgcompletion' => new external_value(PARAM_RAW, 'avgcompletion'),
            'avgattendance' => new external_value(PARAM_FLOAT, 'avgattendance'),
            'lastliveclasscount' => new external_value(PARAM_INT, 'lastliveclasscount'),
            'testscoresprogress' => new external_value(PARAM_RAW, 'testscoresprogress'),
            'lasttestcount' => new external_value(PARAM_INT, 'lasttestcount'),
            'studentwisechapterreportid' => new external_value(PARAM_INT, 'studentwisechapterreportid'),
            'studentwisechapterinstance' => new external_value(PARAM_INT, 'studentwisechapterinstance'),
            'studentwisechapterstype' => new external_value(PARAM_RAW, 'studentwisechapterstype'),
            'chapterdetailsreportid' => new external_value(PARAM_INT, 'chapterdetailsreportid'),
            'chapterdetailsinstance' => new external_value(PARAM_INT, 'chapterdetailsinstance'),
            'chapterdetailstype' => new external_value(PARAM_RAW, 'chapterdetailstype'),
            /*'liveclassreportid' => new external_value(PARAM_INT, 'liveclassreportid'),
            'liveclassinstance' => new external_value(PARAM_INT, 'liveclassinstance'),
            'liveclasstype' => new external_value(PARAM_RAW, 'liveclasstype'),
            'liveclassattendanceid' => new external_value(PARAM_INT, 'liveclassattendanceid'),
            'liveclassattendanceinstance' => new external_value(PARAM_INT, 'liveclassattendanceinstance'),
            'liveclassattendancetype' => new external_value(PARAM_RAW, 'liveclassattendancetype'),
            'readingreportid' => new external_value(PARAM_INT, 'readingreportid'),
            'readingtype' => new external_value(PARAM_RAW, 'readingtype'),
            'practicetestreportid' => new external_value(PARAM_INT, 'practicetestreportid'),
            'practicetesttype' => new external_value(PARAM_RAW, 'practicetesttype'),
            'testscorereportid' => new external_value(PARAM_INT, 'testscorereportid'),
            'testscoretype' => new external_value(PARAM_RAW, 'testscoretype'),
            'forumreportid' => new external_value(PARAM_INT, 'forumreportid'),
            'forumtype' => new external_value(PARAM_RAW, 'forumtype'),
            'testscorelinereportid' => new external_value(PARAM_INT, 'testscorelinereportid'),
            'testscorelinetype' => new external_value(PARAM_RAW, 'testscorelinetype'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
            'totalliveclasses' => new external_value(PARAM_INT, 'totalliveclasses'),
            'zoomtimespend' => new external_value(PARAM_RAW, 'zoomtimespend'),
            'pagetime' => new external_value(PARAM_RAW, 'pagetime'),
            'avgpagecompletion' => new external_value(PARAM_RAW, 'avgpagecompletion'),
            'unreadpagecount' => new external_value(PARAM_INT, 'unreadpagecount'),
            'totalpracticetests' => new external_value(PARAM_INT, 'totalpracticetests'),
            'unattemptedtestcount' => new external_value(PARAM_INT, 'unattemptedtestcount'),
            'avgtestscore' => new external_value(PARAM_RAW, 'avgtestscore'),
            'totaltestscores' => new external_value(PARAM_INT, 'totaltestscores'),
            'unattemptedtscount' => new external_value(PARAM_INT, 'unattemptedtscount'),
            'expiredtests' => new external_value(PARAM_INT, 'expiredtests'),
            'activetests' => new external_value(PARAM_INT, 'activetests'),*/
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }
    
    public static function student_profile_parameters() {
        return new external_function_parameters([
            'packageid' => new external_value(PARAM_INT, 'packageid', ''),
            'startdate' => new external_value(PARAM_INT, 'startdate', false),
            'enddate' => new external_value(PARAM_INT, 'enddate', false),
            'userid' => new external_value(PARAM_INT, 'userid', false),

        ]);
    }
    public static function student_profile($packageid, $startdate, $enddate, $userid) {
        global $CFG, $DB, $PAGE, $USER;

        $userid = $userid ? $userid : $USER->id;
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $courses = $DB->get_records_sql("SELECT c.id, c.fullname, c.shortname
                    FROM {course} c
                    JOIN {local_packagecourses} lp ON c.id = lp.courseid
                    JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
                    WHERE c.visible = :visible AND lp.hierarchyid = :packageid AND ue.userid = :userid",
                    ['visible' => 1, 'packageid' => $packageid, 'userid' => $userid]);
        $courseoptions = array();
        if (!empty($courses)) {
            $count = 0;
            foreach ($courses as $c) {
                $active = false;
                if ($count == 0) {
                    $active = true;
                }
                $courseoptions[] = ['courseid' => $c->id, 'coursename' => format_string($c->fullname),'courseshortname' => format_string($c->shortname), 'selected' => $active];
                $count++;
            }
        }
        $packageslist = $reportrender->get_packagecoursesdata($userid, $packageid, $startdate, $enddate);
        $coursedata = $reportrender->get_coursedata($userid, $courseoptions, $startdate, $enddate);
        $coursewisedata = array();

          foreach ($coursedata as $course) {
            $coursewisedata[] = [
                'id' => $course['id'],
                'fullname'   => $course['coursename'],
                'shortname'  => $course['course_shortname'],
                'coursepercentage' => $course['coursepercentage'],
                'avgtestscore' => $course['avgtestscore'],
                'practicetest' => $course['practicetest'],
                'reading' => $course['reading'],
                'liveclass' => $course['liveclass'],
                'video' => $course['video'],
                'forumquestions' => $course['forumquestions'],
                'liveclasspercentage' => $course['liveclasspercentage'],
                'practicequestion' => $course['practicequestion'],
                'test' => $course['test'],
                'total' => $course['total'],
                'attended' => $course['attended'],
                'fullattended' => $course['fullattended'],
                'partiallyattended' => $course['partiallyattended'],
                'missedliveclass' => $course['missedliveclass'],
                'readingcompleted' => $course['readingcompleted'],
                'timespent' => $course['timespent'],
                'attempted' => $course['attempted'],
                'answered' => $course['answered'],
                'correct' => $course['correct'],
                'wrong' => $course['wrong'],
                'availabletests' => $course['availabletests'],
                'submitted' => $course['submitted'],
                'missed' => $course['missed'],
                ];
          }
            $records['records'] = [
                'packageid' => $packageid,
                'packagename' => $packageslist['packagename'],
                'coursecompletion' => $packageslist['coursecompletion'],
                'averagetestscore' => $packageslist['averagetestscore'],
                'timespend' => $packageslist['timespend'],
                'attendance' => $packageslist['attendance'],
                'courses' => $coursewisedata
                ];
            return $records;
    }
    public static function student_profile_returns() {
        return new external_single_structure([
            'records'   => new external_single_structure([
                    'coursecompletion' => new external_value(PARAM_FLOAT, 'coursecompletion',VALUE_OPTIONAL),
                    'averagetestscore' => new external_value(PARAM_FLOAT, 'averagetestscore',VALUE_OPTIONAL),
                    'timespend' => new external_value(PARAM_RAW, 'timespend',VALUE_OPTIONAL),
                    'attendance' => new external_value(PARAM_RAW, 'attendance',VALUE_OPTIONAL),
                    'courses'    => new external_multiple_structure(new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'id',VALUE_OPTIONAL),
                            'shortname' => new external_value(PARAM_RAW, 'shortname',VALUE_OPTIONAL),
                            'fullname' => new external_value(PARAM_RAW, 'fullname',VALUE_OPTIONAL),
                            'coursepercentage' => new external_value(PARAM_FLOAT, 'coursepercentage',VALUE_OPTIONAL),
                            'avgtestscore' => new external_value(PARAM_FLOAT, 'avgtestscore',VALUE_OPTIONAL),
                            'practicetest' => new external_value(PARAM_FLOAT, 'practicetest',VALUE_OPTIONAL),
                            'reading' => new external_value(PARAM_INT, 'reading',VALUE_OPTIONAL),
                            'liveclass' => new external_value(PARAM_RAW, 'liveclass',VALUE_OPTIONAL),
                            'video' => new external_value(PARAM_INT, 'video',VALUE_OPTIONAL),
                            'forumquestions' => new external_value(PARAM_INT, 'forumquestions',VALUE_OPTIONAL),
                            'liveclasspercentage' => new external_value(PARAM_FLOAT, 'liveclasspercentage',VALUE_OPTIONAL),
                            'practicequestion' => new external_value(PARAM_FLOAT, 'practicequestion',VALUE_OPTIONAL),
                            'test' => new external_value(PARAM_FLOAT, 'test',VALUE_OPTIONAL),
                            'total' => new external_value(PARAM_INT, 'total',VALUE_OPTIONAL),
                            'attended' => new external_value(PARAM_INT, 'attended',VALUE_OPTIONAL),
                            'fullattended' => new external_value(PARAM_INT, 'fullattended',VALUE_OPTIONAL),
                            'partiallyattended' => new external_value(PARAM_INT, 'partiallyattended',VALUE_OPTIONAL),
                            'missedliveclass' => new external_value(PARAM_INT, 'missedliveclass',VALUE_OPTIONAL),
                            'readingcompleted' => new external_value(PARAM_INT, 'readingcompleted',VALUE_OPTIONAL),
                            'timespent' => new external_value(PARAM_RAW, 'timespent',VALUE_OPTIONAL),
                            'attempted' => new external_value(PARAM_FLOAT, 'attempted',VALUE_OPTIONAL),
                            'answered' => new external_value(PARAM_FLOAT, 'answered',VALUE_OPTIONAL),
                            'correct' => new external_value(PARAM_FLOAT, 'correct',VALUE_OPTIONAL),
                            'wrong' => new external_value(PARAM_FLOAT, 'wrong',VALUE_OPTIONAL),
                            'availabletests' => new external_value(PARAM_INT, 'availabletests',VALUE_OPTIONAL),
                            'submitted' => new external_value(PARAM_INT, 'submitted',VALUE_OPTIONAL),
                            'missed' => new external_value(PARAM_INT, 'missed',VALUE_OPTIONAL),
                        ],'',VALUE_OPTIONAL
                    ))

                ])
        ]);
    }

    public static function package_list_parameters(){
        return new external_function_parameters([
            'userid'    => new external_value(PARAM_INT, 'userid', '', VALUE_OPTIONAL)
        ]);
    }
    public static function package_list($userid = false) {
        global $PAGE, $USER, $DB;
        $userid = !empty($userid) ? $userid : $USER->id;


        $enrolcourses = $DB->get_records_sql("SELECT c.id
        FROM {course} c
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
        WHERE ue.userid = :userid", ['userid' => $userid]);

        // Packages filter.
        $packages = array();
        if ($enrolcourses) {
            $courseslist = array_keys($enrolcourses);
            list($coursesql, $packageparams) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED);
            $packageparams['visible'] = 1;
            $packageparams['depth'] = 4;
            $packages = $DB->get_records_sql("SELECT lh.id, lh.name
                            FROM {local_hierarchy} lh
                            JOIN {local_packagecourses} lp ON lp.hierarchyid = lh.id
                            JOIN {course} c ON c.id = lp.courseid
                            WHERE c.visible = :visible AND c.id $coursesql AND lh.depth = :depth", $packageparams);
        } else {
            $packageoptions = array();
        }
        return ['records'  => $packages];
    }
    public static function package_list_returns() {
        return new external_single_structure([
            'records'  => new external_multiple_structure(new external_single_structure(
                [
                    'id'    => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                    'name'  => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
                ]
            )),    
        ]);
    }

    /**
     * Reading report
     * @return external_description
     */
    public static function reading_report_view_parameters(){
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, 'course id of course', ''),
                'userid'   => new external_value(PARAM_INT, 'userid', false),
                'startdate' => new external_value(PARAM_INT, 'startdate', false),
                'enddate' => new external_value(PARAM_INT, 'enddate', false),
                'status' => new external_value(PARAM_INT, 'status', false)
        ]);
    }
    /**
     * View reading report
     * @param int $courseid courseid
     */
    public static function reading_report_view($courseid, $userid, $startdate, $enddate, $status) {
        global $DB, $PAGE, $CFG, $USER;
        $ls = new ls();
        $type = 'readingdetails';
        $reporttype = 'table';
        $report = $DB->get_record('block_learnerscript', array('type' => $type));
        $reportid = $report->id;
        $userid = !empty($userid) ? $userid : $USER->id;
        $filters = new stdClass();
        $filters->filter_courses = $courseid;
        $filters->filter_startdate = $startdate;
        $filters->filter_duedate = $enddate;
        $filters->filter_users = $userid;
        $filters->filter_status = $status;
        $filters->reportid = $reportid;
        $filters = json_encode($filters);
        $filters =  json_decode($filters,true);
        $basicparams =  json_decode($basicparams,true);
        if (empty($basicparams)) {
            $basicparams = array();
        }

        $PAGE->set_context(contextsystem::instance());
        $learnerscript = $PAGE->get_renderer('block_learnerscript');

        if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
            print_error('reportdoesnotexists', 'block_learnerscript');
        }

        $properties = new stdClass();
        $properties->ls_startdate = !empty($filters['ls_fstartdate']) ? $filters['ls_fstartdate'] : 0;
        $properties->ls_enddate   = !empty($filters['ls_fenddate']) ? $filters['ls_fenddate'] : time();
        $reportclass = $ls->create_reportclass($reportid, $properties);
        $reportclass->params = array_merge( $filters, (array)$basicparams);
        $reportclass->cmid = $cmid;
        $reportclass->courseid = isset($courseid) ? $courseid : (isset($reportclass->params['filter_courses']) ? $reportclass->params['filter_courses'] : SITEID);
        $reportclass->mobileservice = true;
        $reportclass->status = $status;
        if ($reporttype != 'table') {
            $reportclass->start = 0;
            $reportclass->length = -1;
            $reportclass->reporttype = $reporttype;
        }
        if($reportdashboard && $report->type == 'statistics'){
            $reportdatatable = false;
        }else{
            $reportdatatable = true;
        }
        $reportdashboard = $PAGE->get_renderer('block_reportdashboard');
        $reportclass->create_report();

        if ($reportdatatable && $reporttype == 'table') {
            $datacolumns = array();
            $columnDefs = array();
            $i = 0;
            $re = array();
            if (!empty($reportclass->orderable)) {
                $re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
            }
            if(empty($reportclass->finalreport->table->data)) {
                $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                $return['reporttype'] = 'table';
                $return['emptydata'] = 1;
                $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
            } else {
                foreach ($reportclass->finalreport->table->head as $key => $value) {
                    $datacolumns[]['data'] = $value;
                    $columnDef = new stdClass();
                    $align = isset($reportclass->finalreport->table->align[$i]) ? $reportclass->finalreport->table->align[$i] : 'left';
                    $wrap = isset($reportclass->finalreport->table->wrap[$i]) && ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
                    $width = isset($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
                    $columnDef->className = 'dt-body-' . $align;
                    $columnDef->targets = $i;
                    $columnDef->wrap = $wrap;
                    $columnDef->width = $width;
                    if (!empty($re[$i]) && $re[$i])  {
                        $columnDef->orderable = false;
                    } else {
                        $columnDef->orderable = true;
                    }
                    $columnDefs[] = $columnDef;
                    $i++;
                }
                $export = explode(',', $reportclass->config->export);
                if (!empty($reportclass->finalreport->table->head)) {
                    $tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
                    $reporttable = new \block_learnerscript\output\reporttable($tablehead,
                        $reportclass->finalreport->table->id,
                        $export,
                        $reportid,
                        $reportclass->sql,
                        false,
                        false,
                        $instanceid,
                        $report->type
                    );
                    $return = array();
                    foreach ($reportclass->finalreport->table->data as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                    $tablecolumns = $learnerscript->render($reporttable);
                    $return['tdata'] = $learnerscript->render($reporttable);
                    $return['data'] =   array(
                                            "draw" => true,
                                            "recordsTotal" => $reportclass->totalrecords,
                                            "recordsFiltered" => $reportclass->totalrecords,
                                            "data" => $data
                                        );
                    $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                    $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
                    $return['columnDefs'] = $columnDefs;
                    $return['reporttype'] = 'table';
                    $return['emptydata'] = 0;
                } else {
                    $return['emptydata'] = 1;
                    $return['reporttype'] = 'table';
                    $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                }
            }
        }
        
        if(isset($return['data']) && !empty($return['data'])) {
            $reportsdata = $return['data']['data'];
            foreach ($reportsdata as $k => $v) {
                $reportcolumns[] = ['chapter' => $v[0], 'topic' => $v[1], 'timespend' => $v[2], 'status' => $v[3]];
            }
            $records['chapters'] = $reportcolumns;
        } else {
           $records['chapters'] = [];
        }
        return $records;
    }
    /**
     * View reading report
     * @return external_description
     */
    public static function reading_report_view_returns() {
          return new external_single_structure([
            'chapters'  => new external_multiple_structure(new external_single_structure(
                [
                    'chapter'    => new external_value(PARAM_RAW, 'chapter', VALUE_OPTIONAL),
                    'topic'    => new external_value(PARAM_RAW, 'topic', VALUE_OPTIONAL),
                    'timespend'  => new external_value(PARAM_RAW, 'timespend', VALUE_OPTIONAL),
                    'status'  => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL)
                ]
            )),    
        ]);
    }
    /**
     * View liveclass report
     * @param int $courseid courseid
     */
    public static function liveclass_report_view_parameters(){
        return new external_function_parameters([
            'courseid'    => new external_value(PARAM_INT, 'courseid', ''),
            'userid'   => new external_value(PARAM_INT, 'userid', false),
            'startdate' => new external_value(PARAM_INT, 'start', false),
            'enddate' => new external_value(PARAM_INT, 'enddate', false),
            'status' => new external_value(PARAM_INT, 'status', false)

        ]);
    }
    public static function liveclass_report_view($courseid, $userid, $startdate, $enddate, $status) {
        global $DB, $PAGE, $CFG, $USER;
        $ls = new ls();
        $type = 'liveclassdetails';
        $reporttype = 'table';
        $report = $DB->get_record('block_learnerscript', array('type' => $type));
        $reportid = $report->id;
        $userid = !empty($userid) ? $userid : $USER->id;
        $filters = new stdClass();
        $filters->filter_courses = $courseid;
        $filters->filter_startdate = $startdate;
        $filters->filter_duedate = $enddate;
        $filters->filter_users = $userid;
        $filters->filter_status = $status;
        $filters->reportid = $reportid;
        $filters = json_encode($filters);
        $filters =  json_decode($filters,true);
        $basicparams =  json_decode($basicparams,true);
        if (empty($basicparams)) {
            $basicparams = array();
        }

        $PAGE->set_context(contextsystem::instance());
        $learnerscript = $PAGE->get_renderer('block_learnerscript');

        if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
            print_error('reportdoesnotexists', 'block_learnerscript');
        }

        $properties = new stdClass();
        $properties->ls_startdate = !empty($filters['ls_fstartdate']) ? $filters['ls_fstartdate'] : 0;
        $properties->ls_enddate   = !empty($filters['ls_fenddate']) ? $filters['ls_fenddate'] : time();
        $reportclass = $ls->create_reportclass($reportid, $properties);
        $reportclass->params = array_merge( $filters, (array)$basicparams);
        $reportclass->cmid = $cmid;
        $reportclass->courseid = isset($courseid) ? $courseid : (isset($reportclass->params['filter_courses']) ? $reportclass->params['filter_courses'] : SITEID);
        $reportclass->status = $status;
        $reportclass->mobileservice = true;
        if ($reporttype != 'table') {
            $reportclass->start = 0;
            $reportclass->length = -1;
            $reportclass->reporttype = $reporttype;
        }
        if($reportdashboard && $report->type == 'statistics'){
            $reportdatatable = false;
        }else{
            $reportdatatable = true;
        }
        $reportdashboard = $PAGE->get_renderer('block_reportdashboard');
        $reportclass->create_report();

        if ($reportdatatable && $reporttype == 'table') {
            $datacolumns = array();
            $columnDefs = array();
            $i = 0;
            $re = array();
            if (!empty($reportclass->orderable)) {
                $re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
            }
            if(empty($reportclass->finalreport->table->data)) {
                $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                $return['reporttype'] = 'table';
                $return['emptydata'] = 1;
                $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
            } else {
                foreach ($reportclass->finalreport->table->head as $key => $value) {
                    $datacolumns[]['data'] = $value;
                    $columnDef = new stdClass();
                    $align = isset($reportclass->finalreport->table->align[$i]) ? $reportclass->finalreport->table->align[$i] : 'left';
                    $wrap = isset($reportclass->finalreport->table->wrap[$i]) && ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
                    $width = isset($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
                    $columnDef->className = 'dt-body-' . $align;
                    $columnDef->targets = $i;
                    $columnDef->wrap = $wrap;
                    $columnDef->width = $width;
                    if (!empty($re[$i]) && $re[$i])  {
                        $columnDef->orderable = false;
                    } else {
                        $columnDef->orderable = true;
                    }
                    $columnDefs[] = $columnDef;
                    $i++;
                }
                $export = explode(',', $reportclass->config->export);
                if (!empty($reportclass->finalreport->table->head)) {
                    $tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
                    $reporttable = new \block_learnerscript\output\reporttable($tablehead,
                        $reportclass->finalreport->table->id,
                        $export,
                        $reportid,
                        $reportclass->sql,
                        false,
                        false,
                        $instanceid,
                        $report->type
                    );
                    $return = array();
                    foreach ($reportclass->finalreport->table->data as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                    $tablecolumns = $learnerscript->render($reporttable);
                    $return['tdata'] = $learnerscript->render($reporttable);
                    $return['data'] =   array(
                                            "draw" => true,
                                            "recordsTotal" => $reportclass->totalrecords,
                                            "recordsFiltered" => $reportclass->totalrecords,
                                            "data" => $data
                                        );
                    $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                    $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
                    $return['columnDefs'] = $columnDefs;
                    $return['reporttype'] = 'table';
                    $return['emptydata'] = 0;
                } else {
                    $return['emptydata'] = 1;
                    $return['reporttype'] = 'table';
                    $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                }
            }
        }
        

        if(isset($return['data']) && !empty($return['data'])) {
            $reportsdata = $return['data']['data'];
            foreach ($reportsdata as $k => $v) {
                $reportcolumns[] = ['date' => $v[0], 'time' => $v[1], 'chapter' => strip_tags($v[2]), 'topic' => $v[3], 'activity' => $v[4], 'percentage' => $v[5], 'status' => $v[6]];
            }
            $records['chapters'] = $reportcolumns;
        } else {
           $records['chapters'] = [];
        }
        
        return $records;
    }
    /**
     * View liveclass report
     * @return external_description
     */
    public static function liveclass_report_view_returns() {
        return new external_single_structure([
            'chapters'  => new external_multiple_structure(new external_single_structure(
                [
                    'date'    => new external_value(PARAM_RAW, 'date', VALUE_OPTIONAL),
                    'time'    => new external_value(PARAM_RAW, 'time', VALUE_OPTIONAL),
                    'chapter'  => new external_value(PARAM_RAW, 'chapter', VALUE_OPTIONAL),
                    'topic'  => new external_value(PARAM_RAW, 'topic', VALUE_OPTIONAL),
                    'activity' => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                    'percentage'  => new external_value(PARAM_RAW, 'percentage', VALUE_OPTIONAL),
                    'status'  => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL)
                ]
            )),     
        ]);
    }

    /**
     * Testscore report
     * @return external_description
     */
    public static function testscore_report_view_parameters(){
        return new external_function_parameters([
            'courseid'    => new external_value(PARAM_INT, 'courseid', ''),
            'userid'   => new external_value(PARAM_INT, 'userid', false),
            'startdate' => new external_value(PARAM_INT, 'startdate', false),
            'enddate' => new external_value(PARAM_INT, 'enddate', false),
            'status' => new external_value(PARAM_INT, 'status', false)
        ]);
    }
    /**
     * View testscore report
     * @param int $courseid courseid
     */
    public static function testscore_report_view($courseid, $userid, $startdate, $enddate, $status) {
        global $DB, $PAGE, $CFG, $USER;

        $ls = new ls();
        $type = 'testscoredetails';
        $reporttype = 'table';
        $report = $DB->get_record('block_learnerscript', array('type' => $type));

        $reportid = $report->id;
        $userid = !empty($userid) ? $userid : $USER->id;
        $filters = new stdClass();
        $filters->filter_courses = $courseid;
        $filters->filter_users = $userid;
        $filters->filter_startdate = $startdate;
        $filters->filter_duedate = $enddate;
        $filters->filter_status = $status;
        $filters->reportid = $reportid;
        $filters = json_encode($filters);
        $filters =  json_decode($filters,true);
        $basicparams =  json_decode($basicparams,true);
        if (empty($basicparams)) {
            $basicparams = array();
        }

        $PAGE->set_context(contextsystem::instance());
        $learnerscript = $PAGE->get_renderer('block_learnerscript');

        if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
            print_error('reportdoesnotexists', 'block_learnerscript');
        }

        $properties = new stdClass();
        $properties->ls_startdate = !empty($filters['ls_fstartdate']) ? $filters['ls_fstartdate'] : 0;
        $properties->ls_enddate   = !empty($filters['ls_fenddate']) ? $filters['ls_fenddate'] : time();
        $reportclass = $ls->create_reportclass($reportid, $properties);
        $reportclass->params = array_merge( $filters, (array)$basicparams);
        $reportclass->cmid = $cmid;
        $reportclass->courseid = isset($courseid) ? $courseid : (isset($reportclass->params['filter_courses']) ? $reportclass->params['filter_courses'] : SITEID);
        $reportclass->status = $status;
        $reportclass->mobileservice = true;

        if ($reporttype != 'table') {
            $reportclass->start = 0;
            $reportclass->length = -1;
            $reportclass->reporttype = $reporttype;
        }
        if($reportdashboard && $report->type == 'statistics'){
            $reportdatatable = false;
        }else{
            $reportdatatable = true;
        }

        $reportdashboard = $PAGE->get_renderer('block_reportdashboard');
        $reportclass->create_report();

        if ($reportdatatable && $reporttype == 'table') {
            $datacolumns = array();
            $columnDefs = array();
            $i = 0;
            $re = array();
            if (!empty($reportclass->orderable)) {
                $re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
            }
            if(empty($reportclass->finalreport->table->data)) {
                $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                $return['reporttype'] = 'table';
                $return['emptydata'] = 1;
                $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
            } else {
                foreach ($reportclass->finalreport->table->head as $key => $value) {
                    $datacolumns[]['data'] = $value;
                    $columnDef = new stdClass();
                    $align = isset($reportclass->finalreport->table->align[$i]) ? $reportclass->finalreport->table->align[$i] : 'left';
                    $wrap = isset($reportclass->finalreport->table->wrap[$i]) && ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
                    $width = isset($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
                    $columnDef->className = 'dt-body-' . $align;
                    $columnDef->targets = $i;
                    $columnDef->wrap = $wrap;
                    $columnDef->width = $width;
                    if (!empty($re[$i]) && $re[$i])  {
                        $columnDef->orderable = false;
                    } else {
                        $columnDef->orderable = true;
                    }
                    $columnDefs[] = $columnDef;
                    $i++;
                }
                $export = explode(',', $reportclass->config->export);
                if (!empty($reportclass->finalreport->table->head)) {
                    $tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
                    $reporttable = new \block_learnerscript\output\reporttable($tablehead,
                        $reportclass->finalreport->table->id,
                        $export,
                        $reportid,
                        $reportclass->sql,
                        false,
                        false,
                        $instanceid,
                        $report->type
                    );
                    $return = array();
                    foreach ($reportclass->finalreport->table->data as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                    $tablecolumns = $learnerscript->render($reporttable);
                    $return['tdata'] = $learnerscript->render($reporttable);
                    $return['data'] =   array(
                                            "draw" => true,
                                            "recordsTotal" => $reportclass->totalrecords,
                                            "recordsFiltered" => $reportclass->totalrecords,
                                            "data" => $data
                                        );
                    $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                    $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
                    $return['columnDefs'] = $columnDefs;
                    $return['reporttype'] = 'table';
                    $return['emptydata'] = 0;
                } else {
                    $return['emptydata'] = 1;
                    $return['reporttype'] = 'table';
                    $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                }
            }
        }
        
        if(isset($return['data']) && !empty($return['data'])) {
            $reportsdata = $return['data']['data'];
            foreach ($reportsdata as $k => $v) {
               /* if(!empty($v[4])) {
                    $urlParts = parse_url($v[4]);
                    parse_str($urlParts['query'], $queryParams);
                    $quizid = $queryParams['id'];
                } else {
                    $quizid = 0;
                }*/
                $pattern = '/"([^"]+)"/';
                $url = '';
                if (preg_match_all($pattern, $v[8], $matches)) {
                    $activityurl = $matches[1];
                    $url = $activityurl[0];
                } else {
                    $url = '';
                }
                
                $reportcolumns[] = ['startdate' => $v[0], 'enddate' => $v[1], 'chapter' => $v[2], 'topic' => $v[3], 'activity' => $v[4], 'score' => $v[5], 'status' => $v[6], 'activityid' => $v[7], 'url' => $url ];
            }
            $records['chapters'] = $reportcolumns;
        } else {
           $records['chapters'] = [];
        }

        return $records;
    }
    /**
     * View testscore report
     * @return external_description
     */
    public static function testscore_report_view_returns() {
        return new external_single_structure([
            'chapters'  => new external_multiple_structure(new external_single_structure(
                [
                    'startdate'    => new external_value(PARAM_RAW, 'startdate', VALUE_OPTIONAL),
                    'enddate'    => new external_value(PARAM_RAW, 'enddate', VALUE_OPTIONAL),
                    'chapter'  => new external_value(PARAM_RAW, 'chapter', VALUE_OPTIONAL),
                    'topic'  => new external_value(PARAM_RAW, 'topic', VALUE_OPTIONAL),
                    'activity'  => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                    'score'  => new external_value(PARAM_RAW, 'score', VALUE_OPTIONAL),
                    'status'  => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                    'activityid' => new external_value(PARAM_INT, 'activityid', VALUE_OPTIONAL),
                    'url' => new external_value(PARAM_RAW, 'url', VALUE_OPTIONAL),
                ]
            )),     
        ]);
    }

    /**
     * Practicequestions report
     * @return external_description
     */
    public static function practicequestions_report_view_parameters(){
        return new external_function_parameters([
            'courseid'    => new external_value(PARAM_INT, 'courseid', ''),
            'userid'   => new external_value(PARAM_INT, 'userid', false),
            'startdate' => new external_value(PARAM_INT, 'start', false),
            'enddate' => new external_value(PARAM_INT, 'enddate', false)
        ]);
    }
    /**
     * View practicequestion report
     * @param int $courseid courseid
     */
    public static function practicequestions_report_view($courseid, $userid, $startdate, $enddate) {
        global $DB, $PAGE, $CFG, $USER;
        $ls = new ls();
        $type = 'practicequestionsdetails';
        $reporttype = 'table';
        $report = $DB->get_record('block_learnerscript', array('type' => $type));
        $reportid = $report->id;
        $userid = !empty($userid) ? $userid : $USER->id;
        $filters = new stdClass();
        $filters->filter_courses = $courseid;
        $filters->filter_users = $userid;
        $filters->filter_startdate = $startdate;
        $filters->filter_duedate = $enddate;
        $filters->reportid = $reportid;
        $filters = json_encode($filters);
        $filters =  json_decode($filters,true);
        $basicparams =  json_decode($basicparams,true);
        if (empty($basicparams)) {
            $basicparams = array();
        }

        $PAGE->set_context(contextsystem::instance());
        $learnerscript = $PAGE->get_renderer('block_learnerscript');

        if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
            print_error('reportdoesnotexists', 'block_learnerscript');
        }

        $properties = new stdClass();
        $properties->ls_startdate = !empty($filters['ls_fstartdate']) ? $filters['ls_fstartdate'] : 0;
        $properties->ls_enddate   = !empty($filters['ls_fenddate']) ? $filters['ls_fenddate'] : time();
        $reportclass = $ls->create_reportclass($reportid, $properties);
        $reportclass->params = array_merge( $filters, (array)$basicparams);
        $reportclass->cmid = $cmid;
        $reportclass->courseid = isset($courseid) ? $courseid : (isset($reportclass->params['filter_courses']) ? $reportclass->params['filter_courses'] : SITEID);
        $reportclass->status = $status;
        $reportclass->mobileservice = true;
        if ($reporttype != 'table') {
            $reportclass->start = 0;
            $reportclass->length = -1;
            $reportclass->reporttype = $reporttype;
        }
        if($reportdashboard && $report->type == 'statistics'){
            $reportdatatable = false;
        }else{
            $reportdatatable = true;
        }
        $reportdashboard = $PAGE->get_renderer('block_reportdashboard');
        $reportclass->create_report();

        if ($reportdatatable && $reporttype == 'table') {
            $datacolumns = array();
            $columnDefs = array();
            $i = 0;
            $re = array();
            if (!empty($reportclass->orderable)) {
                $re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
            }
            if(empty($reportclass->finalreport->table->data)) {
                $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                $return['reporttype'] = 'table';
                $return['emptydata'] = 1;
                $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
            } else {
                foreach ($reportclass->finalreport->table->head as $key => $value) {
                    $datacolumns[]['data'] = $value;
                    $columnDef = new stdClass();
                    $align = isset($reportclass->finalreport->table->align[$i]) ? $reportclass->finalreport->table->align[$i] : 'left';
                    $wrap = isset($reportclass->finalreport->table->wrap[$i]) && ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
                    $width = isset($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
                    $columnDef->className = 'dt-body-' . $align;
                    $columnDef->targets = $i;
                    $columnDef->wrap = $wrap;
                    $columnDef->width = $width;
                    if (!empty($re[$i]) && $re[$i])  {
                        $columnDef->orderable = false;
                    } else {
                        $columnDef->orderable = true;
                    }
                    $columnDefs[] = $columnDef;
                    $i++;
                }
                $export = explode(',', $reportclass->config->export);
                if (!empty($reportclass->finalreport->table->head)) {
                    $tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
                    $reporttable = new \block_learnerscript\output\reporttable($tablehead,
                        $reportclass->finalreport->table->id,
                        $export,
                        $reportid,
                        $reportclass->sql,
                        false,
                        false,
                        $instanceid,
                        $report->type
                    );
                    $return = array();
                    foreach ($reportclass->finalreport->table->data as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                    $tablecolumns = $learnerscript->render($reporttable);
                    $return['tdata'] = $learnerscript->render($reporttable);
                    $return['data'] =   array(
                                            "draw" => true,
                                            "recordsTotal" => $reportclass->totalrecords,
                                            "recordsFiltered" => $reportclass->totalrecords,
                                            "data" => $data
                                        );
                    $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                    $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
                    $return['columnDefs'] = $columnDefs;
                    $return['reporttype'] = 'table';
                    $return['emptydata'] = 0;
                } else {
                    $return['emptydata'] = 1;
                    $return['reporttype'] = 'table';
                    $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                }
            }
        }
        if(isset($return['data']) && !empty($return['data'])) {
            $reportsdata = $return['data']['data'];

            foreach ($reportsdata as $k => $v) {
                $reportcolumns[] = ['activity' => $v[0], 'chapter' => $v[1], 'topic' => $v[2], 'attempted' => $v[3], 'answered' => $v[4], 'correct' => $v[5], 'wrong' => $v[6], 'totalquestions' => $v[7]];
            }
            $records['chapters'] =  $reportcolumns;
        } else {
           $records['chapters'] = [];
        }
       
        return $records;
    }
    /**
     * View practicequestions report
     * @return external_description
     */
    public static function practicequestions_report_view_returns() {
        return new external_single_structure([
            'chapters'  => new external_multiple_structure(new external_single_structure(
                [
                    'activity'    => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                    'chapter'  => new external_value(PARAM_RAW, 'chapter', VALUE_OPTIONAL),
                    'topic'  => new external_value(PARAM_RAW, 'topic', VALUE_OPTIONAL),
                    'attempted'  => new external_value(PARAM_INT, 'attempted', VALUE_OPTIONAL),
                    'answered'  => new external_value(PARAM_INT, 'answered', VALUE_OPTIONAL),
                    'correct'  => new external_value(PARAM_INT, 'correct', VALUE_OPTIONAL),
                    'wrong'  => new external_value(PARAM_INT, 'wrong', VALUE_OPTIONAL),
                    'totalquestions'  => new external_value(PARAM_INT, 'totalquestions', VALUE_OPTIONAL)
                ]
            )),     
        ]);
    }
    /**
     * Chapterwise report
     * @return external_description
     */
    public static function chapterwisereport_report_view_parameters(){
        return new external_function_parameters([
            'courseid'   => new external_value(PARAM_INT, 'courseid', ''),
            'userid'   => new external_value(PARAM_INT, 'userid', false),
            'startdate' => new external_value(PARAM_INT, 'start', false),
            'enddate' => new external_value(PARAM_INT, 'enddate', false)
        ]);
    }
    /**
     * View chapterwise report
     * @param int $courseid courseid
     */
    public static function chapterwisereport_report_view($courseid, $userid, $startdate, $enddate) {
        global $DB, $PAGE, $CFG, $USER;
        $ls = new ls();
        $type = 'chapterreport';
        $reporttype = 'table';
        $report = $DB->get_record('block_learnerscript', array('type' => $type));
        $reportid = $report->id;
        $userid = !empty($userid) ? $userid : $USER->id;
        $filters = new stdClass();
        $filters->filter_courses = $courseid;
        $filters->filter_users = $userid;
        $filters->filter_startdate = $startdate;
        $filters->filter_duedate = $enddate;
        $filters->reportid = $reportid;
        $filters = json_encode($filters);
        $filters = json_decode($filters,true);
        $basicparams = json_decode($basicparams,true);
        if (empty($basicparams)) {
        $basicparams = array();
        }
        $PAGE->set_context(contextsystem::instance());
        $learnerscript = $PAGE->get_renderer('block_learnerscript');
    
        if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
            print_error('reportdoesnotexists', 'block_learnerscript');
        }
    
        $properties = new stdClass();
        $properties->ls_startdate = !empty($filters['ls_fstartdate']) ? $filters['ls_fstartdate'] : 0;
        $properties->ls_enddate   = !empty($filters['ls_fenddate']) ? $filters['ls_fenddate'] : time();
        $reportclass = $ls->create_reportclass($reportid, $properties);
        $reportclass->params = array_merge( $filters, (array)$basicparams);
        $reportclass->cmid = $cmid;
        $reportclass->courseid = isset($courseid) ? $courseid : (isset($reportclass->params['filter_courses']) ? $reportclass->params['filter_courses'] : SITEID);
        $reportclass->status = $status;
        $reportclass->mobileservice = true;
        if ($reporttype != 'table') {
            $reportclass->start = 0;
            $reportclass->length = -1;
            $reportclass->reporttype = $reporttype;
        }
        if($reportdashboard && $report->type == 'statistics'){
            $reportdatatable = false;
        }else{
            $reportdatatable = true;
        }
        $reportdashboard = $PAGE->get_renderer('block_reportdashboard');
        $reportclass->create_report();
    
        if ($reportdatatable && $reporttype == 'table') {
            $datacolumns = array();
            $columnDefs = array();
            $i = 0;
            $re = array();
            if (!empty($reportclass->orderable)) {
                $re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
            }
            if(empty($reportclass->finalreport->table->data)) {
                $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                $return['reporttype'] = 'table';
                $return['emptydata'] = 1;
                $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
            } else {
                foreach ($reportclass->finalreport->table->head as $key => $value) {
                    $datacolumns[]['data'] = $value;
                    $columnDef = new stdClass();
                    $align = isset($reportclass->finalreport->table->align[$i]) ? $reportclass->finalreport->table->align[$i] : 'left';
                    $wrap = isset($reportclass->finalreport->table->wrap[$i]) && ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
                    $width = isset($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
                    $columnDef->className = 'dt-body-' . $align;
                    $columnDef->targets = $i;
                    $columnDef->wrap = $wrap;
                    $columnDef->width = $width;
                    if (!empty($re[$i]) && $re[$i])  {
                        $columnDef->orderable = false;
                    } else {
                        $columnDef->orderable = true;
                    }
                    $columnDefs[] = $columnDef;
                    $i++;
                }
                $export = explode(',', $reportclass->config->export);
                if (!empty($reportclass->finalreport->table->head)) {
                    $tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
                    $reporttable = new \block_learnerscript\output\reporttable($tablehead,
                        $reportclass->finalreport->table->id,
                        $export,
                        $reportid,
                        $reportclass->sql,
                        false,
                        false,
                        $instanceid,
                        $report->type
                    );
                    $return = array();
                    foreach ($reportclass->finalreport->table->data as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                    $tablecolumns = $learnerscript->render($reporttable);
                    $return['tdata'] = $learnerscript->render($reporttable);
                    $return['data'] =   array(
                                            "draw" => true,
                                            "recordsTotal" => $reportclass->totalrecords,
                                            "recordsFiltered" => $reportclass->totalrecords,
                                            "data" => $data
                                        );
                    $reporttitle = get_string('report_' . $report->type, 'block_learnerscript');
                    $return['reportname'] = (new ls)->get_reporttitle($reporttitle, $basicparams);
                    $return['columnDefs'] = $columnDefs;
                    $return['reporttype'] = 'table';
                    $return['emptydata'] = 0;
                } else {
                    $return['emptydata'] = 1;
                    $return['reporttype'] = 'table';
                    $return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
                }
            }
        }
        $reportsdata = $return['data']['data'];

        if(isset($return['data']) && !empty($return['data'])) {
            $reportsdata = $return['data']['data'];
            foreach ($reportsdata as $k => $v) {
                $reportcolumns[] = ['chapter' => $v[0], 'progress' => $v[1], 'liveclass' => $v[2], 'practicequestion' => $v[3], 'reading' => $v[4], 'video' => $v[5], 'testscore' => $v[6]];
            }
            $records['chapters'] = $reportcolumns;
        } else {
            $records['chapters'] = [];
        }
        return $records;
    }
    /**
     * View chapterwise report
     * @return external_description
     */
    public static function chapterwisereport_report_view_returns() {
        return new external_single_structure([
            'chapters'  => new external_multiple_structure(new external_single_structure(
                [
                    'chapter'    => new external_value(PARAM_RAW, 'chapter', VALUE_OPTIONAL),
                    'progress'  => new external_value(PARAM_INT, 'progress', VALUE_OPTIONAL),
                    'liveclass'  => new external_value(PARAM_RAW, 'liveclass', VALUE_OPTIONAL),
                    'practicequestion'  => new external_value(PARAM_RAW, 'practicequestion', VALUE_OPTIONAL),
                    'reading'  => new external_value(PARAM_RAW, 'reading', VALUE_OPTIONAL),
                    'video'  => new external_value(PARAM_RAW, 'video', VALUE_OPTIONAL),
                    'testscore'  => new external_value(PARAM_RAW, 'testscore', VALUE_OPTIONAL),
                ]
            )),    
        ]);
    }

    public static function coursedatedateselect_view_parameters(){
        return new external_function_parameters([
            'packageid'    => new external_value(PARAM_INT, 'packageid', '', VALUE_OPTIONAL),
            'userid'    => new external_value(PARAM_INT, 'userid', '', VALUE_OPTIONAL),
            'startdate'    => new external_value(PARAM_RAW, 'startdate', '', VALUE_OPTIONAL),
            'duedate'    => new external_value(PARAM_RAW, 'duedate', '', VALUE_OPTIONAL),
            'lsstartdate'    => new external_value(PARAM_INT, 'lsstartdate', '', VALUE_OPTIONAL),
            'lsduedate'    => new external_value(PARAM_INT, 'lsduedate', '', VALUE_OPTIONAL),

        ]);
    }
    public static function coursedatedateselect_view($packageid, $userid, $startdate, $duedate, $lsstartdate, $lsduedate) {
        global $PAGE, $USER;
        $context = contextsystem::instance();
        $PAGE->set_context($context);
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $packages = $reportrender->get_packagecoursesdata($userid, $packageid, $lsstartdate, $lsduedate);
        $courses = $reportrender->get_courses($packageid, $userid);
        $coursedata = $reportrender->get_coursedata($userid, $courses, $lsstartdate, $lsduedate);
        $reportsdata = $reportrender->get_reportsdata();
        $packageoptions = $reportrender->get_packages($packageid, $userid); //echo '<pre>';print_r($coursedata);exit;
         $records['records'] = [
                'lsstartdate' => $lsstartdate ? $lsstartdate : 0,
                'lsduedate' => $lsduedate ? $lsduedate : 0,
                'startdate' => $startdate,
                'duedate' => $duedate,
                'userid' => $userid,
                'courseid' => $coursedata[0]['id'],
                'packages' => $packageoptions,
                'courses' => $courses,
                'coursedata' => $coursedata[0],
                'packagecoursesdata' => $packages,
                'chapterwisereportid' => $reportsdata['chapterwisereportid'],
                'chapterwisereportinstance' => $reportsdata['chapterwisereportinstance'],
                'chapterwisereporttype' => $reportsdata['chapterwisereporttype'],
                'liveclassreportid' => $reportsdata['liveclassreportid'],
                'liveclassreportinstance' => $reportsdata['liveclassreportinstance'],
                'liveclassreporttype' => $reportsdata['liveclassreporttype'],
                'readingreportid' => $reportsdata['readingreportid'],
                'readingreportinstance' => $reportsdata['readingreportinstance'],
                'readingreporttype' => $reportsdata['readingreporttype'],
                'testscorereportid' => $reportsdata['testscorereportid'],
                'testscorereportinstance' => $reportsdata['testscorereportinstance'],
                'testscorereporttype' => $reportsdata['testscorereporttype'],
                'practicequestionsreportid' => $reportsdata['practicequestionsreportid'],
                'practicequestionsinstance' => $reportsdata['practicequestionsinstance'],
                'practicequestionstype' => $reportsdata['practicequestionstype'],
                'coursewisedata' => $coursedata,
                ];
        return $records;

    }
    public static function coursedatedateselect_view_returns() {
         return new external_single_structure([
            'records'   => new external_single_structure([
                    'lsstartdate' => new external_value(PARAM_INT, 'lsstartdate',VALUE_OPTIONAL),
                    'lsduedate' => new external_value(PARAM_INT, 'lsduedate',VALUE_OPTIONAL),
                    'startdate' => new external_value(PARAM_RAW, 'startdate',VALUE_OPTIONAL),
                    'duedate' => new external_value(PARAM_RAW, 'duedate',VALUE_OPTIONAL),
                    'userid' => new external_value(PARAM_INT, 'userid',VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_INT, 'userid',VALUE_OPTIONAL),
                    'packages'    => new external_multiple_structure(new external_single_structure(
                        [
                            'packageid' => new external_value(PARAM_INT, 'packageid',VALUE_OPTIONAL),
                            'packagename' => new external_value(PARAM_RAW, 'packagename',VALUE_OPTIONAL),
                            'selected' => new external_value(PARAM_RAW, 'selected',VALUE_OPTIONAL),
                        ]
                    )),
                    'coursecompletion' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'averagetestscore' => new external_value(PARAM_RAW, 'averagetestscore',VALUE_OPTIONAL),
                    'timespend' => new external_value(PARAM_RAW, 'timespend',VALUE_OPTIONAL),
                    'attendance' => new external_value(PARAM_RAW, 'attendance',VALUE_OPTIONAL),
                    'coursecompletioncolor' => new external_value(PARAM_RAW, 'coursecompletioncolor',VALUE_OPTIONAL),
                    'averagetestscorecolor' => new external_value(PARAM_RAW, 'averagetestscorecolor',VALUE_OPTIONAL),
                    'courses'    => new external_multiple_structure(new external_single_structure(
                        [
                            'courseid' => new external_value(PARAM_INT, 'courseid',VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_RAW, 'coursename',VALUE_OPTIONAL),
                            'selected' => new external_value(PARAM_RAW, 'selected',VALUE_OPTIONAL),
                        ]
                    )),
                    'packagecoursesdata'    => new external_single_structure(
                        [
                            'coursecompletion' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                            'averagetestscore' => new external_value(PARAM_RAW, 'averagetestscore',VALUE_OPTIONAL),
                            'timespend' => new external_value(PARAM_RAW, 'timespend',VALUE_OPTIONAL),
                            'attendance' => new external_value(PARAM_RAW, 'attendance',VALUE_OPTIONAL),
                        ]
                    ),
                    'coursedata' => new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'id',VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_RAW, 'id',VALUE_OPTIONAL),
                            'shortname' => new external_value(PARAM_RAW, 'shortname',VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'id',VALUE_OPTIONAL),
                            'selected' => new external_value(PARAM_RAW, 'shortname',VALUE_OPTIONAL),
                            'coursepercentage' => new external_value(PARAM_RAW, 'coursepercentage',VALUE_OPTIONAL),
                            'avgtestscore' => new external_value(PARAM_RAW, 'avgtestscore',VALUE_OPTIONAL),
                            'practicetest' => new external_value(PARAM_RAW, 'practicetest',VALUE_OPTIONAL),
                            'reading' => new external_value(PARAM_RAW, 'reading',VALUE_OPTIONAL),
                            'liveclass' => new external_value(PARAM_RAW, 'liveclass',VALUE_OPTIONAL),
                            'video' => new external_value(PARAM_RAW, 'video',VALUE_OPTIONAL),
                            'forumquestions' => new external_value(PARAM_RAW, 'forumquestions',VALUE_OPTIONAL),
                            'liveclasspercentage' => new external_value(PARAM_RAW, 'liveclasspercentage',VALUE_OPTIONAL),
                            'practicequestion' => new external_value(PARAM_RAW, 'practicequestion',VALUE_OPTIONAL),
                            'test' => new external_value(PARAM_RAW, 'test',VALUE_OPTIONAL),
                            'total' => new external_value(PARAM_RAW, 'total',VALUE_OPTIONAL),
                            'attended' => new external_value(PARAM_RAW, 'attended',VALUE_OPTIONAL),
                            'fullattended' => new external_value(PARAM_RAW, 'fullattended',VALUE_OPTIONAL),
                            'partiallyattended' => new external_value(PARAM_RAW, 'partiallyattended',VALUE_OPTIONAL),
                            'missedliveclass' => new external_value(PARAM_RAW, 'missedliveclass',VALUE_OPTIONAL),
                            'readingcompleted' => new external_value(PARAM_RAW, 'readingcompleted',VALUE_OPTIONAL),
                            'timespent' => new external_value(PARAM_RAW, 'timespent',VALUE_OPTIONAL),
                            'attempted' => new external_value(PARAM_RAW, 'attempted',VALUE_OPTIONAL),
                            'answered' => new external_value(PARAM_RAW, 'answered',VALUE_OPTIONAL),
                            'correct' => new external_value(PARAM_RAW, 'correct',VALUE_OPTIONAL),
                            'wrong' => new external_value(PARAM_RAW, 'wrong',VALUE_OPTIONAL),
                            'availabletests' => new external_value(PARAM_RAW, 'availabletests',VALUE_OPTIONAL),
                            'submitted' => new external_value(PARAM_RAW, 'submitted',VALUE_OPTIONAL),
                            'missed' => new external_value(PARAM_RAW, 'missed',VALUE_OPTIONAL),
                            'chapterwisereportid' => new external_value(PARAM_RAW, 'chapterwisereportid',VALUE_OPTIONAL),
                            'chapterwisereportinstance' => new external_value(PARAM_RAW, 'chapterwisereportinstance',VALUE_OPTIONAL),
                            'chapterwisereporttype' => new external_value(PARAM_RAW, 'chapterwisereporttype',VALUE_OPTIONAL),
                            'liveclassreportid' => new external_value(PARAM_RAW, 'liveclassreportid',VALUE_OPTIONAL),
                            'liveclassreportinstance' => new external_value(PARAM_RAW, 'liveclassreportinstance',VALUE_OPTIONAL),
                            'liveclassreporttype' => new external_value(PARAM_RAW, 'liveclassreporttype',VALUE_OPTIONAL),
                            'readingreportid' => new external_value(PARAM_RAW, 'readingreportid',VALUE_OPTIONAL),
                            'readingreportinstance' => new external_value(PARAM_RAW, 'readingreportinstance',VALUE_OPTIONAL),
                            'readingreporttype' => new external_value(PARAM_RAW, 'readingreporttype',VALUE_OPTIONAL),
                            'testscorereportid' => new external_value(PARAM_RAW, 'testscorereportid',VALUE_OPTIONAL),
                            'testscorereportinstance' => new external_value(PARAM_RAW, 'testscorereportinstance',VALUE_OPTIONAL),
                            'testscorereporttype' => new external_value(PARAM_RAW, 'testscorereporttype',VALUE_OPTIONAL),
                            'practicequestionsreportid' => new external_value(PARAM_RAW, 'practicequestionsreportid',VALUE_OPTIONAL),
                            'practicequestionsinstance' => new external_value(PARAM_RAW, 'practicequestionsinstance',VALUE_OPTIONAL),
                            'practicequestionstype' => new external_value(PARAM_RAW, 'practicequestionstype',VALUE_OPTIONAL),
                            'coursecompletioncolor' => new external_value(PARAM_RAW, 'coursecompletioncolor',VALUE_OPTIONAL),
                            'averagetestscorecolor' => new external_value(PARAM_RAW, 'averagetestscorecolor',VALUE_OPTIONAL),
                            'practicetestcolor' => new external_value(PARAM_RAW, 'practicetestcolor',VALUE_OPTIONAL),
                            'readingcolor' => new external_value(PARAM_RAW, 'readingcolor',VALUE_OPTIONAL),
                            'liveclasscolor' => new external_value(PARAM_RAW, 'liveclasscolor',VALUE_OPTIONAL),
                            'videocolor' => new external_value(PARAM_RAW, 'videocolor',VALUE_OPTIONAL),

                        ]
                    ),
                    'chapterwisereportid' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'chapterwisereportinstance' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'chapterwisereporttype' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'liveclassreportid' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'liveclassreportinstance' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'liveclassreporttype' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'readingreportid' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'readingreportinstance' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'readingreporttype' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'testscorereportid' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'testscorereportinstance' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'testscorereporttype' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'practicequestionsreportid' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'practicequestionsinstance' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'practicequestionstype' => new external_value(PARAM_RAW, 'coursecompletion',VALUE_OPTIONAL),
                    'coursewisedata'    => new external_multiple_structure(new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'id',VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_RAW, 'id',VALUE_OPTIONAL),
                            'shortname' => new external_value(PARAM_RAW, 'shortname',VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'id',VALUE_OPTIONAL),
                            'selected' => new external_value(PARAM_RAW, 'shortname',VALUE_OPTIONAL),
                            'coursepercentage' => new external_value(PARAM_RAW, 'coursepercentage',VALUE_OPTIONAL),
                            'avgtestscore' => new external_value(PARAM_RAW, 'avgtestscore',VALUE_OPTIONAL),
                            'practicetest' => new external_value(PARAM_RAW, 'practicetest',VALUE_OPTIONAL),
                            'reading' => new external_value(PARAM_RAW, 'reading',VALUE_OPTIONAL),
                            'liveclass' => new external_value(PARAM_RAW, 'liveclass',VALUE_OPTIONAL),
                            'video' => new external_value(PARAM_RAW, 'video',VALUE_OPTIONAL),
                            'forumquestions' => new external_value(PARAM_RAW, 'forumquestions',VALUE_OPTIONAL),
                            'liveclasspercentage' => new external_value(PARAM_RAW, 'liveclasspercentage',VALUE_OPTIONAL),
                            'practicequestion' => new external_value(PARAM_RAW, 'practicequestion',VALUE_OPTIONAL),
                            'test' => new external_value(PARAM_RAW, 'test',VALUE_OPTIONAL),
                            'total' => new external_value(PARAM_RAW, 'total',VALUE_OPTIONAL),
                            'attended' => new external_value(PARAM_RAW, 'attended',VALUE_OPTIONAL),
                            'fullattended' => new external_value(PARAM_RAW, 'fullattended',VALUE_OPTIONAL),
                            'partiallyattended' => new external_value(PARAM_RAW, 'partiallyattended',VALUE_OPTIONAL),
                            'missedliveclass' => new external_value(PARAM_RAW, 'missedliveclass',VALUE_OPTIONAL),
                            'readingcompleted' => new external_value(PARAM_RAW, 'readingcompleted',VALUE_OPTIONAL),
                            'timespent' => new external_value(PARAM_RAW, 'timespent',VALUE_OPTIONAL),
                            'attempted' => new external_value(PARAM_RAW, 'attempted',VALUE_OPTIONAL),
                            'answered' => new external_value(PARAM_RAW, 'answered',VALUE_OPTIONAL),
                            'correct' => new external_value(PARAM_RAW, 'correct',VALUE_OPTIONAL),
                            'wrong' => new external_value(PARAM_RAW, 'wrong',VALUE_OPTIONAL),
                            'availabletests' => new external_value(PARAM_RAW, 'availabletests',VALUE_OPTIONAL),
                            'submitted' => new external_value(PARAM_RAW, 'submitted',VALUE_OPTIONAL),
                            'missed' => new external_value(PARAM_RAW, 'missed',VALUE_OPTIONAL),
                            'chapterwisereportid' => new external_value(PARAM_RAW, 'chapterwisereportid',VALUE_OPTIONAL),
                            'chapterwisereportinstance' => new external_value(PARAM_RAW, 'chapterwisereportinstance',VALUE_OPTIONAL),
                            'chapterwisereporttype' => new external_value(PARAM_RAW, 'chapterwisereporttype',VALUE_OPTIONAL),
                            'liveclassreportid' => new external_value(PARAM_RAW, 'liveclassreportid',VALUE_OPTIONAL),
                            'liveclassreportinstance' => new external_value(PARAM_RAW, 'liveclassreportinstance',VALUE_OPTIONAL),
                            'liveclassreporttype' => new external_value(PARAM_RAW, 'liveclassreporttype',VALUE_OPTIONAL),
                            'readingreportid' => new external_value(PARAM_RAW, 'readingreportid',VALUE_OPTIONAL),
                            'readingreportinstance' => new external_value(PARAM_RAW, 'readingreportinstance',VALUE_OPTIONAL),
                            'readingreporttype' => new external_value(PARAM_RAW, 'readingreporttype',VALUE_OPTIONAL),
                            'testscorereportid' => new external_value(PARAM_RAW, 'testscorereportid',VALUE_OPTIONAL),
                            'testscorereportinstance' => new external_value(PARAM_RAW, 'testscorereportinstance',VALUE_OPTIONAL),
                            'testscorereporttype' => new external_value(PARAM_RAW, 'testscorereporttype',VALUE_OPTIONAL),
                            'practicequestionsreportid' => new external_value(PARAM_RAW, 'practicequestionsreportid',VALUE_OPTIONAL),
                            'practicequestionsinstance' => new external_value(PARAM_RAW, 'practicequestionsinstance',VALUE_OPTIONAL),
                            'practicequestionstype' => new external_value(PARAM_RAW, 'practicequestionstype',VALUE_OPTIONAL),
                            'coursecompletioncolor' => new external_value(PARAM_RAW, 'coursecompletioncolor',VALUE_OPTIONAL),
                            'averagetestscorecolor' => new external_value(PARAM_RAW, 'averagetestscorecolor',VALUE_OPTIONAL),
                            'practicetestcolor' => new external_value(PARAM_RAW, 'practicetestcolor',VALUE_OPTIONAL),
                            'readingcolor' => new external_value(PARAM_RAW, 'readingcolor',VALUE_OPTIONAL),
                            'liveclasscolor' => new external_value(PARAM_RAW, 'liveclasscolor',VALUE_OPTIONAL),
                            'videocolor' => new external_value(PARAM_RAW, 'videocolor',VALUE_OPTIONAL),
                        ]
                    )),
                ]),
        ]);
    }

    public static function liveclassdetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function liveclassdetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $liveclassdetails = $reportrender->liveclassdetailsview($courseid);
        return $liveclassdetails;
    }

    public static function liveclassdetails_view_returns() {
        return new external_single_structure([
            'liveclassreportid' => new external_value(PARAM_INT, 'liveclassreportid'),
            'liveclassinstance' => new external_value(PARAM_INT, 'liveclassinstance'),
            'liveclasstype' => new external_value(PARAM_RAW, 'liveclasstype'),
            'liveclassattendanceid' => new external_value(PARAM_INT, 'liveclassattendanceid'),
            'liveclassattendanceinstance' => new external_value(PARAM_INT, 'liveclassattendanceinstance'),
            'liveclassattendancetype' => new external_value(PARAM_RAW, 'liveclassattendancetype'),
            'totalliveclasses' => new external_value(PARAM_INT, 'totalliveclasses'),
            'zoomtimespend' => new external_value(PARAM_RAW, 'zoomtimespend'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function readingdetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function readingdetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $readingdetails = $reportrender->readingdetailsview($courseid);
        return $readingdetails;
    }

    public static function readingdetails_view_returns() {
        return new external_single_structure([
            'readingreportid' => new external_value(PARAM_INT, 'readingreportid'),
            'readingtype' => new external_value(PARAM_RAW, 'readingtype'),
            'pagetime' => new external_value(PARAM_RAW, 'pagetime'),
            'avgpagecompletion' => new external_value(PARAM_RAW, 'avgpagecompletion'),
            'unreadpagecount' => new external_value(PARAM_INT, 'unreadpagecount'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function practicetestdetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function practicetestdetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $practicetestdetails = $reportrender->practicetestdetailsview($courseid);
        return $practicetestdetails;
    }

    public static function practicetestdetails_view_returns() {
        return new external_single_structure([
            'practicetestreportid' => new external_value(PARAM_INT, 'practicetestreportid'),
            'practicetesttype' => new external_value(PARAM_RAW, 'practicetesttype'),
            'totalpracticetests' => new external_value(PARAM_INT, 'totalpracticetests'),
            'unattemptedtestcount' => new external_value(PARAM_INT, 'unattemptedtestcount'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function testscoredetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function testscoredetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $testscoredetails = $reportrender->testscoredetailsview($courseid);
        return $testscoredetails;
    }

    public static function testscoredetails_view_returns() {
        return new external_single_structure([
            'testscorereportid' => new external_value(PARAM_INT, 'testscorereportid'),
            'testscoretype' => new external_value(PARAM_RAW, 'testscoretype'),
            'testscorelinereportid' => new external_value(PARAM_INT, 'testscorelinereportid'),
            'testscorelinetype' => new external_value(PARAM_RAW, 'testscorelinetype'),
            'avgtestscore' => new external_value(PARAM_RAW, 'avgtestscore'),
            'totaltestscores' => new external_value(PARAM_INT, 'totaltestscores'),
            'unattemptedtscount' => new external_value(PARAM_INT, 'unattemptedtscount'),
            'expiredtests' => new external_value(PARAM_INT, 'expiredtests'),
            'activetests' => new external_value(PARAM_INT, 'activetests'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function forumdetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function forumdetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $forumdetails = $reportrender->forumdetailsview($courseid);
        return $forumdetails;
    }

    public static function forumdetails_view_returns() {
        return new external_single_structure([
            'forumreportid' => new external_value(PARAM_INT, 'forumreportid'),
            'forumtype' => new external_value(PARAM_RAW, 'forumtype'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function studentchapterdetails_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid', ''),
        ]);
    }

    public static function studentchapterdetails_view($courseid = false) {
        global $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $reportrender = $PAGE->get_renderer('block_reportdashboard');
        $studentchapterdetails = $reportrender->studentchapterdetailsview($courseid);
        return $studentchapterdetails;
    }

    public static function studentchapterdetails_view_returns() {
        return new external_single_structure([
            'chapterdetailsreportid' => new external_value(PARAM_INT, 'chapterdetailsreportid'),
            'chapterdetailsinstance' => new external_value(PARAM_INT, 'chapterdetailsinstance'),
            'chapterdetailstype' => new external_value(PARAM_RAW, 'chapterdetailstype'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

}
