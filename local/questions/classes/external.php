<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
use dml_transaction_exception;
use context;
use context_system;
use Exception;
use moodle_exception;
use core_external;
use external_api;
use \local_questions\local\questionbank as question;
require_once($CFG->libdir.'/externallib.php');

class local_questions_external extends external_api {
    public static function viewquestions_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }
    public static function viewquestions($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $DB, $PAGE, $CFG;
        require_login();
        $params = self::validate_parameters(
            self::viewquestions_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata,
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = (new question)->get_listof_questions($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        return [
           'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'url' => $CFG->wwwroot,
        ];
    }
    public static function viewquestions_returns() {
        return new external_single_structure([
          'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
          'url' => new external_value(PARAM_RAW, 'url'),
          'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
          'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
          'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
          'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
          'records' => new external_single_structure(
                  array(
                      'hascourses' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'id'),
                                'questionid'  => new external_value(PARAM_INT, 'questionid'),
                            )
                        )
                    ),
                    'request_view' => new external_value(PARAM_INT, 'request_view', VALUE_OPTIONAL),
                    'nocourses' => new external_value(PARAM_BOOL, 'nocourses', VALUE_OPTIONAL),
                    'totalusers' => new external_value(PARAM_INT, 'totalusers', VALUE_OPTIONAL),
                    'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                )
            )
        ]);
    }
       public static function goal_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the board'),            
            'query' => new external_value(PARAM_RAW, 'Search Query'),            
        ]);    

    }
    public static function goal_selector($type,$query) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
            'query' => $query
        );
        $params = self::validate_parameters(self::goal_selector_parameters(), $params);
        if($type){
               $goalsql ="SELECT hi.id,hi.name as fullname from {local_hierarchy} as hi WHERE hi.parent = 0 AND hi.depth =1 AND hi.is_active = 1 ";
                if($query){
                $goalsql .= " AND name LIKE :query ";
                $params['query'] = '%'.$query.'%';
                }
                $goalsql .= " ORDER BY hi.id DESC";
                $data = $DB->get_records_sql($goalsql,$params);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function goal_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }    
    public static function board_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the board'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'goalid' => new external_value(PARAM_RAW, 'Selected goal',VALUE_OPTIONAL),            
        ]);    

    }
    public static function board_selector($type,$query,$goalid) {
        global $PAGE,$DB;

        $params = array(         
            'type' => $type,
            'query' =>$query,
            'goalid' =>$goalid,
        );
        $params = self::validate_parameters(self::board_selector_parameters(), $params);
        $goalid =  json_decode($goalid);
        if(is_array($goalid)){
          $gid = implode(',', $goalid);
        }else{
          $gid =  $goalid;
        }
        $gid=(int) $gid;
        if(!empty($gid)){
            $boardsql ="SELECT hi.id,hi.name as fullname from {local_hierarchy} as hi WHERE hi.parent = :gid AND hi.depth = 2 AND hi.is_active = 1 ";
            if($query){
                $boardsql .= " AND name LIKE :query ";
                $params['query'] = '%'.$query.'%';
                }
            $data = $DB->get_records_sql($boardsql,['gid'=>$gid,'query' => $params['query'] ]);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function board_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }
     public static function class_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the class'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'boardid' => new external_value(PARAM_RAW, 'Selected board',VALUE_OPTIONAL),
            
        ]);    

    }
    public static function class_selector($type,$query,$boardid) {
        global $PAGE,$DB;

        $params = array(         
            'type' => $type,
            'query' => $query,
            'boardid' =>$boardid
        );
        $params = self::validate_parameters(self::class_selector_parameters(), $params);
        $boardid =  json_decode($boardid);
        if(is_array($boardid)){
          $bid = implode(',', $boardid);
        }else{
          $bid =  $boardid;
        }
        if(!empty($bid)){
            $classsql ="SELECT hi.id,hi.name as fullname from {local_hierarchy} as hi WHERE hi.parent = :bid AND  hi.depth =3 AND hi.is_active = 1 ";
            if($query){
                $classsql .= " AND name LIKE :query ";
                $params['query'] = '%'.$query.'%';
                }
            $data = $DB->get_records_sql($classsql,['bid'=>$bid,'query' => $params['query']]);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function class_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }


       public static function course_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the Selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'classid' => new external_value(PARAM_RAW, 'Selected course',VALUE_OPTIONAL)
        ]);    

    }

    public static function course_selector($type,$query,$classid) {
        global $PAGE,$DB;

        $params = array(         
            'type' => $type,
            'query' => $query,
            'classid' =>$classid
        );
        $params = self::validate_parameters(self::course_selector_parameters(), $params);
        $classid =  json_decode($classid);
        if(is_array($classid)){
          $cid = implode(',', $classid);
        }else{
          $cid =  $classid;
        }

        if(!empty($cid)){
            $coursesql ="SELECT sub.courseid as id,sub.name as fullname from {local_subjects} AS sub WHERE classessid = :cid  AND sub.is_active = 1 ";
              if($query){
                $coursesql .= " AND name LIKE :query ";
                $params['query'] = '%'.$query.'%';
                }
            $data = $DB->get_records_sql($coursesql,['cid'=>$cid,'query' => $params['query']]);
       
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function course_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      )

                   )) 
             )
        );
    }

    public static function allcourseslist_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the Selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),           
        ]);    

    }

    public static function allcourseslist_selector($type,$query) {
        global $PAGE,$DB;

        $params = array(         
            'type' => $type,
            'query' => $query
        );
        $params = self::validate_parameters(self::allcourseslist_selector_parameters(), $params);
            //$allcoursesql ="SELECT sub.courseid as id,CONCAT(sub.name, ' (', sub.code,')') as fullname from {local_subjects} AS sub WHERE 1=1 ";
            $allcoursesql =" SELECT sub.courseid as id,CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,'(',sub.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh on lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 on lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 on lh1.parent = lh2.id AND lh2.depth = 1 
                WHERE 1=1 AND lh1.is_active = 1 AND lh2.is_active = 1 AND lh.is_active = 1 AND sub.is_active = 1";
                if($query){
                $allcoursesql .= " AND sub.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
                }
                $allcoursesql .= " ORDER BY sub.courseid DESC";
            $data = $DB->get_records_sql($allcoursesql,$params);
        return ['status' => true, 'data' => $data];
    }
    public static function allcourseslist_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      )

                   )) 
             )
        );
    }


     public static function unit_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'chapterid' => new external_value(PARAM_RAW, 'Selected unit',VALUE_OPTIONAL),
            'courseid' => new external_value(PARAM_RAW, 'Selected questionbankid',VALUE_OPTIONAL),
            'unitid' => new external_value(PARAM_RAW, 'Selected questionbankid',VALUE_OPTIONAL),
        ]);    

    }

    public static function unit_selector($type,$query,$chapterid,$courseid=null,$unitid) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
            'query' => $query,
            'chapterid' =>$chapterid,
            'courseid' =>$courseid,
            'unitid' => $unitid 
        );
        $params = self::validate_parameters(self::unit_selector_parameters(), $params);
        $chapterid =  json_decode($chapterid);
        $unitid =  json_decode($unitid);
        if(!empty($chapterid && $unitid)){
            $unitsql = " SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_topics} AS lt WHERE  courseid = :courseid AND chapterid = :chapterid  AND unitid = :unitid";
             if($query){
             $unitsql .= " AND lt.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
             }             
            $data = $DB->get_records_sql($unitsql, ['courseid' => $courseid, 'chapterid' => $chapterid, 'unitid' => $unitid ,'query' => $params['query']]);
         
           
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function unit_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success',VALUE_OPTIONAL))
                   )) 
             )
        );
    }


     public static function chapter_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'topicid' => new external_value(PARAM_RAW, 'Selected course',VALUE_OPTIONAL),
            'courseid' => new external_value(PARAM_RAW, 'Selected questionbankid',VALUE_OPTIONAL),
        ]);    

    }

    public static function chapter_selector($type,$query,$topicid,$courseid=null) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
            'query' => $query,
            'topicid' =>$topicid,
            'courseid' =>$courseid 
        );

        $params = self::validate_parameters(self::chapter_selector_parameters(), $params);
        $topicid =  json_decode($topicid);
        if(!empty($topicid)){
           $sectionsql = " SELECT section,course
                           FROM {course_sections} as cs 
                           WHERE cs.id = :topicid ";
            $getsectionid = $DB->get_record_sql($sectionsql,['topicid'=>$topicid]);
            $chaptersql = " SELECT lc.id AS id, lc.name AS fullname 
                            FROM {local_chapters} AS lc WHERE  courseid = :courseid AND unitid = :topicid";
            if($query) {
                $chaptersql .= " AND lc.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
             }                
            $data = $DB->get_records_sql($chaptersql, ['topicid'=>$topicid,'courseid'=>$courseid,'query' => $params['query']]);

         
           
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function chapter_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success',VALUE_OPTIONAL))
                   )) 
             )
        );
    }

        public static function topic_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'courseid' => new external_value(PARAM_RAW, 'Selected course',VALUE_OPTIONAL),
            'questionid' => new external_value(PARAM_RAW, 'Selected questionbankid',VALUE_OPTIONAL),
        ]);    

    }

    public static function topic_selector($type,$query,$courseid,$questionid=null) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
            'query' => $query,
            'courseid' =>$courseid,
            'questionid' =>$questionid
        );
        $params = self::validate_parameters(self::topic_selector_parameters(), $params);
        $courseid =  json_decode($courseid);
        if(is_array($courseid)){
          $cid = implode(',', $courseid);
        }else{
          $cid =  $courseid;
        }
        if(!empty($cid)){
            $topicssql = " SELECT lu.id AS id, lu.name AS fullname 
                           FROM {local_units} AS lu WHERE  courseid = :couid ";
             if($query) {
            $topicssql .= " AND lu.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
             }                 
            $data = $DB->get_records_sql($topicssql,['couid'=> $cid,'query' => $params['query']]);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function topic_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success',VALUE_OPTIONAL))
                   )) 
             )
        );
    }

     public static function concept_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the selector'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),
            'chapterid' => new external_value(PARAM_RAW, 'Selected chapterid',VALUE_OPTIONAL),
            'courseid' => new external_value(PARAM_RAW, 'Selected courseid',VALUE_OPTIONAL),
            'unitid' => new external_value(PARAM_RAW, 'Selected unitid',VALUE_OPTIONAL),
            'topicid' => new external_value(PARAM_RAW, 'Selected topicid',VALUE_OPTIONAL),
        ]);    

    }

    public static function concept_selector($type,$query,$chapterid,$courseid=null,$unitid,$topicid) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
            'query' => $query,
            'chapterid' =>$chapterid,
            'courseid' =>$courseid,
            'unitid' => $unitid,
            'topicid' => $topicid 
        );
        $params = self::validate_parameters(self::concept_selector_parameters(), $params);
        $chapterid =  json_decode($chapterid);
        $unitid =  json_decode($unitid);
        $topicid =  json_decode($topicid);
        if(!empty($chapterid && $unitid && $topicid)){
            $unitsql = " SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_concept} AS lt WHERE  courseid = :courseid AND chapterid = :chapterid  AND unitid = :unitid AND topicid = :topicid";
             if($query){
             $unitsql .= " AND lt.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
             }             
            $data = $DB->get_records_sql($unitsql, ['courseid' => $courseid, 'chapterid' => $chapterid, 'unitid' => $unitid ,'topicid' => $topicid,'query' => $params['query']]);
         
           
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function concept_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success',VALUE_OPTIONAL))
                   )) 
             )
        );
    }
       public static function difficulty_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the difficulty'),            
        ]);    

    }
    public static function difficulty_selector($type) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
        );
        $params = self::validate_parameters(self::difficulty_selector_parameters(), $params);
        if($type){
        $options = [];
        //$options[0]   =  get_string("select_difficultylevel",'customfield_difficultylevel');
        $options['1'] =  get_string('high','customfield_difficultylevel');
        $options['2'] =  get_string('medium','customfield_difficultylevel');
        $options['3'] =  get_string('low','customfield_difficultylevel');
        $data = [];
        foreach($options as $key => $option) {
            $row = [];
            $row['id'] = $key;
            $row['fullname'] = $option;
            $data[] = $row;
        }
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }
    public static function difficulty_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }

          public static function cognitive_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the cognitive'),            
        ]);    

    }
    public static function cognitive_selector($type) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
        );
        $params = self::validate_parameters(self::cognitive_selector_parameters(), $params);
        if($type){
        $options = [];
        //$options[0] = get_string("select_cognitivelevel",'customfield_cognitivelevel');
        $options['1'] =  get_string('na','customfield_cognitivelevel');
        $options['2'] =  get_string('creating','customfield_cognitivelevel');
        $options['3'] =  get_string('evaluating','customfield_cognitivelevel');
        $options['4'] =  get_string('analysing','customfield_cognitivelevel');
        $options['5'] =  get_string('applying','customfield_cognitivelevel') ; 
        $options['6'] =  get_string('understanding','customfield_cognitivelevel') ; 
        $options['7'] =  get_string('remembering','customfield_cognitivelevel') ; 
        $data = [];
        foreach($options as $key => $option) {
            $row = [];
            $row['id'] = $key;
            $row['fullname'] = $option;
            $data[] = $row;
        }
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }
    public static function cognitive_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }


    public static function source_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the source'),
            'query' => new external_value(PARAM_RAW, 'Search Query',VALUE_OPTIONAL),           
        ]);    

    }

    public static function source_selector($type,$query) {
        global $PAGE,$DB,$USER;

        $params = array(         
            'type' => $type,
            'query' => $query,
        );
        $params = self::validate_parameters(self::source_selector_parameters(), $params);
      
        if($type){
            $systemcontext = context_system::instance();
           if(is_siteadmin() || has_capability('local/questions:allowallsources',$systemcontext)){
            $sourcesql ="SELECT qs.id,qs.name as fullname from {local_question_sources} as qs WHERE 1 = 1 ";
            }else{
            $sourcesql ="SELECT qs.id,qs.name as fullname from {local_question_sources} as qs 
                         JOIN {user_sources} as us
                         ON us.sourceid = qs.id WHERE 1 = 1  AND us.userid = :userid"; 
             $params['userid'] = $USER->id;            
            }
            if($query) {
            $sourcesql .= " AND qs.name LIKE :query ";
                $params['query'] = '%'.$query.'%';
            }
            $sourcesql .=" ORDER BY qs.id DESC";
            $data = $DB->get_records_sql($sourcesql,$params);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }
    public static function source_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }

         public static function qstatus_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the difficulty'),            
        ]);    

    }
    public static function qstatus_selector($type) {
        global $PAGE,$DB;
        $params = array(         
            'type' => $type,
        );
        $params = self::validate_parameters(self::qstatus_selector_parameters(), $params);
        if($type){
            $options = [];
            // //$options[0]   =  get_string("select_difficultylevel",'customfield_difficultylevel');
            $options['draft'] =  get_string('draft','local_questions');
            $options['underreview'] =  get_string('underreview','local_questions');
            $options['readytoreview'] =  get_string('readytoreview','local_questions');
            $options['reject'] =  get_string('reject','local_questions');
            $options['publish'] =  get_string('publish','local_questions');
            $data = [];
            foreach($options as $key => $option) {
                $row = [];
                $row['id'] = $key;
                $row['fullname'] = $option;
                $data[$key] = $row;
            }
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }
    public static function qstatus_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_RAW, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success'),
                      ) 

                   )) 
             )
        );
    }



    public static function changequestionstatus_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'workshopid' => new external_value(PARAM_INT,'Workshop id',0),
            'status' => new external_value(PARAM_RAW ,'Question status',VALUE_OPTIONAL),
        ]);    
    }

    public static function changequestionstatus($questionid,$category,$status) {
        global $PAGE,$DB;
        $params = array(         
            'questionid' => $questionid,
            'workshopid' =>$category,
            'status' =>$status
        );

        $params = self::validate_parameters(self::changequestionstatus_parameters(), $params);
        $data = (new question)->questions_reviewstatus($questionid,$category,$status);
        return true;
    }

    public static function changequestionstatus_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function questionid_selector_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_RAW, 'type of the selector'),
            'id' => new external_value(PARAM_RAW, 'questionid course',VALUE_OPTIONAL)
        ]);    

    }

    public static function questionid_selector($type) {

        global $PAGE,$DB;
        $params = array(         
            'type' => $type
        );
        $params = self::validate_parameters(self::questionid_selector_parameters(), $params);
        if(!empty($type)){
           $qidsql = "SELECT id AS id,id as fullname FROM {question} WHERE 1=1";
            $data = $DB->get_records_sql($qidsql);
        }else{
            $data = array();
        }
        return ['status' => true, 'data' => $data];
    }

    public static function questionid_selector_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status: true if success'),
                'data' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'status: true if success'),
                        'fullname' => new external_value(PARAM_RAW, 'status: true if success',VALUE_OPTIONAL))
                   )) 
             )
        );
    }

    public static function quiz_questions_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Question ID'),
        ]);    
    }

    public static function quiz_questions($quizid) {
        global $PAGE, $DB;
        $params = self::validate_parameters(
            self::quiz_questions_parameters(),
            [
                'quizid' => $quizid,
            ]
        );

        $quizdata = (new question)->get_quizinfo($params['quizid']);

        return ['quizresults' => $quizdata];
    }

    public static function quiz_questions_returns() {
        return new external_single_structure([
            'quizresults' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'questionid' => new external_value(PARAM_INT, 'questionid', VALUE_OPTIONAL),
                        'questionname' => new external_value(PARAM_RAW, 'questionname', VALUE_OPTIONAL),
                        'questiontext' => new external_value(PARAM_RAW, 'questiontext', VALUE_OPTIONAL),
                        'answers' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'optionid' => new external_value(PARAM_INT, 'optionid', VALUE_OPTIONAL),
                                    'option' => new external_value(PARAM_RAW, 'option', VALUE_OPTIONAL),
                                )
                            )
                        ),
                    )
                )
            ),
        ]);
    }

    public static function createuser_withsources_parameters() {
        global $CFG;
        $userfields = [
            'username' => new external_value(PARAM_RAW, 'username'),
            'password' => new external_value(PARAM_RAW, 'password'),
            'firstname' => new external_value(PARAM_RAW, 'firstname'),
            'lastname' => new external_value(PARAM_RAW, 'lastname'),
            'email' => new external_value(PARAM_RAW, 'email'),
            'phone1' => new external_value(PARAM_INT, 'phone1'),
        ];
        return new external_function_parameters(
            [
                'users' => new external_multiple_structure(
                    new external_single_structure($userfields)
                ),
                'roles' => new external_value(PARAM_RAW, 'roles'),
                'old_id' => new external_value(PARAM_RAW, 'old_id', 0),
                'sources' => new external_value(PARAM_RAW, 'sources', ''),
                'profile' => new external_value(PARAM_RAW, 'profile', ''),
            ]
        );
    }

    public static function createuser_withsources($users, $roles, $old_id=0, $sources='', $profile='') {
        global $DB;
        $params = self::validate_parameters(
            self::createuser_withsources_parameters(),
            [
                'users' => $users,
                'old_id' => $old_id,
                'roles' => $roles,
                'sources' => $sources,
                'profile' => $profile,
            ]
        );

        $userdata = $params['users'][0];
        $userid = (new question)->createorupdate_user($userdata, $roles, $sources, $old_id, $profile);

        return ['id' => $userid];
    }

    public static function createuser_withsources_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'id'),
        ]);
    }

    public static function delete_userwithsources_parameters() {
        return new external_function_parameters(
            [
                'username' => new external_value(PARAM_RAW, 'username'),
            ]
        );
    }

    public static function delete_userwithsources($username) {
        global $DB;
        $params = self::validate_parameters(
            self::delete_userwithsources_parameters(),
            [
                'username' => $username,
            ]
        );
        
        $id = $DB->get_field('user', 'id', ['username' => $params['username']]);
        $DB->delete_records('user_sources', ['userid' => $id]);
        $DB->delete_records('user', ['id' => $id]);

        return true;
    }

    public static function delete_userwithsources_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

 /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function get_random_question_summaries_parameters() {
        return new external_function_parameters([
                'categoryid' => new external_value(PARAM_INT, 'Category id to find random questions'),
                'includesubcategories' => new external_value(PARAM_BOOL, 'Include the subcategories in the search'),
                'tagids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Tag id')
                ),
                'contextid' => new external_value(PARAM_INT,
                    'Context id that the questions will be rendered in (used for exporting)'),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
                'goalid' => new external_value(PARAM_RAW, 'goalid',VALUE_OPTIONAL),
                'boardid' => new external_value(PARAM_RAW, 'boardid',VALUE_OPTIONAL),
                'classid' => new external_value(PARAM_RAW, 'classid',VALUE_OPTIONAL),
                'courseid' => new external_value(PARAM_RAW, 'courseid',VALUE_OPTIONAL),
                'coursetopicsid' => new external_value(PARAM_RAW, 'coursetopicsid',VALUE_OPTIONAL),
                'chapterid' => new external_value(PARAM_RAW, 'chapterid',VALUE_OPTIONAL),
                'unitid' => new external_value(PARAM_RAW, 'unitid',VALUE_OPTIONAL),
                'conceptid' => new external_value(PARAM_RAW, 'conceptid',VALUE_OPTIONAL),
                
        ]);
    }

    /**
     * Gets the list of random questions for the given criteria. The questions
     * will be exported in a summaries format and won't include all of the
     * question data.
     *
     * @param int $categoryid Category id to find random questions
     * @param bool $includesubcategories Include the subcategories in the search
     * @param int[] $tagids Only include questions with these tags
     * @param int $contextid The context id where the questions will be rendered
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @return array The list of questions and total question count.
     */
    public static function get_random_question_summaries(
        $categoryid,
        $includesubcategories,
        $tagids,
        $contextid,
        $limit = 0,
        $offset = 0,
        $goalid,
        $boardid,
        $classid,
        $courseid,
        $coursetopicsid,
        $chapterid,
        $unitid,
        $conceptid
    ) {
        global $DB, $PAGE;
       // $categoryid = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
        // Parameter validation.
        $params = self::validate_parameters(
            self::get_random_question_summaries_parameters(),
            [
                'categoryid' => $categoryid,
                'includesubcategories' => $includesubcategories,
                'tagids' => $tagids,
                'contextid' => $contextid,
                'limit' => $limit,
                'offset' => $offset,
                'goalid' => $goalid,
                'boardid' => $boardid,
                'classid' => $classid,
                'courseid'=> $courseid,
                'coursetopicsid'=> $coursetopicsid,
                'chapterid'=> $chapterid,
                'unitid'=> $unitid,
                'conceptid' => $conceptid
            ]
        );
        $categoryid = $params['categoryid'];
        $includesubcategories = $params['includesubcategories'];
        $tagids = $params['tagids'];
        $contextid = $params['contextid'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $goalid = $params['goalid'];
        $boardid = $params['boardid'];
        $classid = $params['classid'];
        $courseid = $params['courseid'];
        $chapterid = $params['chapterid'];
        $unitid = $params['unitid'];
        $conceptid = $params['conceptid'];
      
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);

        $categorycontextid = $DB->get_field('question_categories', 'contextid', ['id' => $categoryid], MUST_EXIST);
        $categorycontext = \context::instance_by_id($categorycontextid);

        // $editcontexts = new \core_question\local\bank\question_edit_contexts($categorycontext);
        // // The user must be able to view all questions in the category that they are requesting.
        // $editcontexts->require_cap('moodle/question:viewall');

        $loader = new \local_questions\bank\randomquestion_loader(new \qubaid_list([]));
        $properties = local_questions\bank\questionsummaryexporter::get_mandatory_properties();
        $questions = $loader->get_questions_quiz($categoryid, $includesubcategories, $tagids, $limit, $offset, $properties,$goalid,$boardid,$classid,$courseid,$coursetopicsid,$chapterid,$unitid,$conceptid);
    
        $totalcount = $loader->count_questions($categoryid, $includesubcategories, $tagids);

        $renderer = $PAGE->get_renderer('core');

        
        $formattedquestions = array_map(function($question) use ($context, $renderer) {
            $exporter = new \core_question\external\question_summary_exporter($question, ['context' => $context]);
            return $exporter->export($renderer);
        }, $questions);
        return [
            'totalcount' => $totalcount,
            'questions' => $formattedquestions
        ];
    }

    /**
     * Returns description of method result value.
     */
    public static function  get_random_question_summaries_returns() {
        return new external_single_structure([
            'totalcount' => new external_value(PARAM_INT, 'total number of questions in result set'),
            'questions' => new external_multiple_structure(
                \core_question\external\question_summary_exporter::get_read_structure()
            )
        ]);
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function create_sources_parameters() {
        return new external_function_parameters([
            'name' => new external_value(PARAM_RAW, 'name'),
            'code' => new external_value(PARAM_RAW, 'code'),
        ]);
    }

    /**
     *
     * @param string $name Name of the source
     * @param string $code Code of the source
     */
    public static function create_sources($name, $code) {
        global $DB, $PAGE;
        $params = self::validate_parameters(
            self::create_sources_parameters(),
            [
                'name' => $name,
                'code' => $code,
            ]
        );

        $source = new stdClass();
        $source->name = $params['name'];
        $source->code = $params['code'];
      
        $id = $DB->get_field('local_question_sources', 'id', ['code' => $code]);
        if ($id > 0) {
            $source->id = $id;
            $source->timemodified = time();
            $DB->update_record('local_question_sources', $source);
        } else {
            $source->timecreated = time();
            $id = $DB->insert_record('local_question_sources', $source);
        }

        return [
            'id' => $id,
        ];
    }

    /**
     * Returns id of result value.
     */
    public static function  create_sources_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'total number of questions in result set'),
        ]);
    }

    public static function regrade_all_questions_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'quiz'),
        ]);
    }
    public static function regrade_all_questions($quizid) {
        global $DB, $PAGE;
        $params = self::validate_parameters(
            self::regrade_all_questions_parameters(),
            [
                'quizid' => $quizid
            ]
        );
        $quizinfo = $DB->get_record('quiz', ['id' => $quizid]);
        $cm = $DB->get_record_sql("SELECT cm.* FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module AND m.name LIKE 'quiz' WHERE cm.instance = :instance", ['instance' => $quizinfo->id]);
        $customfielddata = $DB->get_records_sql("SELECT cff.id, cff.shortname, cfd.value FROM {customfield_field} cff JOIN {customfield_data} cfd ON cfd.fieldid = cff.id WHERE cfd.instanceid = :cmid ", ['cmid' => $cm->id]);
        foreach($customfielddata AS $customdata) {
            $quizinfo->{$customdata->shortname} = $customdata->value;
        }
        if ($quizinfo->nsca) {
            $course = get_course($quizinfo->course);
            $quizobj = \mod_quiz\quiz_settings::create($quizinfo->id);
            $structure = $quizobj->get_structure();
            foreach ($structure->get_slots() AS $slot) {
                $structure->update_slot_maxmark($slot , $quizinfo->nsca);
            }
            $quizobj->get_grade_calculator()->recompute_quiz_sumgrades();
        }
        return true;
    }
    public static function regrade_all_questions_returns() {
        return new external_value(PARAM_BOOL, 'status of the service');
    }
}
