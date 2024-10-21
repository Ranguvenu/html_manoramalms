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

defined('MOODLE_INTERNAL') || die();

use block_reportdashboard\local;
use block_learnerscript\local\ls as ls;

/**
 * Block Report Dashboard renderer.
 * @package   block_reportdashboard
 */
class block_reportdashboard_renderer extends plugin_renderer_base {

    public function render_widgetheader($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_reportdashboard/widgetheader', $data);
    }
    public function render_reportarea($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_reportdashboard/reportarea', $data);
    }
    public function render_dashboardheader($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_reportdashboard/dashboardheader', $data);
    }
    /**
     * List of role for current user
     * @return [string] [list of roles]
     */
    public function switch_role() {
        global $USER, $DB, $OUTPUT, $COURSE, $CFG;
        $actions = '';
        if (!empty($_SESSION['role'])) {
            $currentrole = $_SESSION['role'];
        } else {
            $currentrole = 'Switch Role';
        }
        $actions .= html_writer::start_tag("span", ["class" => "dropdown", "id" => "switchrole_dropdwn"]);
        $actions .= html_writer::tag("button", $currentrole, ["class" => "dropbtn",
            "onclick" => "(function(e){ require('block_learnerscript/helper').dropdown('switchrole_menu')
                })(event)",]);
        $actions .= html_writer::start_tag("ul", ["id" => "switchrole_menu", "class" => "dropdown-content"]);
        $systemcontext = context_system::instance();
        if (!is_siteadmin()) {
            $roles = (new ls)->get_currentuser_roles();
        } else {
            $roles = get_switchable_roles($systemcontext);
        }
        $actions .= html_writer::start_tag("li", ["role" => "presentation"])
            . html_writer::link($CFG->wwwroot . '/blocks/reportdashboard/dashboard.php', 'Switch Role', [])
            . html_writer::end_tag("li");
        foreach ($roles as $key => $value) {
            $roleshortname = $DB->get_field('role', 'shortname', ['id' => $key]);
            $roleurl = new moodle_url('/blocks/reportdashboard/dashboard.php',
                ['role' => $roleshortname]);
            $actions .= html_writer::start_tag("li", ["role" => "presentation"])
                . html_writer::link($roleurl, $value, [])
                . html_writer::end_tag("li");
        }

        $actions .= html_writer::end_tag("ul");
        $actions .= html_writer::end_tag("span");
        return $actions;
    }

    /**
     * This funtion return the students detaisl for the selected batch course.
     * @param  int $courseid Selected batch course ID
     * @return [type] [description]
     */
    public function get_studentsdetails($courseid = false) {
        global $DB, $CFG;
        // Total students tile.
        $totalstudentslist = $DB->get_records_sql("SELECT DISTINCT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        WHERE c.visible = 1 AND c.id = :courseid", ['courseid' => $courseid]);
        $totalstudents = !empty($totalstudentslist) ? count($totalstudentslist) : 0;
        foreach ($totalstudentslist as $student) {
            $studentslist[] = $student->userid;
        }

        // Active students tile.
        $studentsloggedin = $DB->get_field_sql("SELECT COUNT(DISTINCT a.userid) FROM(SELECT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
                        AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                        JOIN {zoom_meeting_participants} zmp ON zmp.userid = u.id
                        JOIN {zoom_meeting_details} zmd ON zmd.id = zmp.detailsid
                        JOIN {course_modules} cm ON cm.instance = zmd.zoomid
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
                        JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
                        WHERE cm.course = c.id AND c.id = :courseid AND z.start_time <= UNIX_TIMESTAMP()
                        UNION
                        SELECT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
                        AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                        JOIN {logstore_standard_log} lgs ON lgs.userid = u.id AND lgs.action= 'loggedin'
                        AND c.id = :ccourseid
                        UNION
                        SELECT DISTINCT qa.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
                        AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                        JOIN {quiz} q ON q.course = c.id
                        JOIN {quiz_attempts} qa ON qa.quiz = q.id AND qa.userid = ue.userid
                        WHERE c.id = :qcourseid)
                        as a WHERE 1 = 1",
                        ['courseid' => $courseid, 'ccourseid' => $courseid,
                            'qcourseid' => $courseid]);
        $activestudents = !empty($studentsloggedin) ? $studentsloggedin : 0;

        // Average timespent tile.
        if (!empty($totalstudentslist)) {
            list($ssql, $params) = $DB->get_in_or_equal($studentslist, SQL_PARAMS_NAMED);
        } else {
            $ssql = " = 0";
        }
        $params['courseid'] = $courseid;
        $timespent = $DB->get_field_sql("SELECT AVG(a.duration) FROM
                        (SELECT SUM(zmp.duration) AS duration
                        FROM {zoom_meeting_details} zmd
                        JOIN {zoom_meeting_participants} zmp ON zmd.id = zmp.detailsid
                        JOIN {course_modules} cm ON cm.instance = zmd.zoomid
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
                        JOIN {zoom} z ON z.id = cm.instance
                        WHERE 1 = 1 AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid $ssql
                        GROUP BY zmp.userid, z.id) AS a", $params);
        $avgtimespent = !empty($timespent) ? (new ls)->strTime($timespent) : 0;

        // Course completion rate.
        $completionrate = $DB->get_field_sql("SELECT COUNT(DISTINCT cc.userid)
                        FROM {course_completions} cc
                        WHERE cc.timecompleted IS NOT NULL
                        AND cc.course = :courseid", ['courseid' => $courseid]);
        // Avg course completion.
        /*$activitiescountsql = $DB->get_records_sql("SELECT cm.id FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
            AND cm.course = :courseid",
            ['cmvisible' => 1, 'deletioninprogress' => 0, 'courseid' => $courseid]);
        $e = 0;
        $f = 0;
        $activitiescount = count($activitiescountsql);
        foreach ($activitiescountsql as $activity) {
            $activitycompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $activity->id]);
            if ((count($activitycompletions) == $totalstudents) && !empty($totalstudents)) {
                $e++;
            } else {
                $f++;
            }
        }*/
        $completedactivitiesbyusers = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.userid) as completed
                            FROM {course_modules_completion} AS cmc
                            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                            WHERE cmc.completionstate > 0 AND cmc.userid $ssql AND cm.course = :courseid", $params);

        $avgcompletion = !empty($totalstudentslist) ? round(($completedactivitiesbyusers / count($totalstudentslist)) * 100, 0) . '%' : '0%';

        // Average attendance.
        $liveclasses = $DB->get_field_sql("SELECT count(cm.id) FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
                    JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
                    WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                    AND m.name = :modulename AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP()",
                    ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'zoom'
                    , 'courseid' => $courseid]);

