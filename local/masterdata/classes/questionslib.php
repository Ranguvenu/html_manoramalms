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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_masterdata;
class questionslib {
    public $db; 
    public function __construct(){
        global $DB;
        $this->db = $DB;
    }
    public function get_hierary_questions($hierarchy) {
        global $DB;
        $thishierarchy = [];
        $hierarchykeys = [0 => 'goalid', 1 => 'boardid', 2 => 'classid', 3 => 'subjectid', 4 => 'unitid', 5 => 'chapterid', 6 => 'topicid', 7 => 'conceptid'];
        $parent = 0;
        foreach ($hierarchy AS $key => $label) {
            $value = $this->{'get_'.$hierarchykeys[$key]}($label, $parent);
            if ($value) {
                $thishierarchy[$hierarchykeys[$key]] = $value;
                $parent = $value;
            } else {
                break;
                // throw new Exception("Missing {$key}");
            }
        }
        $questionssql = "SELECT q.id FROM {question} q
            JOIN {local_questions_courses} lqc ON lqc.questionid = q.id 
            JOIN {local_qb_questionreview} lqbqr ON lqbqr.questionid = lqc.questionid
            WHERE lqbqr.qstatus LIKE :publishstatus ";
        $params['publishstatus'] = 'publish'; 
        if (isset($thishierarchy['goalid'])) {
            $questionssql .= " AND lqc.goalid = :goalid ";
            $params['goalid'] = $thishierarchy['goalid']; 
        }
        if (isset($thishierarchy['boardid'])) {
            $questionssql .= " AND lqc.boardid = :boardid ";
            $params['boardid'] = $thishierarchy['boardid']; 
        }
        if (isset($thishierarchy['classid'])) {
            $questionssql .= " AND lqc.classid = :classid ";
            $params['classid'] = $thishierarchy['classid']; 
        }
        if (isset($thishierarchy['subjectid'])) {
            $questionssql .= " AND lqc.courseid = :subjectid ";
            $params['subjectid'] = $thishierarchy['subjectid']; 
        }
        //Mapping unitid to topicid intentionally
        if (isset($thishierarchy['unitid'])) {
            $questionssql .= " AND lqc.topicid = :unitid ";
            $params['unitid'] = $thishierarchy['unitid']; 
        }
        if (isset($thishierarchy['chapterid'])) {
            $questionssql .= " AND lqc.chapterid = :chapterid ";
            $params['chapterid'] = $thishierarchy['chapterid']; 
        }
        //Mapping topicid to unitid intentionally
        if (isset($thishierarchy['topicid'])) {
            $questionssql .= " AND lqc.unitid = :topicid ";
            $params['topicid'] = $thishierarchy['topicid']; 
        }
        $questionids= $DB->get_records_sql($questionssql,$params); 
        return $questionids;
    }
    public function get_goalid($name, $parent){
        return $this->db->get_field('local_hierarchy', 'id', ['name' => trim($name), 'parent' => $parent, 'depth' => 1]);
    }
    public function get_boardid($name, $parent){
        return $this->db->get_field('local_hierarchy', 'id', ['name' =>trim($name), 'parent' => $parent, 'depth' => 2]);
    }
    public function get_classid($name, $parent){
        return $this->db->get_field('local_hierarchy', 'id', ['name' => trim($name), 'parent' => $parent, 'depth' => 3]);
    }
    public function get_subjectid($name, $classid){
        // return $this->db->get_field('local_subjects', 'courseid', ['classessid' => $classid, 'name' => $name]);
        return $this->db->get_field_sql("SELECT lc.courseid FROM {local_subjects} lc WHERE lc.classessid = :classessid AND TRIM(lc.name) =:name ORDER BY id DESC", ['classessid' => (int)$classid, 'name' => trim($name)]);
    }
    public function get_unitid($name, $courseid){
        // return $this->db->get_field('local_units', 'id', ['courseid' => $courseid, 'name' => $name]);
        return $this->db->get_field_sql("SELECT lu.id FROM {local_units} lu WHERE lu.courseid = :courseid AND TRIM(lu.name) =:name ", ['courseid' => (int)$courseid, 'name' => trim($name)]);
    }
    public function get_chapterid($name, $unitid){
        // return $this->db->get_field('local_chapters', 'id', ['unitid' => $unitid, 'name' => $name]);
        return $this->db->get_field_sql("SELECT lc.id FROM {local_chapters} lc WHERE lc.unitid = :unitid AND TRIM(lc.name) =:name ", ['unitid' => (int)$unitid, 'name' => trim($name)]);
    }
    public function get_topicid($name, $chapterid){
        // return $this->db->get_field('local_topics', 'id', ['chapterid' => $chapterid, 'name' => $name]);
        return $this->db->get_field_sql("SELECT lt.id FROM {local_topics} lt WHERE lt.chapterid = :chapterid AND TRIM(lt.name) =:name ", ['chapterid' => (int)$chapterid, 'name' => trim($name)]);
    }
    public function get_sourceid($name,$isactive){
        return $this->db->get_field_sql("SELECT lt.id FROM {test_centre_source} lt WHERE lt.is_active = :isactive AND TRIM(lt.source_name) =:name ", ['name' => trim($name),'isactive'=>$isactive]);
        //return $this->db->get_field('test_centre_source', 'id', ['source_name' => trim($name),'is_active'=>$isactive]);
    }

