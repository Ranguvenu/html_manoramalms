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
 * units hierarchy
 *
 * This file defines the current version of the local_units Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_units
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_units\local;

use moodle_exception;
use stdClass;

/**
 * unit library file
 */
class units {
    /**
     * Get list of all units
     *
     * @param [type] $fields       [description]
     * @param [type] $limitfrom    [description]
     * @param [type] $limitnum     [description]
     * @param [type] $filtervalues [description]
     */
    public function get_units($fields = array(), $limitfrom = 0, $limitnum = 0, $filtervalues = null) {
        global $DB;
        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        if (empty($filtervalues)) {
            $data = $DB->get_records('local_units', [], ' id DESC', $select, $limitfrom, $limitnum);
        } else {
            $params =[];
            $sql = "SELECT lu.*
                    FROM {local_units} as lu JOIN {local_subjects} AS sub ON sub.courseid = lu.courseid 
                    JOIN {local_hierarchy} as classhier ON classhier.id = sub.classessid 
                    JOIN {local_hierarchy} as boardhier ON boardhier.id = classhier.parent 
                    JOIN {local_hierarchy} as goalhier ON goalhier.id = boardhier.parent 
                    WHERE 1 = 1";
            if($filtervalues->goal){
              $params['goalhier'] =  $filtervalues->goal;
              $sql .=" AND goalhier.id = :goalhier";
            }
             if($filtervalues->board){
              $params['boardhier'] =  $filtervalues->board;
               $sql .=" AND boardhier.id = :boardhier";
            }
            if($filtervalues->class){
              $params['classhier'] =  $filtervalues->class; 
               $sql .=" AND classhier.id = :classhier"; 
            }
             if($filtervalues->subject){
              $params['courseid'] =  $filtervalues->subject;  
               $sql .=" AND lu.courseid = :courseid"; 
            }
            if($filtervalues->search_query){
                $params['filtervalues'] =  '%'.$filtervalues->search_query.'%';
                $sql .= " AND (lu.name LIKE :filtervalues)";
            }
            $data = $DB->get_records_sql($sql,$params,$limitfrom, $limitnum);
        }
        return $data;
    }

    /**
     * Get list of all chapters
     * @param [type] $unitid [description]
     * @param [type] $fields [description]
     */
    public function get_chapters($unitid = null, $fields = array()) {
        global $DB;
        if ($unitid == null) {
            return $DB->get_records('local_chapters');
        }
        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        return $DB->get_records('local_chapters', ['unitid' => $unitid ], ' id ASC', $select);

    }

    /**
     * Get list of all topics
     * @param [type] $chapterid [description]
     * @param [type] $fields  [description]
     */
    public function get_topics($unitid = null,$chapterid = null, $fields = array()) {
        global $DB;

        if ($chapterid == null && $unitid == null) {
            return $DB->get_records('local_topics');
        }

        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        return $DB->get_records('local_topics', ['unitid' => $unitid, 'chapterid' => $chapterid], ' id ASC', $select);
    }
      /**
     * Get list of all topics
     * @param [type] $chapterid [description]
     * @param [type] $fields  [description]
     */
    public function get_concepts($unitid = null,$chapterid = null,$topicid = null, $fields = array()) {
        global $DB;
        if ($chapterid == null && $unitid == null && $topicid == null) {
            return $DB->get_records('local_concept');
        }
        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        return $DB->get_records('local_concept', ['unitid' => $unitid, 'chapterid' => $chapterid, 'topicid'=> $topicid], ' id DESC', $select);
    }
    /**
     * Get list of all subjects
     * @param [type] $classessid [description]
     * @param [type] $fields     [description]
     */
    public function get_subjects($classessid = null, $fields = array()) {
        global $DB;
        if ($classessid == null) {
            return $DB->get_records('local_subjects');
        }

        !empty($fields) ? $select = implode(',', $fields) : $select = '*';

        return $DB->get_records('local_subjects', ['classessid' => $classessid], ' id ASC', $select);
    }

    public function remove_component($hierarchyid,$component) {
       

        global $DB;
        if($component == 'unit') {
                try{
                    $DB->delete_records('local_units',['id'=>$hierarchyid]);
                    return true;
                }catch(moodle_exception $e){
                  $transaction->rollback($e);
                  return false;
                }
        }
        elseif ($component == 'chapter') {
                try{
                    $DB->delete_records('local_chapters',['id'=>$hierarchyid]);
                    return true;
                }catch(moodle_exception $e){
                  $transaction->rollback($e);
                  return false;
                }
        }
        elseif ($component == 'topic') {
                try{
                    $DB->delete_records('local_topics',['id'=>$hierarchyid]);
                   return true;
                }catch(moodle_exception $e){
                  $transaction->rollback($e);
                  return false;
                }
        } 
        elseif ($component == 'concept') {
                try{
                    $DB->delete_records('local_concept',['id'=>$hierarchyid]);
                   return true;
                }catch(moodle_exception $e){
                  $transaction->rollback($e);
                  return false;
                }
        }   
    }
    public function is_unit_mapped($unitid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_chapters} 
                 WHERE unitid = $unitid";           
        $sector = $DB->record_exists_sql($sql); 
        return ($sector) ? 1 : 0;
    }
    public function is_chapter_mapped($chapterid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_topics} 
                 WHERE chapterid = $chapterid";           
        $sector = $DB->record_exists_sql($sql);
         return ($sector) ? 1 : 0;
    }
     public function is_topic_mapped($topicid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_concept} 
                 WHERE topicid = $topicid";           
        $sector = $DB->record_exists_sql($sql);
         return ($sector) ? 1 : 0;
    }
    
}
