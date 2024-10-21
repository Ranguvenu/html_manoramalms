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

/** LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage learnerscript
 * @author: Sudharani
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use context_system;
use stdClass;
use html_writer;

class report_liveclassdetails extends reportbase implements report {

    private $relatedctxsql;

    /**
     * [__construct description]
     * @param [type] $report           [description]
     * @param [type] $reportproperties [description]
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report, $reportproperties);
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->columns = array("liveclassdetailscolumns" => array('scheduleddate', 'scheduledtime', 'studentname', 'course', 'zoom', 'chapter', 'topic', 'activity', 'percentage', 'status'));
        $this->filters = ['users', 'courses', 'status'];
        $this->parent = false;
        $this->courselevel = true;
        $this->orderable = array();
        $this->defaultcolumn = 'a.id';
    }
    function init() {
        global $DB;
    }
    function count() {
       global $DB;
        $filter = '';
        $status = '';
        $searchfilter = '';
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filter .= " AND u.id = ".$this->params['filter_users'];
        }
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $filter .= " AND c.id = ".$this->params['filter_courses'];
        }
        if (isset($this->params['filter_startdate']) && !empty($this->params['filter_startdate']) && ($this->params['filter_startdate'] != 0)) {
            $startdate = $this->params['filter_startdate'];
            $filter .= " AND main.start_time >= $startdate ";
        }
        if (isset($this->params['filter_duedate']) && !empty($this->params['filter_duedate']) && ($this->params['filter_duedate'] != 0)) {
            $duedate = $this->params['filter_duedate'];
            $filter .= " AND main.start_time <= $duedate";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 1) {
            $status .= " AND ((a.attended = 0) OR (a.attended = '') OR (a.attended IS NULL)) ";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 2) {
            $status .= " AND a.attended <= a.totalsession AND ROUND((a.attended/a.totalsession)*100, 2) <= 80 AND ROUND((a.attended/a.totalsession)*100, 2) >= 1";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 3) {
             $status .= " AND a.attended <= a.totalsession AND ROUND((a.attended/a.totalsession)*100, 2) > 80";
        }
        if (!empty($this->params['filter_chapters']) && $this->params['filter_chapters'] > 0) {
            $filter .= " AND cf.id = ".$this->params['filter_chapters'];
        }
        if (isset($this->search) && $this->search) {
            $params['queryparam'] = "%$this->search%";
            $searchfilter .= " AND a.sectionname LIKE :queryparam";
            
        }
        $activities = $DB->get_records_sql("SELECT a.* FROM(SELECT DISTINCT main.id, cs.name as sectionname, (SELECT SUM(zmp.leave_time - zmp.join_time)
            FROM {zoom_meeting_participants} zmp
            JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
            JOIN {zoom} zz ON zz.id = zmd.zoomid
            WHERE zz.id = main.id AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid = u.id GROUP BY zmp.userid) as attended,(SELECT SUM(zmd.end_time-zmd.start_time)
                    FROM {zoom_meeting_details} zmd
                    JOIN {zoom} z ON z.id = zmd.zoomid
                    WHERE z.id = main.id) as totalsession
            FROM {zoom} as main
            JOIN {course_modules} cm ON cm.instance = main.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'zoom'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
        WHERE ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 AND main.start_time <= UNIX_TIMESTAMP() AND cm.deletioninprogress = 0 AND main.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $filter) as a WHERE 1=1 $status $searchfilter GROUP BY a.id ", $params);
        $totalactivities = count($activities);
        $this->sql = "SELECT $totalactivities AS totalactivities ";
    }

    function select() {
       global $DB;
        $filterdata = '';
        $status = '';
        $searchfilterdata = '';
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filterdata .= " AND u.id = ".$this->params['filter_users'];
        }
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $filterdata .= " AND c.id = ".$this->params['filter_courses'];
        }
        if (isset($this->params['filter_startdate']) && !empty($this->params['filter_startdate']) && ($this->params['filter_startdate'] != 0)) {
            $startdate = $this->params['filter_startdate'];
            $filterdata .= " AND main.start_time >= $startdate ";
        }
        if (isset($this->params['filter_duedate']) && !empty($this->params['filter_duedate']) && ($this->params['filter_duedate'] != 0)) {
            $duedate = $this->params['filter_duedate'];
            $filterdata .= " AND main.start_time <= $duedate ";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 1) {
            $statusfilter .= " AND ((a.attended = 0) OR (a.attended = '') OR (a.attended IS NULL)) ";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 2) {
            $statusfilter .= " AND a.attended <= a.totalsession AND ROUND((a.attended/a.totalsession)*100, 2) <= 80 AND ROUND((a.attended/a.totalsession)*100, 2) >= 1";
        }
        if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 3) {
            $statusfilter .= " AND a.attended <= a.totalsession AND ROUND((a.attended/a.totalsession)*100, 2) > 80";
        }
        if (!empty($this->params['filter_chapters']) && $this->params['filter_chapters'] > 0) {
            $filterdata .= " AND cf.id = ".$this->params['filter_chapters'];
        }
        if (isset($this->search) && $this->search) {
            $this->params['queryparam1'] = "%$this->search%";
            $searchfilterdata .= " AND a.sectionname LIKE :queryparam1";
            
        }
        $this->sql = "SELECT a.id, a.activityname, a.sectionid, a.courseid, a.studentname, a.course, a.scheduleddate, a.created, a.duration, a.zoom, a.userid, a.section, a.sectionname, a.scheduleddatetime FROM (SELECT DISTINCT main.id, main.name as activityname, cm.section as sectionid, c.id as courseid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.fullname as course, main.start_time as scheduleddate, main.start_time as scheduleddatetime, main.created_at as created, main.duration as duration, main.name as zoom, u.id as userid, cs.section, cs.name as sectionname, (SELECT SUM(zmp.leave_time - zmp.join_time) as attend
            FROM {zoom_meeting_participants} zmp
            JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
            JOIN {zoom} zz ON zz.id = zmd.zoomid
            WHERE zz.id = main.id AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid = u.id) as attended, (SELECT SUM(zmd.end_time-zmd.start_time)
                    FROM {zoom_meeting_details} zmd
                    JOIN {zoom} z ON z.id = zmd.zoomid
                    WHERE z.id = main.id) as totalsession
            FROM {zoom} as main
            JOIN {course_modules} cm ON cm.instance = main.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'zoom'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
        WHERE ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 AND main.start_time <= UNIX_TIMESTAMP() AND cm.deletioninprogress = 0 AND main.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $filterdata) as a WHERE 1=1 $statusfilter $searchfilterdata GROUP BY a.id";
    }

    function from() {
    }

    function joins() {
    }

    function where() {
    }

    function search() {
    }

    function filters() {
    }
	function groupby() {
    }
    /**
     * [get_rows description]
     * @param  [type] $elements [description]
     * @return [type]           [description]
     */
    function get_rows($elements) {
        return $elements;
    }
}