    public function view_quiz_attempts($quizid) {
        global  $PAGE,$CFG;
        $renderer = $PAGE->get_renderer('local_masterdata');
        $filterparams  = $renderer->get_quizattempts(true);
        $filterparams['submitid'] = 'form#filteringform';
        $filterparams['labelclasses'] = 'd-none';
        $filterparams['inputclasses'] = 'form-control';
        $filterparams['widthclass'] = 'col-md-6';
        $filterparams['placeholder'] = get_string('search');
        $filterparams['quizid'] = $quizid;
        $globalinput=$renderer->global_filter($filterparams);
        $filterparams['attemptslist'] = $renderer->get_quizattempts();
        $filterparams['globalinput'] = $globalinput;
        $renderer->listofquizattempts($filterparams);
    }

    public function get_quiz_attempts_list($stable, $filterdata, $dataoptions) {

        global $DB,$CFG,$USER;
        $quizid = json_decode($dataoptions)->quizid;
        $quizobj = \mod_quiz\quiz_settings::create($quizid, $USER->id);
        $context = $quizobj->get_context();
        $canattempt = has_capability('mod/quiz:attempt', $context);
        $selectsql = "SELECT lqa.*,u.firstname,u.lastname,u.email,u.phone1
            FROM {local_question_attempts} lqa 
            LEFT JOIN {user} u ON lqa.userid = u.id "; 
        $countsql = "SELECT COUNT(lqa.id)
        FROM {local_question_attempts} lqa
        LEFT JOIN {user} u ON lqa.userid = u.id "; 
        if(!is_siteadmin() && $canattempt) {
            $formsql = " WHERE 1=1 AND quizid = $quizid  AND userid = $USER->id";
        } else {
            $formsql = " WHERE 1=1 AND quizid = $quizid ";
        }
        
        if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){

            $formsql .= " AND (u.firstname LIKE :firstnamesearch OR 
                              u.lastname LIKE :lastnamesearch  OR 
                              u.email LIKE :emailsearch OR 
                              u.phone1 LIKE :mobilesearch 
                            ) ";

            $searchparams = array('firstnamesearch' => '%'.trim($filterdata->search_query).'%',
                'lastnamesearch' => '%'.trim($filterdata->search_query).'%',
                'emailsearch' => '%'.trim($filterdata->search_query).'%',
                'mobilesearch' => '%'.trim($filterdata->search_query).'%',
            );

        }else{
            $searchparams = array();
        }
        $params = array_merge($searchparams);
        $totalattempts = $DB->count_records_sql($countsql.$formsql, $params);
        $formsql .=" ORDER BY lqa.id DESC";
        $attempts = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);
        $listofattempts = array();
        $count = 0;
        foreach($attempts as $attempt) {
            $listofattempts[$count]["id"]= $attempt->id;
            $listofattempts[$count]["cmid"] = $attempt->cmid;
            $listofattempts[$count]["attemptid"] = $attempt->attemptid;
            $listofattempts[$count]["fullname"] =($attempt->firstname) ? ($attempt->firstname.' '. $attempt->lastname) : '-';
            $listofattempts[$count]["email"] =($attempt->email) ? $attempt->email : '-';
            $listofattempts[$count]["phone"] = ($attempt->phone1) ? $attempt->phone1 : '-';
            $listofattempts[$count]["attemptstartdate"] = $attempt->attempt_start_date;
            $listofattempts[$count]["timetaken"] =$attempt->timetaken;
            $listofattempts[$count]["viewattempturl"] = $CFG->wwwroot.'/local/masterdata/viewattempt.php?attemptid='.$attempt->attemptid.'&cmid='.$attempt->cmid;

            $count++;
        }
        $usersContext = array(
            "hascourses" => $listofattempts,
            "totalattempts" => $totalattempts,
            "length" => COUNT($listofattempts),
        );
        return $usersContext;
    }

}
