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
 * local_reportdashboards
 * @package local_reportdashboards
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE, $OUTPUT, $DB, $USER;
require_login();
$userid = optional_param('filter_users', '', PARAM_INT);
$packageid = optional_param('filter_packages', '', PARAM_INT);
use block_learnerscript\local\ls;
use block_learnerscript\local\querylib;
$PAGE->requires->js(new moodle_url('https://learnerscript.com/wp-content/plugins/learnerscript/js/highcharts.js'));
$PAGE->requires->css('/blocks/reportdashboard/css/radios-to-slider.min.css');
$PAGE->requires->css('/blocks/reportdashboard/css/flatpickr.min.css');
$PAGE->requires->css('/blocks/learnerscript/css/fixedHeader.dataTables.min.css');
$PAGE->requires->css('/blocks/learnerscript/css/responsive.dataTables.min.css');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/blocks/learnerscript/css/jquery.dataTables.min.css');
$PAGE->requires->css('/blocks/learnerscript/css/select2.min.css');
$PAGE->requires->js_call_amd('block_reportdashboard/reportdashboard', 'init');

$systemcontext = context_system::instance();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/reportdashboard/dashboard.php'));
$PAGE->set_title(get_string('studentprofile', 'block_reportdashboard'));
$PAGE->navbar->add(get_string('studentprofile', 'block_reportdashboard'),
            new moodle_url('/blocks/reportdashboard/reports.php'));
$learnerscript = get_config('block_learnerscript', 'ls_serialkey');
$PAGE->requires->data_for_js("M.cfg.accessls", $learnerscript , true);

$userid = (isset($userid) && !empty($userid)) ? $userid : $USER->id;
echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => 'licenceresult', 'class' => 'lsaccess'));

// User enrolcourses
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
		$packages = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name
	                    FROM {local_hierarchy} lh
	                    JOIN {local_packagecourses} lp ON lp.hierarchyid = lh.id
	                    JOIN {course} c ON c.id = lp.courseid
	                    WHERE c.visible = :visible AND c.id $coursesql AND lh.depth = :depth", $packageparams);
	} else {
		$packageoptions = array();
	}
$packageoptions = array();
if (!empty($packages)) {
    foreach ($packages as $p) {
    		$active = '';
    	if ($packageid == $p->id) {
    		$active = 'selected';
    	}
        $packageoptions[] = ['packageid' => $p->id, 'packagename' => format_string($p->name), 'selected' => $active];
    }
}
if(isset($packageid) && ($packageid)) {
	$packageid = $packageid;
} else {
	$packageid = key($packages);
}
$packagename = $DB->get_field('local_hierarchy', 'name', array('id' => $packageid,'depth' => 4));