        $attendedclasses = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/(SELECT SUM(zmd.end_time-zmd.start_time)
                FROM {zoom_meeting_details} zmd
                JOIN {zoom} zoom ON zoom.id = zmd.zoomid
                WHERE zoom.id = z.id AND zoom.start_time <= UNIX_TIMESTAMP()))*100, 2) as attend
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
        JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
        JOIN {zoom_meeting_participants} zmp ON zmp.detailsid = zmd.id
        WHERE cm.visible = :visible AND cm.deletioninprogress = :deletioninprogress
        AND m.name = 'zoom' AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid in (SELECT DISTINCT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        WHERE c.visible = 1 AND c.id = $courseid)) as s WHERE s.attend > 80", ['courseid' => $courseid, 'visible' => 1, 'deletioninprogress'  => 0]);

        $avgattendance = !empty($liveclasses) ? ($attendedclasses / $liveclasses) : 0;
        $avgattendance_percent = !empty($totalstudentslist) ? round(($avgattendance / COUNT($totalstudentslist)) * 100, 2) : 0;
        // Students present in last live class.

        $lastliveclasscount = $DB->get_field_sql("SELECT COUNT(DISTINCT ue.userid)
                                FROM {user_enrolments} ue
                                JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                                JOIN {course} c ON e.courseid = c.id AND e.status = 0
                                JOIN {role_assignments}  ra ON ra.userid = ue.userid
                                JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                                JOIN {context} ctx ON ctx.instanceid = c.id
                                JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
                                AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                                JOIN {zoom_meeting_participants} zmp ON zmp.userid = ue.userid
                                WHERE zmp.detailsid = (SELECT zmd.id
                                                            FROM {zoom_meeting_details} zmd
                                                            JOIN {zoom} z ON z.id = zmd.zoomid
                                                            WHERE 1=1 AND z.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() ORDER BY zmd.id DESC LIMIT 1)", ['courseid' => $courseid]);
        $participantscount = !empty($lastliveclasscount) ? $lastliveclasscount : 0;

        // Average test score.

        $testscoressql = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
                    FROM(SELECT u.id as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :qcourseid AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL GROUP BY u.id
                    UNION SELECT u.id as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {assign} a ON a.id = cm.instance
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :acourseid AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL GROUP BY u.id) as a",
                    ['qcourseid' => $courseid, 'acourseid' => $courseid]);
        $testscoresprogress = !empty($testscoressql) ? round($testscoressql) . '%' : '0%';

        // Students attended last test.
        $lasttestcount = $DB->get_field_sql("SELECT t.usercount
                    FROM (SELECT q.id, q.timeopen as activitystart, m.name, COUNT(DISTINCT qa.userid) AS usercount
                    FROM mdl_course_modules cm
                    JOIN mdl_modules m ON m.id = cm.module
                    JOIN mdl_course c ON c.id = cm.course
                    JOIN mdl_quiz q ON q.id = cm.instance AND q.testtype =0
                    JOIN mdl_quiz_attempts qa ON qa.quiz = q.id
                    WHERE c.id = :qcourseid AND m.name = 'quiz' AND q.timeopen != 0 AND q.timeopen <= UNIX_TIMESTAMP()
                    GROUP BY qa.userid
                    UNION
                    SELECT a.id, a.allowsubmissionsfromdate as activitystart, m.name, COUNT(DISTINCT asb.userid) AS usercount
                    FROM mdl_course_modules cm
                    JOIN mdl_modules m ON m.id = cm.module
                    JOIN mdl_course c ON c.id = cm.course
                    JOIN mdl_assign a ON a.id = cm.instance
                    JOIN mdl_assign_submission asb ON asb.assignment = a.id
                    WHERE c.id = :acourseid AND m.name = 'assign' AND a.allowsubmissionsfromdate <= UNIX_TIMESTAMP()
                    GROUP BY asb.userid)
                    as t WHERE 1=1 ORDER BY t.activitystart DESC LIMIT 1", ['qcourseid' => $courseid, 'acourseid' => $courseid]);
        $lasttestcount = !empty($lasttestcount) ? $lasttestcount : 0;

        return ['totalstudents' => $totalstudents,
                'activestudents' => $activestudents,
                'avgtimespent' => $avgtimespent,
                'completionrate' => $completionrate,
                'avgcompletion' => $avgcompletion,
                'avgattendance' => $avgattendance_percent,
                'lastliveclasscount' => $participantscount,
                'testscoresprogress' => $testscoresprogress,
                'lasttestcount' => $lasttestcount];
    }

    public function get_adminreportsdata($courseid = false) {
        global $DB, $CFG;
        // Studentwise report.
        $studentwisechapters = $DB->get_record('block_learnerscript', ['type' => 'studentwisechapters']);
        $studentwisechapterreportid = $studentwisechapters->id;
        $studentwisechapterinstance = $studentwisechapters->id;
        $studentwisechapterstype = 'table';

        // Chapterdetails report.
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

        $testscorelinedetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Test scores']);
        $testscorelinecontenttypes = (new ls)->cr_listof_reporttypes($testscorelinedetails->id);
        $testscorelinereportid = $testscorelinedetails->id;
        $testscorelinetype = key($testscorelinecontenttypes);

        $forumdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Forum']);
        $forumcontenttypes = (new ls)->cr_listof_reporttypes($forumdetails->id);
        $forumreportid = $forumdetails->id;
        $forumtype = key($forumcontenttypes);

        return ['studentwisechapterreportid' => $studentwisechapterreportid,
            'studentwisechapterinstance' => $studentwisechapterinstance,
            'studentwisechapterstype' => $studentwisechapterstype,
            'chapterdetailsreportid' => $chapterdetailsreportid,
            'chapterdetailsinstance' => $chapterdetailsinstance,
            'chapterdetailstype' => $chapterdetailstype,
            'liveclassreportid' => $liveclassreportid,
            'liveclassinstance' => $liveclassinstance,
            'liveclasstype' => $liveclasstype,
            'liveclassattendanceid' => $liveclassattendanceid,
            'liveclassattendanceinstance'   => $liveclassattendanceinstance,
            'liveclassattendancetype'   => $liveclassattendancetype,
            'readingreportid' => $readingreportid,
            'readingtype' => $readingtype,
            'practicetestreportid' => $practicetestreportid,
            'practicetesttype' => $practicetesttype,
            'testscorereportid' => $testscorereportid,
            'testscoretype' => $testscoretype,
            'forumreportid' => $forumreportid,
            'forumtype' => $forumtype,
            'testscorelinereportid' => $testscorelinereportid,
            'testscorelinetype' => $testscorelinetype,
            'courseid' => $courseid
        ];
    }

    /**
     * [get_packagecoursesdata description]
     * @param  [type] $packageid [description]
     * @return [type] [description]
     */
    public function get_packagecoursesdata($userid, $packageid, $lsstartdate = null , $lsduedate = null) {
        global $DB, $CFG;

        $startdate = ($lsstartdate) ? $lsstartdate : '';
        $duedate = ($lsduedate) ? $lsduedate : '';
        $packagename = $DB->get_field('local_hierarchy', 'name', array('depth' => 4));
        $courses = $DB->get_records_sql("SELECT c.id, c.fullname
                    FROM {course} c
                    JOIN {local_packagecourses} lp ON c.id = lp.courseid
                    JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
                    WHERE c.visible = :visible AND lp.hierarchyid = :packageid AND ue.userid = :userid",
                    ['visible' => 1, 'packageid' => $packageid, 'userid' => $userid]);
        if (!empty($courses)) {
            $courseslist = array_keys($courses);
            list($coursesql, $params) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED);
            $params['userid'] = $userid;
            $coursecompconcat = '';
            $avgtestscoreconcat = '';
            $timestampconcat = '';
            $attendedconcat = '';
            if(!empty($startdate) && ($startdate != 0)) {
                $coursecompconcat .= " AND cmc.timemodified >= $startdate";
                $avgtestscoreconcat .= " AND gi.timemodified >= $startdate";
                $timestampconcat .= " AND timemodified >= $startdate";
                $attendedconcat .= " AND z.start_time >= $startdate";
                $coursecriteria .= " AND cmc.timecompleted >= $startdate";
            }
            if(!empty($duedate) && ($duedate != 0)) {
                $coursecompconcat .= " AND cmc.timemodified <= $duedate";
                $avgtestscoreconcat .=" AND gi.timemodified <= $duedate";
                $timestampconcat .= " AND timemodified <= $duedate";
                $attendedconcat .= " AND z.start_time <= $duedate";
                $coursecriteria .= " AND cmc.timecompleted <= $duedate";
            }

            $coursepercentage = [];
            $averagetests = [];
            foreach ($courses as $ck => $cv) {
                $coursecriteria = $DB->get_field_sql("SELECT DISTINCT cmc.id
                    FROM {course_completions} cmc
                    WHERE cmc.timecompleted IS NOT NULL AND cmc.course = :courseid AND cmc.userid = :userid", ['courseid' => $cv->id, 'userid' => $userid]);

                if(!empty($coursecriteria)) {
                    $coursepercentage[] = 100;
                } else {
                    $totalactivites = $DB->get_field_sql("SELECT COUNT(DISTINCT cm.id)
                    FROM {course_modules} cm
                    WHERE cm.visible = 1
                    AND cm.course = :courseid", ['courseid' => $cv->id]);

                    $completed = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.id)
                    FROM {course_modules_completion} cmc
                    JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                    WHERE cm.visible = :visible  AND cmc.userid = :userid
                    AND cmc.completionstate > :completionstate AND cm.course = :courseid $coursecompconcat ",
                    ['visible' => 1, 'userid' => $userid, 'completionstate' => 0, 'courseid' => $cv->id]);

                    $coursepercentage[] = !empty($totalactivites) ? round(($completed / $totalactivites) * 100, 2) : 0;
                }

            }
            // Average testscore
            $encourselist = implode(',', $courseslist);
             $testmodules = $DB->get_records_sql("SELECT gi.id as gradeid, (gg.finalgrade/q.grade) as grade
                FROM mdl_grade_grades AS gg
                JOIN mdl_grade_items gi ON gi.id = gg.itemid
                JOIN mdl_course_modules cm ON cm.instance = gi.iteminstance
                JOIN mdl_modules m ON m.id = cm.module
                JOIN mdl_course c ON c.id = cm.course
                JOIN mdl_quiz q ON q.id = cm.instance AND q.testtype =0
                JOIN mdl_user u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                WHERE c.id IN($encourselist) AND gg.userid = :quserid AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL $avgtestscoreconcat
                UNION
                SELECT gi.id as gradeid, (gg.finalgrade/a.grade) as grade
                FROM mdl_grade_grades AS gg
                JOIN mdl_grade_items gi ON gi.id = gg.itemid
                JOIN mdl_course_modules cm ON cm.instance = gi.iteminstance
                JOIN mdl_modules m ON m.id = cm.module
                JOIN mdl_course c ON c.id = cm.course
                JOIN mdl_assign a ON a.id = cm.instance
                JOIN mdl_user u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                WHERE c.id IN($encourselist) AND gg.userid = :auserid AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL $avgtestscoreconcat", ['quserid' => $userid, 'auserid' => $userid]);
             $gradesum = [];
             $testcount = [];
                if(!empty($testmodules)) {
                    foreach($testmodules as $t => $tm) {
                        $gradesum[] = $tm->grade;
                    }
                }
                $averagetestscore = !empty($testmodules) ? ROUND((array_sum($gradesum)/count($testmodules))*100,2) : 0;
                $coursecompletion = $coursepercentage ? round((array_sum($coursepercentage)/count($courses)), 2) : 0;

                $coursecompletioncolor = $this->get_progressbar_color($coursecompletion);
                $averagetestscorecolor = $this->get_progressbar_color($averagetestscore);
                
            // Timespent.
            $coursetimespent = $DB->get_field_sql("SELECT SUM(timespent)
                                            FROM {block_ls_coursetimestats}
                                            WHERE 1 = 1 AND userid = :userid
                                            AND courseid $coursesql $timestampconcat", $params);
            $liveclasstimespent = $DB->get_field_sql("SELECT SUM(zmp.duration) AS duration
                        FROM {zoom_meeting_details} zmd
                        JOIN {zoom_meeting_participants} zmp ON zmd.id = zmp.detailsid
                        JOIN {course_modules} cm ON cm.instance = zmd.zoomid
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
                        JOIN {zoom} z ON z.id = cm.instance
                        WHERE 1 = 1 AND cm.course $coursesql AND zmp.userid = :userid AND z.start_time <= UNIX_TIMESTAMP() AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $attendedconcat GROUP BY cm.course", $params);

            $coursetimespent = !empty($coursetimespent) ? $coursetimespent : 0;
            $liveclasstimespent = !empty($liveclasstimespent) ? $liveclasstimespent : 0;

            $totaltimespent = $coursetimespent + $liveclasstimespent;
            $timespend = (!empty($totaltimespent) && $totaltimespent !=0 ) ? (new ls)->strtime($totaltimespent) : 0;

            // Attendance.
            list($coursesql, $courseparams) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED);
            list($liveclasssql, $liveclassparams) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED);
            $courseparams['cmvisible'] = 1;
            $courseparams['deletioninprogress'] = 0;
            $courseparams['modulename'] = 'zoom';
            $liveclassparams['cmvisible'] = 1;
            $liveclassparams['deletioninprogress'] = 0;
            $liveclassparams['modulename'] = 'zoom';
            $liveclassparams['userid'] = $userid;

            $liveclasses = $DB->get_field_sql("SELECT count(cm.instance)
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {zoom} z ON z.id = cm.instance
                WHERE 1 = 1 AND cm.visible = :cmvisible
                AND cm.deletioninprogress = :deletioninprogress
                AND m.name = :modulename AND cm.course $coursesql AND z.start_time <= UNIX_TIMESTAMP() AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $attendedconcat", $courseparams);

            $attendedclasses = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/(SELECT SUM(zmd.end_time-zmd.start_time)
                FROM {zoom_meeting_details} zmd
                JOIN {zoom} zoom ON zoom.id = zmd.zoomid
                WHERE zoom.id = z.id AND zoom.start_time <= UNIX_TIMESTAMP()))*100, 2) as attend
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
                JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
                JOIN {zoom_meeting_participants} zmp ON zmp.detailsid = zmd.id
                WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                AND m.name = :modulename AND zmp.userid = :userid AND z.start_time <= UNIX_TIMESTAMP() AND cm.course $liveclasssql AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $attendedconcat GROUP BY z.id) as s WHERE s.attend > 80", $liveclassparams);
            $attendedclasses = ($attendedclasses > $liveclasses) ? $liveclasses : $attendedclasses;
            $attendance = !empty($liveclasses) ? ($attendedclasses .'/'. $liveclasses) : 0;


            return ['coursecompletion' => $coursecompletion,
                'averagetestscore' => $averagetestscore,
                'timespend' => $timespend,
                'attendance' => $attendance,
                'packagename'   => $packagename,
                'coursecompletioncolor' => $coursecompletioncolor,
                'averagetestscorecolor' => $averagetestscorecolor
            ];
        } else {
            return ['coursecompletion' => 0,
                'averagetestscore' => 0,
                'timespend' => 0,
                'attendance' => 0,
                'packagename'   => $packagename,
                'coursecompletioncolor' => $this->get_progressbar_color(0),
                'averagetestscorecolor' => $this->get_progressbar_color(0)
            ];
        }
    }

    /**
     * [get_coursedata description]
     * @param  [int] $userid [description]
     * @param  [array] $courses [description]
     * @param  [date] $startdate [description]
     * @param  [date] $duedate [description]
     * @return [type] [description]
     */
    public function get_coursedata($userid, $courses = [], $lsstartdate = null, $lsduedate = null) {

        if (!empty($courses)) {

            $coursewisedata = [];
            $count = 0;
            foreach ($courses as $k => $v) {
                $active = false;
                if ($count == 0) {
                    $active = true;
                }
                $coursedata = $this->coursedata($v['courseid'], $userid, $lsstartdate, $lsduedate);
                $coursewisedata[] = ['id' => $v['courseid'],
                'coursename' => $v['coursename'],
                'courseid' => $v['courseid'],
                'course_shortname'   => $v['courseshortname'],
                'userid' => $userid,
                'selected' => $active,
                'coursepercentage' => $coursedata['coursepercentage'],
                'avgtestscore' => $coursedata['avgtestscore'],
                'practicetest' => $coursedata['practicetest'],
                'reading' => $coursedata['reading'],
                'liveclass' => $coursedata['liveclass'],
                'video' => $coursedata['video'],
                'forumquestions' => $coursedata['forumquestions'],
                'liveclasspercentage' => $coursedata['liveclasspercentage'],
                'practicequestion' => $coursedata['practicequestion'],
                'test' => $coursedata['test'],
                'total' => $coursedata['total'],
                'attended' => $coursedata['attended'],
                'fullattended' => $coursedata['fullattended'],
                'partiallyattended' => $coursedata['partiallyattended'],
                'missedliveclass' => $coursedata['missedliveclass'],
                'readingcompleted' => $coursedata['readingcompleted'],
                'timespent' => $coursedata['timespent'],
                'attempted' => $coursedata['attempted'],
                'answered' => $coursedata['answered'],
                'correct' => $coursedata['correct'],
                'wrong' => $coursedata['wrong'],
                'availabletests' => $coursedata['availabletests'],
                'submitted' => $coursedata['submitted'],
                'missed' => $coursedata['missed'],
                'chapterwisereportid' => $coursedata['chapterwisereportid'],
                'chapterwisereportinstance' => $coursedata['chapterwisereportinstance'],
                'chapterwisereporttype' => $coursedata['chapterwisereporttype'],
                'liveclassreportid' => $coursedata['liveclassreportid'],
                'liveclassreportinstance' => $coursedata['liveclassreportinstance'],
                'liveclassreporttype' => $coursedata['liveclassreporttype'],
                'readingreportid' => $coursedata['readingreportid'],
                'readingreportinstance' => $coursedata['readingreportinstance'],
                'readingreporttype' => $coursedata['readingreporttype'],
                'testscorereportid' => $coursedata['testscorereportid'],
                'testscorereportinstance' => $coursedata['testscorereportinstance'],
                'testscorereporttype' => $coursedata['testscorereporttype'],
                'practicequestionsreportid' => $coursedata['practicequestionsreportid'],
                'practicequestionsinstance' => $coursedata['practicequestionsinstance'],
                'practicequestionstype' => $coursedata['practicequestionstype'],
                'coursecompletioncolor' => $this->get_progressbar_color($coursedata['coursepercentage']),
                'averagetestscorecolor' => $this->get_progressbar_color($coursedata['avgtestscore']),
                'practicetestcolor' => $this->get_progressbar_color($coursedata['practicetest']),
                'readingcolor' => $this->get_progressbar_color($coursedata['reading']),
                'liveclasscolor' => $this->get_progressbar_color($coursedata['liveclasspercentage']),
                'videocolor' => $this->get_progressbar_color($coursedata['video'])];
                $count++;
            }
            return $coursewisedata;
        }
    }

    /**
     * [coursedata description]
     * @param  [int] $userid [description]
     * @return [type] [description]
     */
    public function coursedata($courseid, $userid, $lsstartdate = null, $lsduedate = null) {
        global $DB, $CFG;
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

         $startdate = ($lsstartdate) ? $lsstartdate : '';
        $duedate = ($lsduedate) ? $lsduedate : '';

        $completedconcat = '';
        $avgtestscoreconcat = '';
        $practicetestconcat = '';
        $attendedconcat = '';
        $readingconcat = '';
        $forumconcat = '';
        $questionsconcat = '';
        $readingtimeconcat = '';
        $testscoreqzconcat = '';
        $testscoreassconcat = '';
        $assignconcat = '';
        $quizconcat = '';
        if(!empty($startdate) && ($startdate !=0)) {
            $completedconcat .= " AND cmc.timemodified >= $startdate";
            $avgtestscoreconcat .= " AND gi.timemodified >= $startdate";
            $practicetestconcat .= " AND cmc.timemodified >= $startdate";
            $attendedconcat .= " AND z.start_time >= $startdate";
            $readingconcat .= " AND cmc.timemodified >= $startdate";
            $forumconcat .= " AND timemodified >= $startdate";
            $questionsconcat .= " AND qat.timemodified >= $startdate";
            $readingtimeconcat .= " AND mt.timemodified >= $startdate";
            $testscoreqzconcat .= "AND q.timecreated >= $startdate";
            $testscoreassconcat .= "AND ass.timemodified >= $startdate";
            $assignconcat .= " AND timemodified >= $startdate";
            $quizconcat .= " AND qa.timemodified >= $startdate"; 
            $cmadded .= " AND cm.added >= $startdate"; 
            $zmdstarttime .= " AND z.start_time >= $startdate";
            $quizopen .= " AND q.timeopen >= $startdate";
            $assopen .= " AND ass.allowsubmissionsfromdate >= $startdate";
            $coursecriteria .= " AND cmc.timecompleted >= $startdate";
        }
        if(!empty($duedate) && ($duedate != 0)) {
            $completedconcat .= " AND cmc.timemodified <= $duedate";
            $avgtestscoreconcat .= " AND gi.timemodified <= $duedate";
            $practicetestconcat .= " AND cmc.timemodified <= $duedate";
            $attendedconcat .= " AND z.start_time <= $duedate";
            $readingconcat .= " AND cmc.timemodified <= $duedate";
            $forumconcat .= " AND timemodified <= $duedate";
            $questionsconcat .= " AND qat.timemodified <= $duedate";
            $readingtimeconcat .= " AND mt.timemodified <= $duedate";
            $testscoreqzconcat .= " AND q.timecreated <= $duedate";
            $testscoreassconcat .= " AND ass.timemodified <= $duedate";
            $assignconcat .= " AND timemodified <= $duedate";
            $quizconcat .= " AND qa.timemodified <= $duedate";
            $cmadded .= " AND cm.added <= $duedate"; 
            $zmdstarttime .= " AND z.start_time <= $duedate";
            $quizopen .= " AND q.timeopen <= $duedate";
            $assopen .= " AND ass.allowsubmissionsfromdate <= $duedate";
            $coursecriteria .= " AND cmc.timecompleted <= $duedate";
        }

        // Course completion.
            $coursecriteria = $DB->get_field_sql("SELECT DISTINCT cmc.id
                FROM {course_completions} cmc
                WHERE cmc.userid = :userid
                AND cmc.timecompleted IS NOT NULL AND cmc.course = :courseid $coursecriteria ",
                ['userid' => $userid, 'courseid' => $courseid]);

                if(!empty($coursecriteria)) {
                    $coursepercentage = 100;
                } else {
                    $totalactivites = $DB->get_field_sql("SELECT COUNT(DISTINCT cm.id)
                        FROM {course_modules} cm
                        WHERE cm.visible = 1
                        AND cm.course = :courseid $cmadded", ['courseid' => $courseid]);

                    $completed = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.id)
                        FROM {course_modules_completion} cmc
                        JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                        WHERE cm.visible = :visible AND cmc.userid = :userid
                        AND cmc.completionstate > :completionstate AND cm.course = :courseid $completedconcat ",
                        ['visible' => 1, 'userid' => $userid, 'completionstate' => 0, 'courseid' => $courseid]);
                    $coursepercentage = !empty($totalactivites) ? round(($completed / $totalactivites) * 100, 2) : 0;
                }
           

            
            //$coursepercentage = round($check);
            
        // Avg test score.
            $averagetestscoredata = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
            FROM(SELECT u.id as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
            FROM mdl_grade_grades AS gg
            JOIN mdl_grade_items gi ON gi.id = gg.itemid
            JOIN mdl_course_modules cm ON cm.instance = gi.iteminstance
            JOIN mdl_modules m ON m.id = cm.module
            JOIN mdl_course c ON c.id = cm.course
            JOIN mdl_quiz q ON q.id = cm.instance AND q.testtype =0
            JOIN mdl_user u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
            WHERE c.id = :qcourseid AND gg.userid = :quserid AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL $avgtestscoreconcat GROUP BY u.id
            UNION SELECT u.id as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
            FROM mdl_grade_grades AS gg
            JOIN mdl_grade_items gi ON gi.id = gg.itemid
            JOIN mdl_course_modules cm ON cm.instance = gi.iteminstance
            JOIN mdl_modules m ON m.id = cm.module
            JOIN mdl_course c ON c.id = cm.course
            JOIN mdl_assign a ON a.id = cm.instance
            JOIN mdl_user u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
            WHERE c.id = :acourseid AND gg.userid = :auserid AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL $avgtestscoreconcat GROUP BY u.id) as a GROUP BY a.userid", ['qcourseid' => $courseid, 'quserid' => $userid, 'acourseid' => $courseid, 'auserid' => $userid]);

                $avgtestscore = !empty($averagetestscoredata) ? round($averagetestscoredata, 2) : 0;

        // Practice test.
            $practicetestsql = $DB->get_records_sql("SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {quiz} q ON q.id = cm.instance
                WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                AND m.name = :modulename
                AND q.testtype = :testtype AND q.course = :courseid $cmadded",
                ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'quiz',
                'testtype' => 1, 'courseid' => $courseid]);
            $i = 0;
            $j = 0;
            $practicetestcount = count($practicetestsql);
            foreach ($practicetestsql as $practicetest) {
                $practicetestcmplts = $DB->get_field_sql("SELECT cmc.id FROM {course_modules_completion} cmc WHERE cmc.coursemoduleid = :coursemoduleid AND cmc.completionstate > :state AND cmc.userid = :userid $practicetestconcat", ['coursemoduleid' => $practicetest->id,
                                    'state' => 0, 'userid' => $userid]);
                if ($practicetestcmplts) {
                    $i++;
                } else {
                    $j++;
                }
            }
            $practicetest = !empty($practicetestsql) ? round(($i / $practicetestcount) * 100, 2): 0;

        // Test.
           $totaltests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' $cmadded
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign' $cmadded) as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

                $totalcomppledtedtests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate > 0
                WHERE c.id = :qcourseid AND cmc.userid = :quserid AND m.name = 'quiz' $completedconcat
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate > 0
                WHERE c.id = :acourseid AND cmc.userid = :auserid AND m.name = 'assign' $completedconcat) as a", ['qcourseid' => $courseid, 'quserid' => $userid, 'acourseid' => $courseid, 'auserid' => $userid]);

            $test = $totaltests ? round(($totalcomppledtedtests/$totaltests)*100, 2) : 0;
            

        // Reading.
            $totalpageactivities = $DB->get_field_sql("SELECT count(DISTINCT cm.id)
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {page} p ON p.id = cm.instance 
                WHERE p.pagetype = 0 AND m.name = 'page' AND cm.course = :courseid $cmadded", ['courseid' => $courseid]);

            $completedpageactivities = $DB->get_field_sql("SELECT count(DISTINCT cmc.id)
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                JOIN {modules} m ON m.id = cm.module
                JOIN {page} p ON p.id = cm.instance
                WHERE p.pagetype = 0 AND m.name IN('page') AND cm.deletioninprogress = 0
                AND cmc.completionstate > 0 AND cm.course = :courseid
                AND cmc.userid = :userid $readingconcat", ['courseid' => $courseid, 'userid' => $userid]);

            $reading = ($totalpageactivities != 0) ? round(($completedpageactivities / $totalpageactivities) * 100, 0) : 0;

        // Liveclass.
            $liveclassessql = $DB->get_records_sql("SELECT cm.instance
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {zoom} z ON z.id = cm.instance
                WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                AND m.name = :modulename AND z.start_time <= UNIX_TIMESTAMP() AND cm.course = :courseid AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $zmdstarttime",
                ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'zoom', 'courseid' => $courseid]);

            $liveclasses = !empty($liveclassessql) ? count($liveclassessql) : 0;
            $attendedclasses = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/(SELECT SUM(zmd.end_time-zmd.start_time)
                FROM {zoom_meeting_details} zmd
                JOIN {zoom} zoom ON zoom.id = zmd.zoomid
                WHERE zoom.id = z.id AND zoom.start_time <= UNIX_TIMESTAMP()))*100, 2) as attend
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
                    JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
                    JOIN {zoom_meeting_participants} zmp ON zmp.detailsid = zmd.id
                    WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                    AND m.name = :modulename AND z.start_time <= UNIX_TIMESTAMP() AND cm.course = :courseid AND zmp.userid = :userid AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $zmdstarttime GROUP BY z.id) as s WHERE s.attend > 80", ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'zoom' , 'courseid' => $courseid, 'userid' => $userid]);

            $presentclasses = $DB->get_records_sql("SELECT SUM(t.presentclass) as presentclass, count(t.presentclass) as presentcount FROM (SELECT (CASE WHEN s.attend > 100 THEN (100) ELSE s.attend END) as presentclass FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/(SELECT SUM(zmd.end_time-zmd.start_time)
                FROM {zoom_meeting_details} zmd
                JOIN {zoom} zoom ON zoom.id = zmd.zoomid
                WHERE zoom.id = z.id AND zoom.start_time <= UNIX_TIMESTAMP()))*100, 2) as attend
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {zoom} z ON z.id = cm.instance AND z.course = cm.course
                    JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
                    JOIN {zoom_meeting_participants} zmp ON zmp.detailsid = zmd.id
                    WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
                    AND m.name = :modulename AND z.start_time <= UNIX_TIMESTAMP() AND cm.course = :courseid AND zmp.userid = :userid AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $attendedconcat GROUP BY z.id) as s WHERE s.attend > 80 OR (s.attend BETWEEN 1 AND 80)) as t WHERE 1=1", ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'zoom' , 'courseid' => $courseid, 'userid' => $userid]);//echo '<pre>';print_r($presentclasses);exit;
                if(!empty($presentclasses)) {
                   foreach($presentclasses as $k => $v) {
                        $presentpercentage = $v->presentclass;
                        $presentcount = $v->presentcount;
                    } 
                } else {
                    $presentliveclasses = '';
                    $presentcount = '';
                }
                
            $liveclass = !empty($liveclasses) ? ($attendedclasses .'/'. $liveclasses) : 0;

        // Video.
            $video = 0;

        // Questions asked in forum.
            $forums = $DB->get_records_sql("SELECT f.id
                FROM {forum} f
                JOIN {course_modules} cm ON cm.instance = f.id
                WHERE f.course = :courseid $cmadded", ['courseid' => $courseid]);
            if (!empty($forums)) {
                $forumsarray = [];
                foreach ($forums as $f) {
                    $forumsarray[] = $f->id;
                }
                 list($forumsql, $forumparams) = $DB->get_in_or_equal($forumsarray, SQL_PARAMS_NAMED);
                 $forumparams['userid'] = $userid;
                $askedquestions = $DB->get_field_sql("SELECT count(DISTINCT id)
                    FROM {forum_discussions}
                    WHERE userid = :userid AND forum $forumsql $forumconcat", $forumparams);
            } else {
                $askedquestions = 0;
            }

            $forumquestions = $askedquestions;

        // Live class percentage.
            $liveclasspercentage = !empty($presentcount) ? ROUND(($presentpercentage / $liveclasses),2) : 0;

        // Practice questions.
            $practicequestion = $practicetest;

        // Test.
            $test = $test;

        // Attendance.
            $total = $liveclasses ? $liveclasses : 0;
            $attended = $attendedclasses ? $attendedclasses : 0;

        // Liveclass tab data.
            $fullattended = 0;
            $partiallyattended = 0;
            $missedliveclass = 0;
            $notstarted = 0;
        if (!empty($liveclassessql)) {
            foreach ($liveclassessql as $zoom) {
                $totalsessiontime = $DB->get_field_sql("SELECT SUM(zmd.end_time-zmd.start_time)
                FROM {zoom_meeting_details} zmd
                JOIN {zoom} z ON z.id = zmd.zoomid
                WHERE z.id = :zoomid AND z.start_time <= UNIX_TIMESTAMP() $zmdstarttime", ['zoomid' => $zoom->instance]);

                $attendedsession = $DB->get_field_sql("SELECT SUM(zmp.leave_time - zmp.join_time)
                FROM {zoom_meeting_participants} zmp
                JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
                JOIN {zoom} z ON z.id = zmd.zoomid
                WHERE zmp.userid = :userid AND z.start_time <= UNIX_TIMESTAMP() AND z.id = :zoomid $attendedconcat
                GROUP BY z.id ", ['userid' => $userid, 'zoomid' => $zoom->instance]);
                if($attendedsession > $totalsessiontime) {
                    $attendedsession = $totalsessiontime;
                }
                if ($totalsessiontime != 0 && $attendedsession != '') {
                    $sessionpercentage = $totalsessiontime ? round(($attendedsession / $totalsessiontime) * 100, 2) : 0;
                    if ($sessionpercentage > 80) {
                        $fullattended++;
                    } else if($sessionpercentage > 1 && $sessionpercentage <= 80){
                        $partiallyattended++;
                    }
                } else {
                    $missedliveclass++;
                }
            }
        }

        $fullattended = $fullattended;
        $partiallyattended = $partiallyattended;
        $missedliveclass = $missedliveclass;

        // Reading tab data.
        $timespent = $DB->get_field_sql("SELECT SUM(mt.timespent)
                            FROM {block_ls_modtimestats} mt
                            JOIN {course_modules} cm ON cm.id = mt.activityid
                            JOIN {modules} m ON m.id = cm.module
                            JOIN {page} p ON p.id = cm.instance
                            WHERE p.pagetype = 0 AND m.name = 'page' AND mt.userid = :userid
                            AND cm.course = :courseid $readingtimeconcat", ['userid' => $userid, 'courseid' => $courseid]);

        $readingtimespent = !empty($timespent) ? (new ls)->strtime($timespent) : 0;
        $readingcompleted = $reading;

        // Practice questions tab data
        $quizesslist = $DB->get_records_sql("SELECT DISTINCT q.id, cm.id as instanceid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
        JOIN {quiz} q ON q.id = cm.instance
        JOIN {context} ctx ON ctx.instanceid = q.course AND ctx.contextlevel = 50
        WHERE 1 = 1 AND cm.visible = :cmvisible AND cm.deletioninprogress = :deletioninprogress
        AND m.name = :modulename
        AND q.testtype = :testtype AND q.course = :courseid  $quizopen ORDER BY id DESC",
        ['cmvisible' => 1, 'deletioninprogress' => 0, 'modulename' => 'quiz',
        'testtype' => 1, 'courseid' => $courseid]);
        $attemptedperce = [];
        $answeredperce = [];
        $correctper = [];
        $wrongperc = [];
        $correct = 0;
        $wrong = 0;
        $attempted = 0;
        $answered = 0;
        if(!empty($quizesslist)) {
            $totalquizes = 0;
            list($quizsql, $quizparams) = $DB->get_in_or_equal(array_keys($quizesslist), SQL_PARAMS_NAMED);
            foreach ($quizesslist as $q) {
                $questions = $DB->get_field_sql("SELECT count(DISTINCT id)
                            FROM {quiz_slots} 
                            WHERE quizid = :quizid", ['quizid' => $q->id]);
                if($questions) {
                    $totalquizes++;
                }
                $quizattempt = $DB->get_field_sql("SELECT id FROM {quiz_attempts} WHERE quiz = :quizid AND state = :state AND userid = :userid ORDER BY id DESC LIMIT 1", ['quizid' => $q->id, 'state' => 'finished', 'userid' => $userid]);
                $correct = 0;
                $attempted = 0;
                $wrong = 0;
                $answered = 0;
                if($quizattempt) {
                   $questionsattempt = $DB->get_records_sql("SELECT DISTINCT qat.questionid, qat.rightanswer, qat.responsesummary
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {quiz_attempts} qatp ON qatp.uniqueid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qas.userid = :userid AND qatp.id = :quizattempt AND qu.component = :component", ['userid' => $userid, 'component' => 'mod_quiz', 'quizattempt' => $quizattempt]);
                   $questionsanswered = $DB->get_records_sql("SELECT DISTINCT qat.questionid, qat.rightanswer, qat.responsesummary
                        FROM {question_attempts} qat
                        JOIN {question_attempt_steps} qas ON qas.questionattemptid = qat.id
                        JOIN {question_usages} qu ON qat.questionusageid = qu.id
                        JOIN {quiz_attempts} qatp ON qatp.uniqueid = qu.id
                        JOIN {context} ctx ON ctx.id = qu.contextid AND ctx.contextlevel =70
                        WHERE qat.responsesummary IS NOT NULL AND qas.userid = :userid AND qatp.id = :quizattempt AND qu.component = :component", ['userid' => $userid, 'component' => 'mod_quiz', 'quizattempt' => $quizattempt]);
                    if($questionsattempt) {
                        $attemptedperce[] = $questions ? (count($questionsattempt)/$questions)*100 : 0;
                    }
                    if($questionsanswered) {
                        $answeredperce[] = $questionsattempt ? (count($questionsanswered)/count($questionsattempt))*100 : 0;
                        if(!empty($questionsanswered)) {
                            foreach ($questionsanswered as $key => $value) {
                                if($value->responsesummary != 'NULL'){
                                    if($value->rightanswer == $value->responsesummary) {
                                        $correct++;
                                    } else {
                                        $wrong++;
                                    }
                                }
                            
                            }
                        }
                    }
                }
                $correctper[] = ($questionsanswered) ? round((($correct/count($questionsanswered))*100), 2) : 0;
                $wrongperc[] = ($questionsanswered) ? round((($wrong/count($questionsanswered))*100), 2) : 0;
            }
            $attempted =  ($totalquizes != 0) ? round((array_sum($attemptedperce)/$totalquizes), 2) : 0;
            $answered =  ($totalquizes != 0) ? round((array_sum($answeredperce)/$totalquizes), 2) : 0;
            $correct =  ($totalquizes != 0) ? round((array_sum($correctper)/$totalquizes), 2) : 0;
            $wrong =  ($totalquizes != 0) ? round((array_sum($wrongperc)/$totalquizes), 2) : 0;
        }
        
        

        // Test score tab data.
        $activirtparams['cmvisible1'] = 1;
        $activirtparams['modulename1'] = 'quiz';
        $activirtparams['testtype1'] = 0;
        $activirtparams['courseid1'] = $courseid;
        $activirtparams['cmvisible'] = 1;
        $activirtparams['modulename'] = 'assign';
        $activirtparams['courseid'] = $courseid;
        $testscores = $DB->get_records_sql("SELECT cm.id as instanceid, m.name as modulename, cm.instance as activityid, q.timeopen as startdate, q.timeclose as duedate, q.name
                FROM {course_modules} cm
                JOIN {quiz} q ON cm.instance = q.id
                JOIN {modules} m ON cm.module = m.id AND m.name = :modulename1
                WHERE 1 = 1 AND cm.visible = :cmvisible1
                AND q.testtype = :testtype1 AND q.course = :courseid1 $quizopen
                UNION
                SELECT cm.id as instanceid, m.name as modulename, cm.instance as activityid, ass.allowsubmissionsfromdate as startdate, ass.duedate as duedate, ass.name
                FROM {course_modules} cm
                JOIN {assign} ass ON cm.instance = ass.id
                JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                WHERE 1 = 1 AND cm.visible = :cmvisible AND ass.course = :courseid $assopen", $activirtparams);
        
        // Avaiable tests.
        $availabletests = count($testscores);

        // Submitted.
        $submitted = 0;
        $missed = 0;
	   $notstarted = 0;
        if (!empty($testscores)) {
            foreach ($testscores as $key => $value) {
                if ($value->modulename == 'assign') {
                    $submission = $DB->get_field_sql("SELECT id
                        FROM {assign_submission}
                        WHERE assignment = :activityid AND status = 'submitted'
                        AND userid = :userid $assignconcat", ['userid' => $userid, 'activityid' => $value->activityid]);
                    if($submission){
                        $submitted++;
                    } else {
                        $date = new DateTime();
                        $timestamp = $date->getTimestamp();
                        if($timestamp > $value->duedate) {
                            $missed++;
                        } else {
                            $notstarted++;
                        }
                    }
                } else {
                    $submitteddate =  $DB->get_field_sql("SELECT id
                        FROM {quiz_attempts} qa
                        WHERE qa.quiz = :quizid
                        AND qa.userid=:userid AND qa.state = :state $quizconcat", ['userid' => $userid,'quizid' => $value->activityid, 'state' => 'finished']);
                    if($submitteddate) {
                        $submitted++;
                    } else {
                        $date = new DateTime();
                        $timestamp = $date->getTimestamp();
                        if(!empty($value->duedate)) {
                            if($timestamp > $value->duedate) {
                            $missed++;
                            } else {
                                $notstarted++;
                            }
                        } else {
                            $notstarted++;
                        }
                        
                     }
                }
            }
        }
        // Submitted.
        $submitted = $submitted;

        // Missed.
        $missed = $missed;

        return ['courseid' => $courseid,
                'coursepercentage' => $coursepercentage,
                'avgtestscore' => $avgtestscore,
                'practicetest' => $attempted,
                'reading' => $reading,
                'liveclass' => $liveclass,
                'video' => $video,
                'forumquestions' => $forumquestions,
                'liveclasspercentage' => $liveclasspercentage,
                'practicequestion' => $attempted,
                'test' => $test,
                'total' => $total,
                'attended' => $attended,
                'fullattended' => $fullattended,
                'partiallyattended' => $partiallyattended,
                'missedliveclass' => $missedliveclass,
                'readingcompleted' => $readingcompleted,
                'timespent' => $readingtimespent,
                'attempted' => $attempted,
                'answered' => $answered,
                'correct' => $correct,
                'wrong' => $wrong,
                'availabletests' => $availabletests,
                'submitted' => $submitted,
                'missed' => $missed,
		 'chapterwisereportid' => $chapterwisereportid,
                'chapterwisereportinstance' => $chapterwisereportinstance,
                'chapterwisereporttype' => $chapterwisereporttype,
                'liveclassreportid' => $liveclassreportid,
                'liveclassreportinstance' => $liveclassreportinstance,
                'liveclassreporttype' => $liveclassreporttype,
                'readingreportid' => $readingreportid,
                'readingreportinstance' => $readingreportinstance,
                'readingreporttype' => $readingreporttype,
                'testscorereportid' => $testscorereportid,
                'testscorereportinstance' => $testscorereportinstance,
                'testscorereporttype' => $testscorereporttype,
                'practicequestionsreportid' => $practicequestionsreportid,
                'practicequestionsinstance' => $practicequestionsinstance,
                'practicequestionstype' => $practicequestionstype];
    }

    public function get_activitiesdata($courseid = false) {
        global $DB;

        $totalstudentslist = $DB->get_records_sql("SELECT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        WHERE c.visible = 1 AND c.id = :courseid", ['courseid' => $courseid]);
        foreach ($totalstudentslist as $student) {
            $studentslist[] = $student->userid;
        }

        // Total live classes.
        $totalliveclasses = $DB->get_field_sql("SELECT COUNT(z.id)
                            FROM {zoom} z
                            WHERE z.course = :courseid AND z.start_time <= UNIX_TIMESTAMP()", ['courseid' => $courseid]);

        // Time spend student percentage in class.

        if (!empty($totalstudentslist)) {
            list($asql, $params1) = $DB->get_in_or_equal($studentslist, SQL_PARAMS_NAMED);
        } else {
            $asql = " = 0";
        }
        $params1['courseid'] = $courseid;
        $timespent = $DB->get_field_sql("SELECT AVG(a.duration) FROM
                        (SELECT SUM(zmp.duration) AS duration
                        FROM {zoom_meeting_details} zmd
                        JOIN {zoom_meeting_participants} zmp ON zmd.id = zmp.detailsid
                        JOIN {course_modules} cm ON cm.instance = zmd.zoomid
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
                        JOIN {zoom} z ON z.id = cm.instance
                        WHERE 1 = 1 AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid $asql
                        GROUP BY zmp.userid, z.id) AS a", $params1);
        $zoomtimespend = !empty($timespent) ? (new ls)->strTime($timespent) : 0;

        // Page timespend.
        $pagetimespend = $DB->get_field_sql("SELECT AVG(blm.timespent)
                            FROM {block_ls_modtimestats} blm
                            JOIN {course_modules} cm ON cm.instance = blm.instanceid
                            JOIN {modules} m ON m.id = cm.module
                            JOIN {page} p ON p.id = cm.instance
                            WHERE p.pagetype = 0 AND m.name = 'page' AND cm.course = :courseid AND blm.userid $asql", $params1);
        $pagetime = !empty($pagetimespend) ? (new ls)->strTime($pagetimespend) : 0;

        // Page average completion.
        $totalpages = $DB->get_field_sql("SELECT COUNT(p.id)
                        FROM {page} p
                        WHERE p.pagetype = 0 AND p.course = $courseid", ['courseid' => $courseid]);
        $totalpageactivities = !empty($totalstudentslist) ? ($totalpages*count($totalstudentslist)) : 0;

        $totalpagecmpusers = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.userid)
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                        JOIN {course_modules} cm ON cm.course = c.id AND cm.deletioninprogress = 0
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
                        JOIN {page} p ON p.id = cm.instance
                        JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                        WHERE 1=1 AND p.pagetype = 0 AND cmc.completionstate > 0
                        AND c.id = :courseid", ['courseid' => $courseid]);

        $avgpagecompletion = !empty($totalpageactivities) ? round(($totalpagecmpusers/$totalpageactivities) *100, 2) : 0;
        // Unread page count.
        $unreadpagecount = $DB->get_field_sql("SELECT COUNT(p.id)
                            FROM {page} p
                            JOIN {course_modules} cm ON p.id = cm.instance
                            JOIN {modules} m ON m.id = cm.module
                            WHERE 1 = 1 AND p.pagetype = 0 AND m.name = 'page' AND cm.id NOT IN (SELECT page
                                            FROM {block_ls_pageviewed}
                                            WHERE 1 = 1
                                            GROUP BY page) AND p.id NOT IN(SELECT cm.instance FROM {course_modules} cm 
                                                JOIN {modules} m ON m.id = cm.module
                                                JOIN {page} p ON p.id = cm.instance 
                                                JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id 
                                                WHERE 1=1 AND p.pagetype = 0 AND m.name = 'page')
                            AND p.course = :courseid", ['courseid' => $courseid]);

        // Practice test count.
        $totalpracticetests = $DB->get_field_sql("SELECT COUNT(q.id)
                                FROM {quiz} q
                                WHERE 1 = 1 AND q.testtype = 1
                                AND q.course = :courseid", ['courseid' => $courseid]);

        // Unattempted practice tests count.
        $unattemptedtestcount = $DB->get_field_sql("SELECT COUNT(q.id)
                            FROM {quiz} q
                            WHERE 1 = 1 AND q.testtype = 1
                            AND q.id NOT IN (SELECT quiz
                                    FROM {quiz_attempts} WHERE 1 = 1 GROUP BY quiz)
                            AND q.course = :courseid", ['courseid' => $courseid]);

        // Average test scores percentage.
        $testscoressql = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
                    FROM (SELECT gg.userid as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :qcourseid AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL GROUP BY gg.userid
                    UNION SELECT gg.userid as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {assign} a ON a.id = cm.instance
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :acourseid AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL GROUP BY gg.userid) as a",
                    ['qcourseid' => $courseid, 'acourseid' => $courseid]);
        $avgtestscore = !empty($testscoressql) ? round($testscoressql).'%' : '0%';

        // Tests count.
        /*$totaltestscores = $DB->get_field_sql("SELECT COUNT(cm.id)
                            FROM {course_modules} cm
                            LEFT JOIN {quiz} q ON q.id = cm.instance
                            LEFT JOIN {assign} a ON a.id = cm.instance
                            JOIN {modules} m ON m.id = cm.module
                            WHERE cm.course = :courseid AND m.name IN ('assign', 'quiz')", ['courseid' => $courseid]);*/

        $totaltestscores = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' 
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign') as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

        // Unattempted tests count.
        $testscores = $DB->get_field_sql("SELECT COUNT(id) FROM {quiz} WHERE ((id NOT IN (SELECT DISTINCT quiz FROM {quiz_attempts}))
        OR(id IN (SELECT DISTINCT quiz FROM {quiz_attempts} WHERE state = 'inprogress'))) AND course = :courseid AND testtype = 0",
        ['courseid' => $courseid]);
        $assignments = $DB->get_field_sql("SELECT COUNT(id)
                            FROM {assign}  WHERE (id NOT IN (SELECT DISTINCT assignment FROM {assign_submission})) AND course = :courseid",
                                ['courseid' => $courseid]);
        $unattemptedtscount = $testscores + $assignments;

        // Expired tests.
        /*$expiredtests = $DB->get_field_sql("SELECT COUNT(cm.id)
                        FROM {course_modules} cm
                        LEFT JOIN {quiz} q ON q.id = cm.instance
                        LEFT JOIN {assign} a ON a.id = cm.instance
                        JOIN {modules} m ON m.id = cm.module
                        WHERE cm.course = :courseid AND
                        (
                            (q.testtype = 0 AND m.name = 'quiz' AND (q.timeclose < UNIX_TIMESTAMP())) OR
                            (m.name = 'assign' AND (a.duedate < UNIX_TIMESTAMP()))
                        )", ['courseid' => $courseid]);*/
        $expiredtests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' AND q.timeclose !=0 AND (q.timeclose < UNIX_TIMESTAMP())
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign' AND (a.duedate < UNIX_TIMESTAMP())) as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

        // Active tests.
        /*$activetests = $DB->get_field_sql("SELECT COUNT(cm.id)
                        FROM {course_modules} cm
                        LEFT JOIN {quiz} q ON q.id = cm.instance
                        LEFT JOIN {assign} a ON a.id = cm.instance
                        JOIN {modules} m ON m.id = cm.module
                        WHERE cm.course = :courseid AND
                        (
                            (q.testtype = 0 AND m.name = 'quiz' AND (q.timeclose >= UNIX_TIMESTAMP() OR q.timeclose = 0)) OR
                            (m.name = 'assign' AND (a.duedate >= UNIX_TIMESTAMP() OR a.duedate = 0))
                        )", ['courseid' => $courseid]);*/
        $activetests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' AND (q.timeclose >= UNIX_TIMESTAMP() OR q.timeclose = 0)
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign' AND (a.duedate >= UNIX_TIMESTAMP() OR a.duedate = 0)) as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);


        return ['totalliveclasses' => $totalliveclasses,
                'zoomtimespend' => $zoomtimespend,
                'pagetime' => $pagetime,
                'avgpagecompletion' => $avgpagecompletion ? $avgpagecompletion : 0,
                'unreadpagecount' => $unreadpagecount,
                'totalpracticetests' => $totalpracticetests,
                'unattemptedtestcount' => $unattemptedtestcount,
                'avgtestscore' => $avgtestscore,
                'totaltestscores' => $totaltestscores,
                'unattemptedtscount' => $unattemptedtscount,
                'expiredtests' => $expiredtests,
                'activetests' => $activetests,
                ];
    }

    public function studentsdetailsview($courseid = false) {
        $studentsdetails = $this->get_studentsdetails($courseid);
        $adminreportsdetails = $this->get_studnetdetailsreport($courseid);
       /*$adminreportsdetails = $this->get_adminreportsdata($courseid);*/
        /*$activitiesdata =  $this->get_activitiesdata($courseid)*/
        return array_merge($studentsdetails, $adminreportsdetails);
    }

    public function package_list() {
        global $DB;

        $packages = $DB->get_records_sql("SELECT id, name
                    FROM {local_hierarchy}
                    WHERE 1=1 AND depth = :depth",
                    ['depth' => 4]);

        $packageoptions = array();
        if (!empty($packages)) {
            foreach ($packages as $p) {
                $packageoptions[] = ['packageid' => $p->id, 'packagename' => format_string($p->name)];
            }
        }
        return $packages;
    }

    public function get_courses($packageid, $userid) {
        global $DB;
        $enrolcourses = $DB->get_records_sql("SELECT c.id
                        FROM {course} c
                       JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                       JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
                       WHERE ue.userid = :userid", ['userid' => $userid]);
        if($enrolcourses) {
            $courseseslist = array_keys($enrolcourses);
            list($coursessql, $packagesparams) = $DB->get_in_or_equal($courseseslist, SQL_PARAMS_NAMED);
            $packagesparams['packageid'] = $packageid;
            $packagesparams['visible'] = 1;
            $courses = $DB->get_records_sql("SELECT c.id, c.fullname
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
                $courseoptions[] = ['courseid' => $c->id, 'coursename' => format_string($c->fullname), 'selected' => $active];
                $count++;
            }
        }
        return $courseoptions;
    }


    public function get_reportsdata() {
        global $DB;

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

        return ['chapterwisereport' => $chapterwisereport,
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
                                'practicequestionstype' => $practicequestionstype,];
    }

    public function get_packages($packageid, $userid) {
        global $DB;

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
        return $packageoptions;
    }

    public function get_studnetdetailsreport($courseid = false) {
        global $DB, $CFG;
        // Studentwise report.
        $studentwisechapters = $DB->get_record('block_learnerscript', ['type' => 'studentwisechapters']);
        $studentwisechapterreportid = $studentwisechapters->id;
        $studentwisechapterinstance = $studentwisechapters->id;
        $studentwisechapterstype = 'table';

         // Chapterdetails report.
        $chapterdetails = $DB->get_record('block_learnerscript', ['type' => 'chapterdetails']);
        $chapterdetailsreportid = $chapterdetails->id;
        $chapterdetailsinstance = $chapterdetails->id;
        $chapterdetailstype = 'table';

        return ['studentwisechapterreportid' => $studentwisechapterreportid,
            'studentwisechapterinstance' => $studentwisechapterinstance,
            'studentwisechapterstype' => $studentwisechapterstype,
            'chapterdetailsreportid' => $chapterdetailsreportid,
            'chapterdetailsinstance' => $chapterdetailsinstance,
            'chapterdetailstype' => $chapterdetailstype,
            'courseid' => $courseid
        ];
    }

    public function liveclassdetailsview($courseid = false) {
        global $DB;
        // Liveclass report.
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

        // Total live classes.
        $totalliveclasses = $DB->get_field_sql("SELECT COUNT(z.id)
                            FROM {zoom} z
                            WHERE z.course = :courseid AND z.start_time <= UNIX_TIMESTAMP()", ['courseid' => $courseid]);

        // Time spend student percentage in class.
        $totalstudentslist = $DB->get_records_sql("SELECT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        WHERE c.visible = 1 AND c.id = :courseid", ['courseid' => $courseid]);
        foreach ($totalstudentslist as $student) {
            $studentslist[] = $student->userid;
        }

        if (!empty($totalstudentslist)) {
            list($asql, $params1) = $DB->get_in_or_equal($studentslist, SQL_PARAMS_NAMED);
        } else {
            $asql = " = 0";
        }
        $params1['courseid'] = $courseid;
        $timespent = $DB->get_field_sql("SELECT AVG(a.duration) FROM
                        (SELECT SUM(zmp.duration) AS duration
                        FROM {zoom_meeting_details} zmd
                        JOIN {zoom_meeting_participants} zmp ON zmd.id = zmp.detailsid
                        JOIN {course_modules} cm ON cm.instance = zmd.zoomid
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
                        JOIN {zoom} z ON z.id = cm.instance
                        WHERE 1 = 1 AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid $asql
                        GROUP BY zmp.userid, z.id) AS a", $params1);
        $zoomtimespend = !empty($timespent) ? (new ls)->strTime($timespent) : 0;

        return ['liveclassreportid' => $liveclassreportid,
            'liveclassinstance' => $liveclassinstance,
            'liveclasstype' => $liveclasstype,
            'liveclassattendanceid' => $liveclassattendanceid,
            'liveclassattendanceinstance'   => $liveclassattendanceinstance,
            'liveclassattendancetype'   => $liveclassattendancetype,
            'totalliveclasses' => $totalliveclasses,
            'zoomtimespend' => $zoomtimespend,
            'courseid' => $courseid
        ];
    }

    public function readingdetailsview($courseid = false) {
        global $DB, $CFG;
        // Reading report.
        $readingdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Reading']);
        $readingcontenttypes = (new ls)->cr_listof_reporttypes($readingdetails->id);
        $readingreportid = $readingdetails->id;
        $readingtype = key($readingcontenttypes);

        $totalstudentslist = $DB->get_records_sql("SELECT ue.userid
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        WHERE c.visible = 1 AND c.id = :courseid", ['courseid' => $courseid]);
        foreach ($totalstudentslist as $student) {
            $studentslist[] = $student->userid;
        }

        // Total live classes.
        $totalliveclasses = $DB->get_field_sql("SELECT COUNT(z.id)
                            FROM {zoom} z
                            WHERE z.course = :courseid AND z.start_time <= UNIX_TIMESTAMP()", ['courseid' => $courseid]);

        // Time spend student percentage in class.

        if (!empty($totalstudentslist)) {
            list($asql, $params1) = $DB->get_in_or_equal($studentslist, SQL_PARAMS_NAMED);
        } else {
            $asql = " = 0";
        }
        $params1['courseid'] = $courseid;
        // Page timespend.
        $pagetimespend = $DB->get_field_sql("SELECT AVG(blm.timespent)
                            FROM {block_ls_modtimestats} blm
                            JOIN {course_modules} cm ON cm.instance = blm.instanceid
                            JOIN {modules} m ON m.id = cm.module
                            JOIN {page} p ON p.id = cm.instance
                            WHERE p.pagetype = 0 AND m.name = 'page' AND cm.course = :courseid AND blm.userid $asql", $params1);
        $pagetime = !empty($pagetimespend) ? (new ls)->strTime($pagetimespend) : 0;

        // Page average completion.
        $totalpages = $DB->get_field_sql("SELECT COUNT(p.id)
                        FROM {page} p
                        WHERE p.pagetype = 0 AND p.course = $courseid", ['courseid' => $courseid]);
        $totalpageactivities = !empty($totalstudentslist) ? ($totalpages*count($totalstudentslist)) : 0;

        $totalpagecmpusers = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.userid)
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {course} c ON e.courseid = c.id AND e.status = 0
                        JOIN {role_assignments}  ra ON ra.userid = ue.userid
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                        JOIN {context} ctx ON ctx.instanceid = c.id
                        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                        AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                        JOIN {course_modules} cm ON cm.course = c.id AND cm.deletioninprogress = 0
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
                        JOIN {page} p ON p.id = cm.instance
                        JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                        WHERE 1=1 AND p.pagetype = 0 AND cmc.completionstate > 0
                        AND c.id = :courseid", ['courseid' => $courseid]);

        $avgpagecompletion = !empty($totalpageactivities) ? round(($totalpagecmpusers/$totalpageactivities) *100, 2) : 0;
        // Unread page count.
        $unreadpagecount = $DB->get_field_sql("SELECT COUNT(p.id)
                            FROM {page} p
                            JOIN {course_modules} cm ON p.id = cm.instance
                            JOIN {modules} m ON m.id = cm.module
                            WHERE 1 = 1 AND p.pagetype = 0 AND m.name = 'page' AND cm.id NOT IN (SELECT page
                                            FROM {block_ls_pageviewed}
                                            WHERE 1 = 1
                                            GROUP BY page) AND p.id NOT IN(SELECT cm.instance FROM {course_modules} cm 
                                                JOIN {modules} m ON m.id = cm.module
                                                JOIN {page} p ON p.id = cm.instance 
                                                JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id 
                                                WHERE 1=1 AND p.pagetype = 0 AND m.name = 'page')
                            AND p.course = :courseid", ['courseid' => $courseid]);

        return ['readingreportid' => $readingreportid,
            'readingtype' => $readingtype,
            'pagetime' => $pagetime,
            'avgpagecompletion' => $avgpagecompletion ? $avgpagecompletion : 0,
            'unreadpagecount' => $unreadpagecount,
            'courseid' => $courseid
        ];
    }

    public function practicetestdetailsview($courseid = false) {
        global $DB, $CFG;
        // Practicetest report.
        $practicetestdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Practice Test']);
        $practicetestcontenttypes = (new ls)->cr_listof_reporttypes($practicetestdetails->id);
        $practicetestreportid = $practicetestdetails->id;
        $practicetesttype = key($practicetestcontenttypes);

        // Practice test count.
        $totalpracticetests = $DB->get_field_sql("SELECT COUNT(q.id)
                                FROM {quiz} q
                                WHERE 1 = 1 AND q.testtype = 1
                                AND q.course = :courseid", ['courseid' => $courseid]);

        // Unattempted practice tests count.
        $unattemptedtestcount = $DB->get_field_sql("SELECT COUNT(q.id)
                            FROM {quiz} q
                            WHERE 1 = 1 AND q.testtype = 1
                            AND q.id NOT IN (SELECT quiz
                                    FROM {quiz_attempts} WHERE 1 = 1 GROUP BY quiz)
                            AND q.course = :courseid", ['courseid' => $courseid]);

        return ['practicetestreportid' => $practicetestreportid,
            'practicetesttype' => $practicetesttype,
            'totalpracticetests' => $totalpracticetests,
            'unattemptedtestcount' => $unattemptedtestcount,
            'courseid' => $courseid
        ];
    }

    public function testscoredetailsview($courseid = false) {
        global $DB, $CFG;
        // Testscore report.
        $testscoredetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Test Score']);
        $testscorecontenttypes = (new ls)->cr_listof_reporttypes($testscoredetails->id);
        $testscorereportid = $testscoredetails->id;
        $testscoretype = key($testscorecontenttypes);

        $testscorelinedetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Test scores']);
        $testscorelinecontenttypes = (new ls)->cr_listof_reporttypes($testscorelinedetails->id);
        $testscorelinereportid = $testscorelinedetails->id;
        $testscorelinetype = key($testscorelinecontenttypes);

        $testscoressql = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
                    FROM (SELECT gg.userid as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :qcourseid AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL GROUP BY gg.userid
                    UNION SELECT gg.userid as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                    FROM {grade_grades} AS gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course} c ON c.id = cm.course
                    JOIN {assign} a ON a.id = cm.instance
                    JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                    WHERE c.id = :acourseid AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL GROUP BY gg.userid) as a",
                    ['qcourseid' => $courseid, 'acourseid' => $courseid]);
        $avgtestscore = !empty($testscoressql) ? round($testscoressql).'%' : '0%';

        $totaltestscores = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' 
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign') as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

        // Unattempted tests count.
        $testscores = $DB->get_field_sql("SELECT COUNT(id) FROM {quiz} WHERE ((id NOT IN (SELECT DISTINCT quiz FROM {quiz_attempts}))
        OR(id IN (SELECT DISTINCT quiz FROM {quiz_attempts} WHERE state = 'inprogress'))) AND course = :courseid AND testtype = 0",
        ['courseid' => $courseid]);
        $assignments = $DB->get_field_sql("SELECT COUNT(id)
                            FROM {assign}  WHERE (id NOT IN (SELECT DISTINCT assignment FROM {assign_submission})) AND course = :courseid",
                                ['courseid' => $courseid]);
        $unattemptedtscount = $testscores + $assignments;

        $expiredtests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' AND q.timeclose !=0 AND (q.timeclose < UNIX_TIMESTAMP())
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign' AND (a.duedate < UNIX_TIMESTAMP())) as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

        $activetests = $DB->get_field_sql("SELECT count(a.id)
                FROM (SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE c.id = :qcourseid AND m.name = 'quiz' AND (q.timeclose >= UNIX_TIMESTAMP() OR q.timeclose = 0)
                UNION SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE c.id = :acourseid AND m.name = 'assign' AND (a.duedate >= UNIX_TIMESTAMP() OR a.duedate = 0)) as a", ['qcourseid' => $courseid, 'acourseid' => $courseid]);

        return ['testscorereportid' => $testscorereportid,
            'testscoretype' => $testscoretype,
            'testscorelinereportid' => $testscorelinereportid,
            'testscorelinetype' => $testscorelinetype,
            'avgtestscore' => $avgtestscore,
            'totaltestscores' => $totaltestscores,
            'unattemptedtscount' => $unattemptedtscount,
            'expiredtests' => $expiredtests,
            'activetests' => $activetests,
            'courseid' => $courseid
        ];
    }

    public function forumdetailsview($courseid = false) {
        global $DB, $CFG;
        // Forum report.
        $forumdetails = $DB->get_record('block_learnerscript', ['type' => 'sql', 'name' => 'Forum']);
        $forumcontenttypes = (new ls)->cr_listof_reporttypes($forumdetails->id);
        $forumreportid = $forumdetails->id;
        $forumtype = key($forumcontenttypes);

        return ['forumreportid' => $forumreportid,
            'forumtype' => $forumtype,
            'courseid' => $courseid
        ];
    }

    public function studentchapterdetailsview($courseid = false) {
        global $DB, $CFG;
        // Chapterdetails report.
        $chapterdetails = $DB->get_record('block_learnerscript', ['type' => 'chapterdetails']);
        $chapterdetailsreportid = $chapterdetails->id;
        $chapterdetailsinstance = $chapterdetails->id;
        $chapterdetailstype = 'table';

        return ['chapterdetailsreportid' => $chapterdetailsreportid,
            'chapterdetailsinstance' => $chapterdetailsinstance,
            'chapterdetailstype' => $chapterdetailstype,
            'courseid' => $courseid
        ];
    }
    public function get_progressbar_color($percentage) {

        if ($percentage > 0 && $percentage <= 15) {
            $color = '#FF0000';
        } else if($percentage > 15 && $percentage <= 70) {
            $color = '#F6C167';
        } else {
            $color = '#008000';
        }
        return $color;
    }

}
