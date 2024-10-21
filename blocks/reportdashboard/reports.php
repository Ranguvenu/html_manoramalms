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
 * TODO describe file reports
 *
 * @package    block_reportdashboard
 * @copyright  2023 Jahnavi Nanduri <jahnavi.nanduri@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_learnerscript\local\ls as ls;

global $CFG, $PAGE, $OUTPUT, $DB;

require_login();

$courseid = optional_param('filter_course', 0, PARAM_INT);
$role = optional_param('role', '', PARAM_INT);
$learnerscript = get_config('block_learnerscript', 'ls_serialkey');

$PAGE->requires->jquery();
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
$PAGE->set_url(new moodle_url('/blocks/reportdashboard/reports.php'));
$PAGE->set_title(get_string('dashboard', 'block_reportdashboard'));
$PAGE->navbar->add(get_string('reports', 'block_reportdashboard'),
            new moodle_url('/blocks/reportdashboard/reports.php'));
$PAGE->requires->data_for_js("M.cfg.accessls", $learnerscript , true);

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => 'licenceresult', 'class' => 'lsaccess'));

$rolelist = (new ls)->get_currentuser_roles();
if (!is_siteadmin()) {
    if (!empty($role) && in_array($role, $rolelist)) {
        $role = empty($role) ? array_shift($rolelist) : $role;
    } else if (empty($role)) {
        $role = empty($role) ? array_shift($rolelist) : $role;
    } else {
        $role = $role;
    }
    $_SESSION['role'] = $role;
} else {
    $_SESSION['role'] = $role;
}

// Goals filter.
$goals = $DB->get_records_sql("SELECT id, name
                    FROM {local_hierarchy}
                    WHERE parent = :parentid AND depth = :depth",
                    ['parentid' => 0, 'depth' => 1]);
$goalsoptions = array();
if (!empty($goals)) {
    foreach ($goals as $c) {
        $goalsoptions[] = ['goalid' => $c->id, 'goalname' => format_string($c->name)];
    }
}
$dashboardgoalid = key($goals);

// Board filter.
$boards = $DB->get_records_sql("SELECT id, name
                    FROM {local_hierarchy}
                    WHERE parent = :parentid AND depth = :depth",
                    ['parentid' => $dashboardgoalid, 'depth' => 2]);
$boardsoptions = array();
if (!empty($boards)) {
    foreach ($boards as $c) {
        $boardsoptions[] = ['boardid' => $c->id, 'boardname' => format_string($c->name)];
    }
}
$dashboardboardid = key($boards);
// Class filter.
$classes = $DB->get_records_sql("SELECT id, name
                    FROM {local_hierarchy}
                    WHERE parent = :parentid AND depth = :depth",
                    ['parentid' => $dashboardboardid, 'depth' => 3]);
$classesoptions = array();
if (!empty($classes)) {
    foreach ($classes as $c) {
        $classesoptions[] = ['classid' => $c->id, 'classname' => format_string($c->name)];
    }
}
$dashboardclassid = key($classes);

// Subject filter.
$subjects = $DB->get_records_sql("SELECT id, name
                FROM {local_subjects}
                WHERE 1 = 1 AND classessid = :classid",
                ['classid' => $dashboardclassid]);
$subjectsoptions = array();
if (!empty($subjects)) {
    foreach ($subjects as $c) {
        $subjectsoptions[] = ['subjectid' => $c->id, 'subjectname' => format_string($c->name)];
    }
}
$dashboardsubjectid = key($subjects);

// Batch filter.
$batches = $DB->get_records_sql("SELECT lb.id, lb.name
                        FROM {local_batches} lb
                        JOIN {local_batch_courses} lbc ON lbc.batchid = lb.id
                        JOIN {local_packagecourses} lpc ON lpc.batchid = lbc.batchid
                                AND lpc.courseid = lbc.courseid
                        JOIN {local_subjects} ls ON ls.courseid = lpc.parentcourseid
                        WHERE 1 = 1 AND ls.id = :subjectid",
                    ['subjectid' => $dashboardsubjectid]);
$batchesoptions = array();
if (!empty($batches)) {
    foreach ($batches as $c) {
        $batchesoptions[] = ['batchid' => $c->id, 'batchname' => format_string($c->name)];
    }
} else {
    $batchesoptions[] = ['batchid' => 0, 'batchname' => 'Select batch'];
}
$dashboardbatchid = key($batches);

if ($dashboardbatchid > 0) {
    $courseid = $DB->get_field_sql("SELECT c.id
                    FROM {course} c
                    JOIN {local_batch_courses} lbc ON lbc.courseid = c.id
                    WHERE 1 = 1 AND lbc.batchid = :batchid",
                    ['batchid' => $dashboardbatchid]);
} else {
    $courseid = 0;
}

$reportrender = $PAGE->get_renderer('block_reportdashboard');
$studentsdetails = $reportrender->get_studentsdetails($courseid);
$_SESSION['courseid'] = $courseid;

$activitiesdata = $reportrender->get_activitiesdata($courseid);

$studentwisechapters = $DB->get_record('block_learnerscript', ['type' => 'studentwisechapters']);
$studentwisechapterreportid = $studentwisechapters->id;
$studentwisechapterinstance = $studentwisechapters->id;
$studentwisechapterstype = 'table';

$chapterdetails = $DB->get_record('block_learnerscript', ['type' => 'chapterdetails']);
$chapterdetailsreportid = $chapterdetails->id;
$chapterdetailsinstance = $chapterdetails->id;
$chapterdetailstype = 'table';

$liveclassdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Live Class']);
$reportcontenttypes = (new ls)->cr_listof_reporttypes($liveclassdetails->id);
$liveclassreportid = $liveclassdetails->id;
$liveclassinstance = $liveclassdetails->id;
$liveclasstype = key($reportcontenttypes);

$liveclassattendance = $DB->get_record('block_learnerscript', ['type' => 'liveclassattendance']);
$attendancereportcontenttypes = (new ls)->cr_listof_reporttypes($liveclassattendance->id);
$liveclassattendanceid = $liveclassattendance->id;
$liveclassattendanceinstance = $liveclassattendance->id;
$liveclassattendancetype = key($attendancereportcontenttypes);

$readingdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Reading']);
$readingcontenttypes = (new ls)->cr_listof_reporttypes($readingdetails->id);
$readingreportid = $readingdetails->id;
$readingtype = key($readingcontenttypes);

$practicetestdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Practice Test']);
$practicetestcontenttypes = (new ls)->cr_listof_reporttypes($practicetestdetails->id);
$practicetestreportid = $practicetestdetails->id;
$practicetesttype = key($practicetestcontenttypes);

$testscoredetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Test Score']);
$testscorecontenttypes = (new ls)->cr_listof_reporttypes($testscoredetails->id);
$testscorereportid = $testscoredetails->id;
$testscoretype = key($testscorecontenttypes);

$forumdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Forum']);
$forumcontenttypes = (new ls)->cr_listof_reporttypes($forumdetails->id);
$forumreportid = $forumdetails->id;
$forumtype = key($forumcontenttypes);

$testscorelinedetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Test scores']);
$testscorelinecontenttypes = (new ls)->cr_listof_reporttypes($testscorelinedetails->id);
$testscorelinereportid = $testscorelinedetails->id;
$testscorelinetype = key($testscorelinecontenttypes);

echo "<input type='hidden' name='filter_goals' id='ls_goalid' class = 'report_goals' value=" . $dashboardgoalid . " />";
echo "<input type='hidden' name='filter_boards' id='ls_boardid' class = 'report_boards' value=" . $dashboardboardid . " />";
echo "<input type='hidden' name='filter_classes' id='ls_classid' class = 'report_classes' value=" . $dashboardclassid . " />";
echo "<input type='hidden' name='filter_subjects' id='ls_subjectid' class = 'report_subjects' value=" . $dashboardsubjectid . " />";
echo "<input type='hidden' name='filter_batches' id='ls_batchid' class = 'report_batches' value=" . $dashboardbatchid . " />";
echo "<input type='hidden' name='filter_courses' id='ls_courseid' class = 'report_courses' value=" . $courseid . " />";

echo $OUTPUT->render_from_template('block_reportdashboard/admin_dashboard',
                                ['goalsoptions' => $goalsoptions,
                                'boardsoptions' => $boardsoptions,
                                'classesoptions' => $classesoptions,
                                'subjectsoptions' => $subjectsoptions,
                                'batchesoptions' => $batchesoptions,
                                'totalstudents' => $studentsdetails['totalstudents'],
                                'activestudents' => $studentsdetails['activestudents'],
                                'avgtimespent' => $studentsdetails['avgtimespent'],
                                'completionrate' => $studentsdetails['completionrate'],
                                'avgcompletion' => $studentsdetails['avgcompletion'],
                                'avgattendance' => $studentsdetails['avgattendance'],
                                'lastliveclasscount' => $studentsdetails['lastliveclasscount'],
                                'testscoresprogress' => $studentsdetails['testscoresprogress'],
                                'lasttestcount' => $studentsdetails['lasttestcount'],
                                'studentwisechapterreportid' => $studentwisechapterreportid,
                                'studentwisechapterinstance' => $studentwisechapterinstance,
                                'studentwisechapterstype' => $studentwisechapterstype,
                                'chapterdetailsreportid' => $chapterdetailsreportid,
                                'chapterdetailsinstance' => $chapterdetailsinstance,
                                'chapterdetailstype' => $chapterdetailstype,
                                'liveclassreportid' => $liveclassreportid,
                                'liveclassinstance' => $liveclassinstance,
                                'liveclasstype' => $liveclasstype,
                                'liveclassattendanceid' => $liveclassattendanceid,
                                'liveclassattendanceinstance' => $liveclassattendanceinstance,
                                'liveclassattendancetype'   => $liveclassattendancetype,
                                'readingreportid' => $readingreportid,
                                'readingtype' => $readingtype,
                                'practicetestreportid' => $practicetestreportid,
                                'practicetesttype' => $practicetesttype,
                                'testscorereportid' => $testscorereportid,
                                'testscoretype' => $testscoretype,
                                'testscorelinereportid' => $testscorelinereportid,
                                'testscorelinetype' => $testscorelinetype,
                                'forumreportid' => $forumreportid,
                                'forumtype' => $forumtype,
                                'courseid' => $courseid,
                                'totalliveclasses' => $activitiesdata['totalliveclasses'],
                                'zoomtimespend' => $activitiesdata['zoomtimespend'],
                                'pagetime' => $activitiesdata['pagetime'],
                                'avgpagecompletion' => $activitiesdata['avgpagecompletion'],
                                'unreadpagecount' => $activitiesdata['unreadpagecount'],
                                'totalpracticetests' => $activitiesdata['totalpracticetests'],
                                'unattemptedtestcount' => $activitiesdata['unattemptedtestcount'],
                                'avgtestscore' => $activitiesdata['avgtestscore'],
                                'totaltestscores' => $activitiesdata['totaltestscores'],
                                'unattemptedtscount' => $activitiesdata['unattemptedtscount'],
                                'expiredtests' => $activitiesdata['expiredtests'],
                                'activetests' => $activitiesdata['activetests']
                            ]);

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
