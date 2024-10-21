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
 * @author: Jahnavi<jahnavi@eabyas.in>
 * @date: 2020
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls as ls;
use block_learnerscript\report;
use context_system;
use stdClass;
use html_writer;

class report_chapterreport extends reportbase implements report {

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
        $this->columns = array("chapterwisecolumns" => array('chapter','chaptername', 'progress', 'liveclass', 'practicetest', 'reading', 'video', 'testscore'));
        $this->parent = false;
        $this->filters = array('users', 'courses');
        $this->courselevel = true;
        $this->orderable = array();
        $this->defaultcolumn = "CONCAT(cf.id, '@', u.id)";
    }
    function init() {
        global $DB;
    }
    function count() {
        $this->sql = "SELECT COUNT(DISTINCT CONCAT(cf.id, '@', u.id)) ";
    }

    function select() {
        $this->sql = " SELECT DISTINCT CONCAT(cf.id, '@', u.id), cf.id AS formatid, cs.id AS sectionid,
                     u.id AS userid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.id AS courseid, cs.name as chapter, cs.section ";
    }

    public function from() {
        $this->sql .= " FROM {course_format_options} as cf";
    }

    public function joins() {
        $this->sql .= " JOIN {course_sections} as cs ON cs.id = cf.sectionid
        JOIN {course} c ON c.id = cs.course
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0";
        parent::joins();
    }

    public function where() {
        $this->sql .= " WHERE ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
        AND cf.value = 0 AND cf.name like 'parent'
        AND cf.format LIKE 'flexsections'";
        $this->params['visible'] =1;
        parent::where();
    }
    function search() {
        global $DB;
        if (isset($this->search) && $this->search) {
            $this->searchable = array("cs.name");
            $statsql = array();
            $i = 0;
            foreach ($this->searchable as $key => $value) {
                $i++;
                $statsql[] = $DB->sql_like($value, ":queryparam$i", false);
                $this->params["queryparam$i"] = "%$this->search%";
            }
            $fields = implode(" OR ", $statsql);
            $this->sql .= " AND ($fields) ";
        }
    }

    public function filters() {
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $this->sql .= " AND u.id = (:filter_users)";
        }
        if (isset($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $this->sql .= " AND c.id = (:filter_courses)";
        }
    }
	public function groupby() {

    }

    public function get_rows($users) {
        return $users;
    }
}
