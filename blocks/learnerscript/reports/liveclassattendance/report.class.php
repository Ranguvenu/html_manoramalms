<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License AS published by
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

/** LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage learnerscript
 * @author: jahnavi<jahnavi@eabyas.com>
 * @date: 2022
 */

 namespace block_learnerscript\lsreports;
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls as ls;
use block_learnerscript\report;


class report_liveclassattendance extends reportbase implements report {

    public function __construct($report, $reportproperties){
        parent::__construct($report);
        $this->courselevel = true;
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $columns = ['attendancepercentages', 'studentsattended'];
        $this->columns = ['liveclassattendancecolumns' => $columns];
        $this->filters = array('courses');        
        $this->orderable = array('attendancepercentages', 'studentsattended');
        // $this->defaultcolumn = 't1.attendancepercentages';

    }
    function init() {
        global $DB;
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams AS $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
    }
    function count() {
        global $DB;
        $percentages = [0, 25, 50, 75, 100];
        $totalpercentages = COUNT($percentages);

        $this->sql = " SELECT $totalpercentages AS totalpercentages";
    }
    function select() {
        global $DB, $CFG;
        $i = 0;
        $concatsql = " ";
        $percentages = ['0%', '25%', '50%', '75%', '100%'];
        $i = 0;
        $query = '';
        foreach ($percentages as $percent) {
            if($i > 0){
                $query .= " UNION SELECT '".$percent."' AS attendancepercentages ";
            }else{
                $query .= " SELECT '".$percent."' AS attendancepercentages ";
            }
            $i++;
        }
        $this->sql = " SELECT t1.attendancepercentages  FROM ($query) AS t1 WHERE 1=1 ORDER BY CAST(t1.attendancepercentages AS UNSIGNED) ASC ";
        
        parent::select();
    }
    
    function from() { 
        $this->sql .= " ";
    }

    function joins() {
        $this->sql .= " ";
        parent::joins();
    }

    function where() {
        $this->sql .= " ";
        parent::where();
    }

    function search() {
    }

    function filters() {
        // if (isset($this->params['filter_courses']) && !empty($this->params['filter_courses'])) {
        //     $this->sql .= " AND c.id = (:filter_courses)";
        // }
    }
    function groupby() {
    }
    /**
     * [get_rows description]
     * @param  array  $users [description]
     * @return [type]        [description]
     */
    public function get_rows($users = array()) {
        return $users;
    }
}
