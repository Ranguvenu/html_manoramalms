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

class report_practicequestionsdetails extends reportbase implements report {

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
        $this->columns = array("practicequestionsdetailscolumns" => array('studentname', 'course', 'quiz', 'chapter', 'topic', 'attempted', 'answered', 'correct', 'wrong', 'totalquestions'));
        $this->filters = ['users', 'courses'];
        $this->parent = false;
        $this->courselevel = true;
        $this->orderable = array();
        $this->defaultcolumn = 'quiz.id';
    }
    function init() {
        global $DB;
    }
    function count() {
        $this->sql = "SELECT COUNT(DISTINCT quiz.id) ";
    }

    function select() {
        $this->sql = "SELECT DISTINCT quiz.id, cm.section as sectionid, c.id as courseid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.fullname as course, quiz.timeopen as startdate, quiz.timeclose as duedate, quiz.name as quiz, u.id as userid, cm.id as instanceid, cs.section ";
    }

    function from() {
        $this->sql .= " FROM {quiz} as quiz";
    }

    function joins() {
        parent::joins();
         $this->sql .=" JOIN {course_modules} cm ON cm.instance = quiz.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'quiz'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0
         ";
    }

    function where() {
        $this->sql .=" WHERE quiz.testtype = 1 AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1";
        parent::where();
    }

    function search() {
        global $DB;
        if (isset($this->search) && $this->search) {
            $this->searchable =array("cs.name","quiz.name");
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

    function filters() {
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $this->sql .= " AND u.id = (:filter_users)";
        }
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $this->sql .= " AND c.id = (:filter_courses)";
        }
        if (isset($this->params['filter_startdate']) && !empty($this->params['filter_startdate']) && ($this->params['filter_startdate'] != 0)) {
            $startdate = $this->params['filter_startdate'];
            $this->sql .= " AND quiz.timeopen >= $startdate";
        }
        if (isset($this->params['filter_duedate']) && !empty($this->params['filter_duedate']) && ($this->params['filter_duedate'] != 0)) {
            $duedate = $this->params['filter_duedate'];
            $this->sql .= " AND quiz.timeopen <= $duedate";
        }
    }
	public function groupby() {
        $this->sql .=" GROUP BY quiz.id";
    }
    /**
     * [get_rows description]
     * @param  [type] $elements [description]
     * @return [type]           [description]
     */
    public function get_rows($elements) {
        return $elements;
    }
}
