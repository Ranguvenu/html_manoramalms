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
 * studymaterial hierarchy
 *
 * This file defines the current version of the local_studymaterial Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_studymaterial
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');
use local_studymaterial\local\studymaterial as studymaterial;
use local_studymaterial\controller as controller;
use cache;
use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use context_system;

require_once("{$CFG->dirroot}/course/externallib.php");

/**
 * local_studymaterial_external [description]
 */
class local_studymaterial_external extends external_api {
    /**
     * studymaterial view parameters [description]
     */
    public static function studymaterial_view_parameters() {
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
     * studymaterial_view [description]
     * @param [type] $options [description]
     * @param [type] $dataoptions [description]
     * @param [type] $contextid [description]
     * @param [type] $filterdata [description]
     * @param [type] $offset [description]
     * @param [type] $limit [description]
     */
    public static function studymaterial_view($options, $dataoptions, $contextid, $filterdata, $offset = 0, $limit = 0) {
        global $DB, $PAGE;
        require_login();

        // Parameter validation.
        $params = self::validate_parameters(
            self::studymaterial_view_parameters(),
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
        $dataoptionsarr = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $systemcontext = context_system::instance();
        $studymateriallist = (new studymaterial())->get_studymaterial(array(), $offset, $limit, $filtervalues,$dataoptionsarr->courseid);
        foreach ($studymateriallist as $studymaterial) {
            $records['studymaterial'][] = ['studymaterialid' => $studymaterial->id,
            'course' => $studymaterial->course,
            'name' => $studymaterial->name,
            'intro' => $studymaterial->intro,
            'introformat' => $studymaterial->introformat,
            'content' => $studymaterial->content,
            'contentformat' => $studymaterial->contentformat,
            'revision' => $studymaterial->revision,
            'timecreated' => $studymaterial->timecreated,
            'timemodified' => $studymaterial->timemodified,
            'editstudymaterial'=>(has_capability('local/studymaterial:edit', $systemcontext) || is_siteadmin()),
            'deletestudymaterial'=>(has_capability('local/studymaterial:delete', $systemcontext) || is_siteadmin()),
            'viewstudymaterial'=>(has_capability('local/studymaterial:view', $systemcontext) || is_siteadmin()),
        ];
    }
        $records['length'] = count($studymateriallist);
        $totalcount = count((new studymaterial())->get_studymaterial());
        $return = [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $records,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        // print_r($return);
        return $return;
    }

    /**
     * studymaterial view return [description]
     */
    public static function studymaterial_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'records' => new external_single_structure(
                    array(
                        'studymaterial' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'studymaterialid' => new external_value(PARAM_RAW, 'id', VALUE_OPTIONAL),
                                    'course' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                                    'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                                    'intro' => new external_value(PARAM_RAW, 'intro', VALUE_OPTIONAL),
                                    'introformat' => new external_value(PARAM_RAW, 'introformat', VALUE_OPTIONAL),
                                    'content' => new external_value(PARAM_RAW, 'content', VALUE_OPTIONAL),
                                    'contentformat' => new external_value(PARAM_RAW, 'contentformat', VALUE_OPTIONAL),
                                    // 'legacyfiles'=> new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                                    // 'legacyfileslast'=> new external_value(PARAM_RAW, 'code', VALUE_OPTIONAL),
                                    // 'display'=> new external_value(PARAM_RAW, 'display', VALUE_OPTIONAL),
                                    // 'displayoptions'=> new external_value(PARAM_RAW, 'displayoptions', VALUE_OPTIONAL),
                                    'revision'=> new external_value(PARAM_RAW, 'revision', VALUE_OPTIONAL),
                                    'timecreated'=> new external_value(PARAM_RAW, 'timecreated', VALUE_OPTIONAL),
                                    'timemodified'=> new external_value(PARAM_RAW, 'timemodified', VALUE_OPTIONAL),
                                    'editstudymaterial'=> new external_value(PARAM_RAW, 'Edit studymaterial capability', VALUE_OPTIONAL),
                                    'deletestudymaterial'=> new external_value(PARAM_RAW, 'delete studymaterial capability', VALUE_OPTIONAL),
                                    'viewstudymaterial'=> new external_value(PARAM_RAW, 'views tudymaterial capability', VALUE_OPTIONAL),
                                )
                                )
                            , '', VALUE_OPTIONAL),
                            'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                    )
                )
        ]);
    }

    public static function deletestudymaterial_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT,'id',0),
            )
        );
    }
    public static  function deletestudymaterial($id){
        $systemcontext = context_system::instance();
        $params=self::validate_parameters(
            self::deletestudymaterial_parameters(),
            array('id'=>$id)
        );
        self::validate_context($systemcontext);
        if ($id) {
           (new studymaterial)->remove_studymaterial($id);
        } else {
          throw new moodle_exception('Error in submission');
        }
        return true;    
    }
    public static function deletestudymaterial_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

   
}
