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
use DateTime;
class report_testscoredetails extends reportbase implements report {

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
        $this->columns = array("testscoredetailscolumns" => array('startdate', 'enddate', 'studentname', 'course', 'activity', 'chapter', 'topic', 'score', 'status', 'testid', 'url', 'modulename', 'activityid'));
        $this->filters = ['users', 'courses', 'status'];
        $this->parent = false;
        $this->courselevel = true;
        $this->orderable = array();
        $this->defaultcolumn = '';
    }
    function init() {
        global $DB;
        $filters = '';
        $assstatus = '';
        $quzstatus = '';
        $quzwhere = '';
        $asswhere = '';
        $concat = '';

    }
    function count() {
        global $DB;
        $date = new DateTime();
        $timestamp = $date->getTimestamp();
        $assignfilter = '';
        $quizfilter = '';
        $search2 = '';
        $search3 = '';
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filters .= " AND u.id = ".$this->params['filter_users'];
        }
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $filters .= " AND c.id = ".$this->params['filter_courses'];
        }
        if (isset($this->params['filter_startdate']) && !empty($this->params['filter_startdate']) && ($this->params['filter_startdate'] != 0)) {
            $startdate = $this->params['filter_startdate'];
            $assignfilter .= " AND ass.allowsubmissionsfromdate >= $startdate";
            $quizfilter .= " AND qz.timeopen >= $startdate";
        }
        if (isset($this->params['filter_duedate']) && !empty($this->params['filter_duedate']) && ($this->params['filter_duedate'] != 0)) {
            $duedate = $this->params['filter_duedate'];
            $assignfilter .= " AND ass.allowsubmissionsfromdate <= $duedate";
            $quizfilter .= " AND qz.timeopen >= $duedate";
        }
        if(!empty($this->params['filter_status']) && $this->params['filter_status'] == 1) {
            $assstatus .= " JOIN {assign_submission} asub ON asub.assignment = ass.id AND asub.userid = u.id ";
            $asswhere .= "AND asub.status = 'submitted'";

            $quzstatus .= " JOIN {quiz_attempts} qat ON qat.quiz = qz.id AND qat.userid = u.id";
            $quzwhere .= "AND qat.state = 'finished' ";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 2) {
           $asswhere .= " AND $timestamp > ass.duedate AND concat(ass.id,'@',u.id) NOT IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1 AND status = 'submitted')";
            $quzwhere .= " AND qz.timeclose !=0 AND $timestamp > qz.timeclose AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1 =1 AND state = 'finished')";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 3 ) {
            $asswhere .= " AND $timestamp < ass.duedate AND concat(ass.id,'@',u.id) NOT IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1)";
            $quzwhere .= " AND ((qz.timeclose !=0 AND $timestamp < qz.timeclose AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1)) OR (qz.timeclose = 0 AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1)))";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 4 ) {
            $asswhere .= " AND $timestamp < ass.duedate AND concat(ass.id,'@',u.id) IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1 AND status = 'new')";
            $quzwhere .= " AND ((qz.timeclose !=0 AND $timestamp < qz.timeclose AND concat(qz.id,'@',u.id) IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1 AND state = 'inprogress')) OR (qz.timeclose = 0 AND concat(qz.id,'@',u.id) IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1 AND state = 'inprogress')))";
        }
        if (isset($this->search) && $this->search) {
            $this->params['queryparam2'] = "%$this->search%";
            $this->params['queryparam3'] = "%$this->search%";
            $search2 .= " AND cs.name LIKE :queryparam2";
            $search3 .= " AND cs.name LIKE :queryparam3";
            
        }
        $activities= $DB->get_records_sql("SELECT DISTINCT concat(cm.id,'@',ass.id) as activitycount
        FROM {assign} ass
        JOIN {course_modules} as cm ON cm.instance = ass.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'assign'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 $assstatus 
        WHERE ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 $filters $asswhere $assignfilter $search2  GROUP BY concat(cm.id,'@',ass.id)
        UNION 
        SELECT concat(cm.id,'@',qz.id) as activitycount
        FROM {quiz} qz
        JOIN {course_modules} as cm ON cm.instance = qz.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'quiz'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 $quzstatus
        WHERE qz.testtype = 0 AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 $filters $quizfilter $quzwhere $search3 GROUP BY concat(cm.id,'@',qz.id)", $this->params);

        $totalactivities = count($activities);
        $this->sql = "SELECT $totalactivities AS totalactivities ";
    }

    function select() {
        $date = new DateTime();
        $timestamp = $date->getTimestamp();
        $assignfilter = '';
        $quizfilter = '';
        $search = '';
        $search1 = '';
        $search = '';
        if (!empty($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filters .= " AND u.id = ".$this->params['filter_users'];
        }
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $filters .= " AND c.id = ".$this->params['filter_courses'];
        }
        if (isset($this->params['filter_startdate']) && !empty($this->params['filter_startdate']) && ($this->params['filter_startdate'] != 0)) {
            $startdate = $this->params['filter_startdate'];
            $assignfilter .= " AND ass.allowsubmissionsfromdate >= $startdate";
            $quizfilter .= " AND qz.timeopen >= $startdate";
        }
        if (isset($this->params['filter_duedate']) && !empty($this->params['filter_duedate']) && ($this->params['filter_duedate'] != 0)) {
            $duedate = $this->params['filter_duedate'];
            $assignfilter .= " AND ass.allowsubmissionsfromdate <= $duedate";
            $quizfilter .= " AND qz.timeopen <= $duedate";
        }
        if(!empty($this->params['filter_status']) && $this->params['filter_status'] == 1) {
            $assstatus .= " JOIN {assign_submission} asub ON asub.assignment = ass.id AND asub.userid = u.id ";
            $asswhere .= "AND asub.status = 'submitted'";

            $quzstatus .= " JOIN {quiz_attempts} qat ON qat.quiz = qz.id AND qat.userid = u.id";
            $quzwhere .= "AND qat.state = 'finished' ";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 2) {
           $asswhere .= " AND $timestamp > ass.duedate AND concat(ass.id,'@',u.id) NOT IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1 AND status = 'submitted')";
            $quzwhere .= " AND qz.timeclose !=0 AND $timestamp > qz.timeclose AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1 =1 AND state = 'finished')";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 3 ) {
            $asswhere .= " AND $timestamp < ass.duedate AND concat(ass.id,'@',u.id) NOT IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1)";
            $quzwhere .= " AND ((qz.timeclose !=0 AND $timestamp < qz.timeclose AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1)) OR (qz.timeclose = 0 AND concat(qz.id,'@',u.id) NOT IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1)))";
        } else if (!empty($this->params['filter_status']) && $this->params['filter_status'] == 4 ) {
            $asswhere .= " AND $timestamp < ass.duedate AND concat(ass.id,'@',u.id) IN(SELECT concat(assignment,'@',userid) FROM {assign_submission} WHERE 1=1 AND status = 'new')";
            $quzwhere .= " AND ((qz.timeclose !=0 AND $timestamp < qz.timeclose AND concat(qz.id,'@',u.id) IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1 AND state = 'inprogress')) OR (qz.timeclose = 0 AND concat(qz.id,'@',u.id) IN(SELECT concat(quiz,'@',userid) FROM {quiz_attempts} WHERE 1=1 AND state = 'inprogress')))";
        }
        if (isset($this->search) && $this->search) {
            $this->params['queryparam'] = "%$this->search%";
            $this->params['queryparam1'] = "%$this->search%";
            $search .= " AND cs.name LIKE :queryparam";
            $search1 .= " AND cs.name LIKE :queryparam1";
            
        }
         $this->sql = "SELECT DISTINCT concat(cm.id,'@',ass.id) as id, ass.id as activityid, ass.name as activity, cm.module, m.name as modulename, cm.instance as instance, cm.section as sectionid, c.id as courseid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.fullname as course, u.id as userid, ass.allowsubmissionsfromdate AS startdate, ass.duedate AS duedate, cm.id as instanceid, cs.section, cs.name as sectionname, ass.grade as grade
        FROM {assign} ass
        JOIN {course_modules} as cm ON cm.instance = ass.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'assign'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 $assstatus
        WHERE ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 $filters $asswhere $assignfilter $search GROUP BY concat(cm.id,'@',ass.id)
        UNION 
        SELECT DISTINCT concat(cm.id,'@',qz.id) as id, qz.id as activityid, qz.name as activity, cm.module, m.name as modulename, cm.instance as instance, cm.section as sectionid, c.id as courseid, CONCAT(u.firstname, ' ', u.lastname) AS studentname, c.fullname as course, u.id as userid, qz.timeopen AS startdate, qz.timeclose AS duedate, cm.id as instanceid, cs.section, cs.name as sectionname, qz.grade as grade
        FROM {quiz} qz
        JOIN {course_modules} as cm ON cm.instance = qz.id
        JOIN {modules} m ON cm.module = m.id AND m.name = 'quiz'
        JOIN {course} c ON c.id = cm.course
        JOIN {course_sections} cs ON cs.id = cm.section
        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
        JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
        JOIN {role_assignments}  ra ON ra.userid = ue.userid
        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
        JOIN {context} ctx ON ctx.instanceid = c.id
        JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 $quzstatus
        WHERE qz.testtype = 0 AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1 $filters $quizfilter $search1 $quzwhere GROUP BY concat(cm.id,'@',qz.id)";
    }

    function from() {

    }

    function joins() {
        parent::joins();

         $this->sql .="";
    }

    function where() {
    }

    function search() {
        global $DB;
        /*if (isset($this->search) && $this->search) {
            $this->searchable =array("act.sectionname");
            $statsql = array();
            foreach ($this->searchable as $key => $value) {
                $statsql[] =$DB->sql_like($value, "'%" . $this->search . "%'",$casesensitive = false,$accentsensitive = true, $notlike = false);
            }
            $fields = implode(" OR ", $statsql);     
            $this->sql .= " AND ($fields) ";
        }*/
    }

    function filters() {
    }
	public function groupby() {
    
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
