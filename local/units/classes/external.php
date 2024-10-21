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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');
use local_units\local\units as units;
use local_units\controller as controller;
use cache;
use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use context_system;

require_once("{$CFG->dirroot}/course/externallib.php");

/**
 * local_units_external [description]
 */
class local_units_external extends external_api {
    /**
     * units view parameters [description]
     */
    public static function units_view_parameters() {
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
     * units_view [description]
     * @param [type] $options [description]
     * @param [type] $dataoptions [description]
     * @param [type] $contextid [description]
     * @param [type] $filterdata [description]
     * @param [type] $offset [description]
     * @param [type] $limit [description]
     */
    public static function units_view($options, $dataoptions, $contextid, $filterdata, $offset = 0, $limit = 0) {
        global $DB, $PAGE;
        require_login();
        $systemcontext = \context_system::instance();  
        // Parameter validation.
        $params = self::validate_parameters(
            self::units_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'contextid' => $contextid,
                'filterdata' => $filterdata,
                'offset' => $offset,
                'limit' => $limit,
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;

        $unit = new units();
        $unitslist = $unit->get_units(array(), $offset, $limit, $filtervalues);
  
        foreach ($unitslist as $units) {

            $subjectid = $DB->get_field_sql("SELECT courseid FROM {local_units} WHERE id = $units->id");
            $subject = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' (',sub.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh on lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 on lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 on lh1.parent = lh2.id AND lh2.depth = 1 WHERE 1=1 AND sub.courseid = $subjectid");
            $chapterdata = array();
            $chapters = $unit->get_chapters($units->id, ['id', 'name', 'code']); 
            if(!empty($chapters)){
               $unitsdeletehide = false;
            }else{
               $unitsdeletehide = true;
            }
            if(is_siteadmin() || has_capability('local/questions:qhdeleteaction', $systemcontext)){
            $deleteacap = true;
            }else{
            $deleteacap = false;
            }
            if(is_siteadmin() || has_capability('local/questions:qheditaction', $systemcontext)){
            $editacap = true;
            }else{
            $editacap = false;
            }
           foreach ($chapters as $chapter) {
                $topicsdata = array();
                if (empty($topicsdata)) {
                    $topics = $unit->get_topics($units->id, $chapter->id, ['id', 'name', 'code']);
                    if(!empty($topics)){
                       $chaptersdeletehide = false;
                    }else{
                      $chaptersdeletehide = true;
                    }
                    foreach ($topics as $topic) {
                        $conceptdata = array();
                          if (empty($conceptdata)) {
                            $concepts = $unit->get_concepts($units->id, $chapter->id,$topic->id, ['id', 'name', 'code']);
                            if(!empty($concepts)){
                              $topicsdeletehide = false;
                             }else{
                               $topicsdeletehide = true;
                             }
                            foreach ($concepts as $con) {
                                $conceptdata[] = ['conceptid' => $con->id,
                                                       'name' => $con->name ,
                                                       'code' => $con->code
                                                   ];
                            }
                        }
                        $topicsdata[] = ['topicid' => $topic->id,
                                           'name' => $topic->name ,
                                           'code' => $topic->code,
                                            'concepts' => $conceptdata,
                                            'topicsdeletehide' => $topicsdeletehide
                                           ];
                    }
                }
                $chapterdata[] = ['chapterid' => $chapter->id,
                                    'name' => $chapter->name,
                                    'code' => $chapter->code,
                                    'topics' => $topicsdata,
                                    'chaptersdeletehide' => $chaptersdeletehide
                                ];
            }

            $records['units'][] = ['unitid' => $units->id,
                                    'name' => $units->name,
                                    'hierarchypath'=>$subject,
                                    'code' => $units->code,
                                    'chapters' => $chapterdata,
                                    'unitsdeletehide' => $unitsdeletehide,
                                    'deleteacap' => $deleteacap,
                                    'editacap' => $editacap
                                ];
        }
        $records['length'] = count($unitslist);
        $totalcount = count($unit->get_units(array(), 0, 0, $filtervalues));
        $return = [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $records,
            'options' => $options,
            'dataoptions' => $dataoptions
        ];
        return $return;
    }

    /**
     * units view return [description]
     */
    public static function units_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'records' => new external_single_structure(
                    array(
                        'units' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                        'unitid' => new external_value(PARAM_RAW, 'id', VALUE_OPTIONAL),
                                        'hierarchypath' => new external_value(PARAM_RAW, 'hierarchypath', VALUE_OPTIONAL),
                                        'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                                        'code' => new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                                        'unitsdeletehide' => new external_value(PARAM_RAW, 'to hide delete button in units'),
                                        'deleteacap' => new external_value(PARAM_RAW, 'To delete hierarchy based on capability'),
                                        'editacap' => new external_value(PARAM_RAW, 'To edit hierarchy based on capability'),
                        'chapters' => new external_multiple_structure(
                                         new external_single_structure(
                                            array(
                                                'chapterid' => new external_value(PARAM_RAW, 'id'),
                                                 'name' => new external_value(PARAM_RAW, 'name'),
                                                 'code' => new external_value(PARAM_RAW, 'code'),
                                                  'chaptersdeletehide' => new external_value(PARAM_RAW, 'to hide delete button in chapters'),
                        'topics' => new external_multiple_structure(
                                                    new external_single_structure(
                                                        array(
                                                            'topicid' => new external_value(PARAM_RAW, 'id'),
                                                            'name' => new external_value(PARAM_RAW, 'name'),
                                                            'code' => new external_value(PARAM_RAW, 'code'),
                                                            'topicsdeletehide' => new external_value(PARAM_RAW, 'to hide delete button in topics'),
                        'concepts' => new external_multiple_structure(
                                                                new external_single_structure(
                                                                    array(
                                                                        'conceptid' => new external_value(PARAM_RAW, 'id'),
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
            if($component == 'unit') {
                $returndata = (new units)->is_unit_mapped($id);
            } else if($component == 'chapter') {
                $returndata = (new units)->is_chapter_mapped($id);
            }
            else if($component == 'topic') {
                $returndata = (new units)->is_topic_mapped($id);
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
           (new units)->remove_component($id,$component);
        } else {
          throw new moodle_exception('Error in submission');
        }
        return true;    
    }
    public static function deletecomponent_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }


}
