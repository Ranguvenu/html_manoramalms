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
use block_learnerscript\report;
use context_system;
use stdClass;
use html_writer;

class report_chapterdetails extends reportbase implements report {

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
        $this->columns = array("chapterdetailscolumns" => array('chapter', 'video','liveclass', 'reading', 'practicequestion', 'testscore'));
        $this->parent = false;
        $this->courselevel = true;
        $this->orderable = array();
        $this->defaultcolumn = 'cs.id';
        $this->excludedroles = array("'student'");
    }
    function init() {
        global $DB;
    }
    function count() {
        $this->sql = "SELECT COUNT(DISTINCT cs.id) ";
    }

    function select() {
        $this->sql = " SELECT cs.id AS id, cs.course as course, cs.section, cs.id as sectionid";
    }

    function from() {
        $this->sql .= "  FROM {course_sections} AS cs";
    }

    function joins() {
        parent::joins();
         $this->sql .=" JOIN {course} c ON c.id = cs.course";
    }
    function where() {
        $this->sql .= " WHERE cs.id IN (SELECT sectionid
                            FROM {course_format_options} WHERE format LIKE 'flexsections'
                            AND name LIKE 'parent' AND value = 0)";
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

    function filters() {
        if (isset($this->params['filter_courses'])) {
            $this->sql .= " AND c.id = (:filter_courses)";
        }

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