// Courses.
if($enrolcourses) {
	$courseseslist = array_keys($enrolcourses);
	list($coursessql, $packagesparams) = $DB->get_in_or_equal($courseseslist, SQL_PARAMS_NAMED);
	$packagesparams['packageid'] = $packageid;
	$packagesparams['visible'] = 1;
	$courses = $DB->get_records_sql("SELECT c.id, c.fullname, c.shortname
                    FROM {course} c
                    JOIN {local_packagecourses} lp ON c.id = lp.courseid
                    WHERE c.visible = :visible AND lp.hierarchyid = :packageid AND c.id $coursessql", $packagesparams);
} else {
	$courses = array();
}

$courseoptions = array();
if (!empty($courses)) {
	$count = 0;
    foreach ($courses as $c) {
    	$active = false;
        if ($count == 0) {
            $active = true;
        }
        $courseoptions[] = ['courseid' => $c->id, 'coursename' => format_string($c->fullname), 'courseshortname' => format_string($c->shortname), 'selected' => $active];
        $count++;
    }
    $courseid = $courseoptions[0]['courseid'];
} else {
	$courseid = 0;
}
echo "<input type='hidden' name='filter_courses' id='ls_courseid' class = 'report_courses' value=" . $courseid . " />";
echo "<input type='hidden' name='filter_users' id='ls_userid' class = 'report_users' value=" . $userid . " />";
$reportrender = $PAGE->get_renderer('block_reportdashboard');
$packagecoursesdata = $reportrender->get_packagecoursesdata($userid, $packageid);
$coursesdata = $reportrender->get_coursedata($userid, $courseoptions);
$coursewisedata = !empty($coursesdata) ? $coursesdata : false;
$coursedata = $coursewisedata ? $coursewisedata[0] : false;

// Chapterwise report
$chapterwisereport = $DB->get_record('block_learnerscript', ['type' => 'chapterwisereport']);
$chapterwisereportid = $chapterwisereport->id;
$chapterwisereportinstance = $chapterwisereport->id;
$chapterwisereporttype = 'table';

// Liveclass report.
$liveclassreport = $DB->get_record('block_learnerscript', ['type' => 'liveclassreport']);
$liveclassreportid = $liveclassreport->id;
$liveclassreportinstance = $liveclassreport->id;
$liveclassreporttype = 'table';

// Reading report.
$readingreport = $DB->get_record('block_learnerscript', ['type' => 'readingreport']);
$readingreportid = $readingreport->id;
$readingreportinstance = $readingreport->id;
$readingreporttype = 'table';

// Testscore report.
$testscorereport = $DB->get_record('block_learnerscript', ['type' => 'testscorereport']);
$testscorereportid = $testscorereport->id;
$testscorereportinstance = $testscorereport->id;
$testscorereporttype = 'table';

// Practice questions report.
$practicequestionsreport = $DB->get_record('block_learnerscript', ['type' => 'practicequestions']);
$practicequestionsreportid = $practicequestionsreport->id;
$practicequestionsinstance = $practicequestionsreport->id;
$practicequestionstype = 'table';
//echo '<pre>';print_r($coursedata);exit;
echo $OUTPUT->render_from_template('block_reportdashboard/studentdashboard',
								['userid' => $userid,
								'packages' => $packageoptions,
								'courses' => $courseoptions,
								'lsstartdate' => 0,
								'lsduedate' => 0,
								'coursecompletion' => !empty($packagecoursesdata['coursecompletion']) ? $packagecoursesdata['coursecompletion'] : '0',
                				'averagetestscore' => !empty($packagecoursesdata['averagetestscore']) ? $packagecoursesdata['averagetestscore'] : '0',
                				'timespend' => !empty($packagecoursesdata['timespend']) ? $packagecoursesdata['timespend'] : '0',
                				'attendance' => !empty($packagecoursesdata['attendance']) ? $packagecoursesdata['attendance'] : '0',
								'chapterwisereport' => $chapterwisereport,
								'chapterwisereportid' => $chapterwisereportid,
								'chapterwisereportinstance' => $chapterwisereportinstance,
								'chapterwisereporttype' => $chapterwisereporttype,
								'liveclassreport' => $liveclassreport,
								'liveclassreportid' => $liveclassreportid,
								'liveclassreportinstance' => $liveclassreportinstance,
								'liveclassreporttype' => $liveclassreporttype,
								'readingreport' => $readingreport,
								'readingreportid' => $readingreportid,
								'readingreportinstance' => $readingreportinstance,
								'readingreporttype' => $readingreporttype,
								'testscorereport' => $testscorereport,
								'testscorereportid' => $testscorereportid,
								'testscorereportinstance' => $testscorereportinstance,
								'testscorereporttype' => $testscorereporttype,
								'practicequestionsreport' => $practicequestionsreport,
								'practicequestionsreportid' => $practicequestionsreportid,
								'practicequestionsinstance' => $practicequestionsinstance,
								'practicequestionstype' => $practicequestionstype,
								'userid' => $userid,
								'packageid' => $packageid,
								'packagename' => $packagename,
								'packagecoursesdata' => $packagecoursesdata,
								'coursewisedata' => $coursewisedata,
								'coursedata' => $coursedata]);

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
