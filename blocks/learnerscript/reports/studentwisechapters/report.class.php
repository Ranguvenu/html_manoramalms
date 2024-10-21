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
 * TODO describe file report.class
 *
 * @package    block_learnerscript
 * @copyright  2023 Jahnavi <jahnavi.nanduri@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_learnerscript\lsreports;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls as ls;

defined('MOODLE_INTERNAL') || die();

class report_studentwisechapters extends reportbase {
    public function __construct($report, $reportproperties) {
        global $DB;
        parent::__construct($report, $reportproperties);
        $columns = ['studentname', 'chapter', 'video', 'liveclass', 'reading', 'practisequestions', 'testscore'];
        $this->columns = ['studentwisechapters' => $columns];

        $this->components = array('columns', 'conditions', 'ordering', 'filters','permissions', 'plot');
        $this->filters = array('users', 'chapters');
        $this->parent = true;
        $this->orderable = array('');
        $this->searchable = array();
        $this->defaultcolumn = "CONCAT(cf.id, '@', u.id)";
        $this->excludedroles = array("'student'");
    }

    public function init() {
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
    }

    public function count() {
        $this->sql = "SELECT COUNT(DISTINCT CONCAT(cf.id, '@', u.id))";
    }

    public function select() {
        $this->sql = "SELECT DISTINCT CONCAT(cf.id, '@', u.id), cf.id AS formatid, cs.id AS sectionid,
                     u.id AS userid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.id AS courseid,
                    cs.section
                    ";
        parent::select();
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
        if (!is_siteadmin($this->userid) && !(new ls)->is_manager($this->userid, $this->contextlevel, $this->role)) {
            if ($this->rolewisecourses != '') {
                $this->sql .= " AND c.id IN ($this->rolewisecourses) ";
            }
        }
        parent::where();
    }
    function search() {
        global $DB;
        if (isset($this->search) && $this->search) {
            $this->searchable = array("CONCAT(u.firstname, ' ', u.lastname)", "u.firstname","u.lastname", "cs.name");
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
        if (!empty($this->params['filter_chapters']) && $this->params['filter_chapters'] > 0) {
            $this->sql .= " AND cf.id = (:filter_chapters)";
        }
        if (isset($this->params['filter_courses'])) {
            $this->sql .= " AND c.id = (:filter_courses)";
        }
    }

    public function groupby() {

    }

    public function get_rows($users) {
        return $users;
    }

    public function column_queries($columnname, $userid) {
    }
}
