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
 * Goals hierarchy
 *
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_goals
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_goals\local;

use moodle_exception;
use stdClass;

/**
 * Goal library file
 */
class goals {
    /**
     * Get list of all goals
     *
     * @param [type] $fields       [description]
     * @param [type] $limitfrom    [description]
     * @param [type] $limitnum     [description]
     * @param [type] $filtervalues [description]
     */
    public function get_goals($fields = array(), $limitfrom = 0, $limitnum = 0, $filtervalues = null) {
        global $DB;
        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        if (empty($filtervalues)) {
            return $DB->get_records('local_hierarchy', ['parent' => 0, 'depth' => 1], ' id DESC', $select, $limitfrom, $limitnum);
        } else {
            $sel = " (name LIKE '%$filtervalues->search_query%') AND parent = 0 AND depth = 1 ";
            return $DB->get_records_select('local_hierarchy', $sel, null, ' id DESC', $fields = '*', $limitfrom, $limitnum);
        }
    }

    /**
     * Get list of all boards
     * @param [type] $goalid [description]
     * @param [type] $fields [description]
     */
    public function get_boards($goalid = null, $fields = array()) {
        global $DB;
        if ($goalid == null) {
            return $DB->get_records('local_hierarchy');
        }

        !empty($fields) ? $select = implode(',', $fields) : $select = '*';

        return $DB->get_records('local_hierarchy', ['parent' => $goalid, 'depth' => 2], ' id DESC', $select);

    }

    /**
     * Get list of all classes
     * @param [type] $boardid [description]
     * @param [type] $fields  [description]
     */
    public function get_classess($boardid = null, $fields = array()) {
        global $DB;
        if ($boardid == null) {
            return $DB->get_records('local_hierarchy');
        }

        !empty($fields) ? $select = implode(',', $fields) : $select = '*';

        return $DB->get_records('local_hierarchy', ['parent' => $boardid, 'depth' => 3], ' id DESC', $select);
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

        return $DB->get_records('local_subjects', ['classessid' => $classessid], ' id DESC', $select);
    }

    public function remove_component($hierarchyid,$component) {
        global $DB;
        if($component == 'goal' || $component == 'board' || $component == 'class') {
            $categoryid = $DB->get_field('local_hierarchy','categoryid',['id'=>$hierarchyid]);
            try{
                $transaction = $DB->start_delegated_transaction();
                if($categoryid){
                    $ccategory = new stdClass();
                    $ccategory->id = $categoryid;
                    $ccategory->idnumber = uniqid();
                    $DB->update_record('course_categories', $ccategory);
                }
                $DB->delete_records('local_hierarchy',['id'=>$hierarchyid]);
                $transaction->allow_commit();
               return true;
            } catch(moodle_exception $e){
              $transaction->rollback($e);
              return false;
            }
            
        } else {
            $courseid = $DB->get_field('local_subjects','courseid',['id'=>$hierarchyid]);
            try{
                $transaction = $DB->start_delegated_transaction();
                if($courseid){
                    delete_course($courseid,false);
                }
                $DB->delete_records('local_subjects',['id'=>$hierarchyid]);
                $transaction->allow_commit();
               return true;
            } catch(moodle_exception $e){
              $transaction->rollback($e);
              return false;
            }

        }
    }
    public function is_goal_mapped($goalid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_hierarchy} 
                 WHERE parent = $goalid AND depth = 2";           
        $sector = $DB->record_exists_sql($sql);
        return ($sector) ? 1 : 0;
    }
    public function is_board_mapped($boardid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_hierarchy} 
                 WHERE parent = $boardid AND depth = 3";           
        $sector = $DB->record_exists_sql($sql);
        return ($sector) ? 1 : 0;
    }
    public function is_class_mapped($classid){
        global $DB;
        $sql =  "SELECT id
                 FROM {local_subjects} 
                 WHERE classessid = $classid";           
        $sector = $DB->record_exists_sql($sql);
        return ($sector) ? 1 : 0;
    }
    
}
