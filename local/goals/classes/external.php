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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');
use local_goals\local\goals as goals;
use local_goals\controller as controller;
use cache;
use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use context_system;

require_once("{$CFG->dirroot}/course/externallib.php");

/**
 * local_goals_external [description]
 */
class local_goals_external extends external_api {
    /**
     * Goals view parameters [description]
     */
    public static function goals_view_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Goals_view [description]
     * @param [type] $options [description]
     * @param [type] $dataoptions [description]
     * @param [type] $contextid [description]
     * @param [type] $filterdata [description]
     * @param [type] $offset [description]
     * @param [type] $limit [description]
     */
    public static function goals_view($options, $dataoptions, $contextid, $filterdata, $offset = 0, $limit = 0) {
        global $DB, $PAGE;
        require_login();

        // Parameter validation.
        $params = self::validate_parameters(
            self::goals_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;

        $goal = new goals();
        $goalslist = $goal->get_goals(array(), $offset, $limit, $filtervalues);
        foreach ($goalslist as $goals) {
            $boarddata = array();
            $boards = $goal->get_boards($goals->id, ['id', 'name', 'code']);
            foreach ($boards as $board) {
                $classesdata = array();
                if (empty($classesdata)) {
                    $classess = $goal->get_classess($board->id, ['id', 'name', 'code']);
                    foreach ($classess as $clas) {
                        $subjectdata = array();
                        $cache = cache::make('local_goals', 'subjects');
                        if (empty($subjectdata)) {
                            $subjects = $goal->get_subjects($clas->id, ['id', 'name', 'code']);
                            foreach ($subjects as $sub) {
                                $subjectdata[] = ['subjectid' => $sub->id,
                                                       'name' => $sub->name ,
                                                       'code' => $sub->code
                                                   ];
                            }
                        }
                        $classesdata[] = ['classessid' => $clas->id,
                                           'name' => $clas->name ,
                                           'code' => $clas->code,
                                           'subjects' => $subjectdata
                                           ];
                    }
                }
                $boarddata[] = ['boardid' => $board->id,
                                    'name' => $board->name,
                                    'code' => $board->code,
                                    'classess' => $classesdata
                                ];
            }

            $records['goals'][] = ['goalid' => $goals->id,
                                    'name' => $goals->name,
                                    'code' => $goals->code,
                                    'boards' => $boarddata
                                ];
        }
        $records['length'] = count($goalslist);
        $totalcount = count($goal->get_goals());
        $return = [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $records,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        return $return;
    }

    /**
     * Goals view return [description]
     */
    public static function goals_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'records' => new external_single_structure(
                    array(
                        'goals' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'goalid' => new external_value(PARAM_RAW, 'id', VALUE_OPTIONAL),
                                    'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                                    'code' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                                    'boards' => new external_multiple_structure(
                                         new external_single_structure(
                                            array(
                                                'boardid' => new external_value(PARAM_RAW, 'id'),
                                                 'name' => new external_value(PARAM_RAW, 'name'),
                                                 'code' => new external_value(PARAM_RAW, 'code'),
                                                'classess' => new external_multiple_structure(
                                                    new external_single_structure(
                                                        array(
                                                            'classessid' => new external_value(PARAM_RAW, 'id'),
                                                            'name' => new external_value(PARAM_RAW, 'name'),
                                                            'code' => new external_value(PARAM_RAW, 'code'),
                                                            'subjects' => new external_multiple_structure(
                                                                new external_single_structure(
                                                                    array(
                                                                        'subjectid' => new external_value(PARAM_RAW, 'id'),
                                                                        'name' => new external_value(PARAM_RAW, 'name'),
                                                                        'code' => new external_value(PARAM_RAW, 'code'))
                                                                ), '', VALUE_OPTIONAL),
                                                            )
                                                    ), '', VALUE_OPTIONAL),
                                            )
                                    ), '', VALUE_OPTIONAL),
                                )
                                )
                            , '', VALUE_OPTIONAL),
                        'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                    )
                )
        ]);
    }
    /**
     * Create batch params
     */
    public static function create_batch_parameters() {
        return new external_function_parameters(
            array(
                'name' => new external_value(PARAM_RAW, 'name'),
                'code' => new external_value(PARAM_RAW, 'code'),
                'enrolstartdate' => new external_value(PARAM_RAW, 'enrolstartdate'),
                'enrolenddate' => new external_value(PARAM_RAW, 'enrolenddate'),
                'duration' => new external_value(PARAM_INT, 'duration'),
                'studentlimit' => new external_value(PARAM_INT, 'studentlimit'),
                'provider' => new external_value(PARAM_RAW, 'provider'),
                'courses' => new external_value(PARAM_RAW, 'courses'),
            )
        );
    }

    /**
     * Create batch
     * @param [type] $name [description]
     * @param [type] $code [description]
     * @param [type] $enrolstartdate [description]
     * @param [type] $enrolenddate [description]
     * @param [type] $duration [description]
     * @param [type] $studentlimit [description]
     * @param [type] $provider [description]
     * @param [type] $courses [description]
     */
    public static function create_batch($name, $code, $enrolstartdate, $enrolenddate, $duration, $studentlimit, $provider, $courses) {
        // Parameter validation.
        global $DB, $USER, $CFG;
        $params = self::validate_parameters (
               self::create_batch_parameters(), ['name' => $name ,
                                    'code' => $code ,
                                    'enrolstartdate' => $enrolstartdate ,
                                    'enrolenddate' => $enrolenddate,
                                    'duration' => $duration,
                                    'studentlimit' => $studentlimit,
                                    'provider' => $provider,
                                    'courses' => $courses,
                                  ]);
        $data = new stdClass();
        $data->name = $name;
        $data->code = $code;
        $data->enrolstartdate = $enrolstartdate;
        $data->enrolenddate = $enrolenddate;
        $data->duration = $duration;
        $data->studentlimit = $studentlimit;
        $data->provider = $provider;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->usermodified = time();

        $batch = $DB->insert_record('local_batches', $data);

        $classcategoryid = $DB->get_field_sql("SELECT classessid FROM mdl_local_subjects WHERE id IN ($courses) GROUP BY classessid");
        $categoryid = (new local_goals\controller)->get_parent_category($classcategoryid);
        $category = array('name' => $name, 'idnumber' => $code, 'description' => 'This category is related to batch', 'parent' => $categoryid, 'visible' => 1, 'depth' => 4, 'timemodified' => time());
        $category = core_course_category::create($category);
        $record = ['categoryid' => $category->id,
                   'parent' => $classcategoryid,
                   'depth' => 4,
                   'name' => $name,
                   'code' => $code,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $USER->id];
        $DB->insert_record('local_hierarchy', $record);

        if ($batch) {
            $courselist = explode(",", $courses);
            foreach ($courselist as $c => $cvalue) {
                $subjectrecord = $DB->get_record('local_subjects', array('id' => $cvalue));
                $coursename = $DB->get_record_sql("SELECT fullname, shortname FROM {course} WHERE id = $subjectrecord->courseid");
                $newcourse['fullname'] = $coursename->fullname;
                $newcourse['shortname'] = $name . '_' . $coursename->shortname;
                $newcourse['categoryid'] = $category->id;
                $newcourse['visible'] = true;
                $newcourse['options'][] = array('name' => 'users', 'value' => true);
                $duplicated = \core_course_external::duplicate_course($subjectrecord->courseid, $newcourse['fullname'], $newcourse['shortname'], $newcourse['categoryid'], $newcourse['visible'], array());
                // We need to execute the return values cleaning process to simulate the web service server.
                // $duplicated = external_api::clean_returnvalue(core_course_external::duplicate_course_returns(), $duplicated);

                $batch_course = new stdClass();
                $batch_course->batchid = $batch;
                $batch_course->courseid = $duplicated['id'];
                $batch_course->timecreated = time();
                $batch_course->timemodified = time();
                $batch_course->usermodified = time();
                $batch_course_data = $DB->insert_record('local_batch_courses', $batch_course);
            }
            if ($batch_course_data) {
                $batch_course_data_status = true; 
            } else {
                $batch_course_data_status = false;
            }
        }

        try {
            if ($batch_course_data_status) {
                $return = 'created batch';
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            new moodle_exception('statuserror', 'local_goals');
        }

        return array(
            'returnurl' => $return
        );
    }

    /**
     * Returns
     */
    public static function  create_batch_returns() {
        return new external_single_structure(
            array(
                'returnurl' => new external_value(PARAM_RAW, 'returnurl'),
            )
        );
    }
    public static function candeletecomponent_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id'),
                'component' => new external_value(PARAM_RAW, 'component'),
               
            )
        );
    }
    public static function candeletecomponent($id,$component){
        global $DB;
        $params = self::validate_parameters(self::candeletecomponent_parameters(),
                                    ['id' => $id,
                                     'component' => $component,
                                    ]);
        if($id && $component){
            if($component == 'goal') {
                $returndata = (new goals)->is_goal_mapped($id);
            } else if($component == 'board') {
                $returndata = (new goals)->is_board_mapped($id);
            } else if($component == 'class') {
                $returndata = (new goals)->is_class_mapped($id);
            }
            $data = new stdClass();
            $data->candelete = $returndata;
            return $data;
        } else {
            throw new moodle_exception('Error while getting the data');
        }
    }   
    public static function candeletecomponent_returns() {
        return new external_single_structure([
            'candelete' => new external_value(PARAM_INT, 'candelete'),

        ]);
    }
    public static function deletecomponent_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT,'id',0),
                'component' => new external_value(PARAM_RAW, 'component'),
            )
        );
    }
    public static  function deletecomponent($id,$component){
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::deletecomponent_parameters(),
            array('id'=>$id,'component'=>$component)
        );
        self::validate_context($systemcontext);
        if ($id && $component) {
           (new goals)->remove_component($id,$component);
        } else {
          throw new moodle_exception('Error in submission');
        }
        return true;    
    }
    public static function deletecomponent_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function create_hierarchies_parameters(){
        return new external_function_parameters(
            array(
                'old_id' => new external_value(PARAM_RAW, 'old_id', 0),
                'id' => new external_value(PARAM_INT, 'id', 0),
                'type' => new external_value(PARAM_RAW, 'type'),
                'referenceid' => new external_value(PARAM_INT, 'referenceid'),
                'name' => new external_value(PARAM_RAW, 'referenceid'),
                'code' => new external_value(PARAM_RAW, 'code'),
                'description' => new external_value(PARAM_RAW, 'description', ''),
                'image' => new external_value(PARAM_RAW, 'image', ''),
                'tags' => new external_value(PARAM_RAW, 'tags', []),
                'is_active' => new external_value(PARAM_RAW, 'is_active', 1),
            )
        );
    }
    public static  function create_hierarchies($old_id = 0, $id, $type, $referenceid, $name, $code, $description='', $image='', $tags = '', $is_active=0) {
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::create_hierarchies_parameters(), 
            [
                'old_id' => $old_id,
                'id' => $id,
                'type' => $type,
                'referenceid' => $referenceid,
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'image' => $image,
                'tags' => $tags,
                'is_active' => $is_active,
            ]
        );
        $data = (object)$params;
        $data->description = ['text'=> $data->description];
        $id = $data->id;

        switch ($type) {
            case 'Goal':
                if ($id > 0) {
                    $id = (new local_goals\controller)->update_goals($data);
                } else {
                    $id = (new local_goals\controller)->create_goals($data);
                }
              break;
            case 'Board':
                $data->goalid = $data->referenceid;
                if ($id > 0) {
                    $id = (new local_goals\controller)->update_boards($data);
                } else {
                    $id = (new local_goals\controller)->create_boards($data);
                }
              break;
            case 'Class':
                $data->boardid = $data->referenceid;
                if ($id > 0) {
                    $id = (new local_goals\controller)->update_class($data);
                } else {
                    $id = (new local_goals\controller)->create_class($data);
                }
                break;
            case 'Course':
                $data->classessid = $data->referenceid;
                if ($id > 0) {
                    $id = (new local_goals\controller)->update_subject($data);
                } else {
                    $id = (new local_goals\controller)->create_subject($data);
                }
                break;
          }

        return ['id' => $id];    
    }
    public static function create_hierarchies_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'id'),
        ]);
    }

    public static function delete_hierarchy_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'type' => new external_value(PARAM_RAW, 'type'),
            )
        );
    }
    public static  function delete_hierarchy($id, $type) {
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::delete_hierarchy_parameters(), 
            [
                'id' => $id,
                'type' => $type,
            ]
        );

        $result = (new local_goals\controller)->delete_hierarchy($params);

        return ['result' => $result];    
    }
    public static function delete_hierarchy_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'result'),
        ]);
    }
}
