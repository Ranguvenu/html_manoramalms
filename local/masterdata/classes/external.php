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

require_once($CFG->libdir.'/externallib.php');
use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use context_system;
/**
 * Class external
 *
 * @package    local_masterdata
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */class local_masterdata_external extends external_api {

    public static function viewquizattempts_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied')
        ]);
    }

    public static function viewquizattempts($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $DB, $PAGE, $CFG;
        require_login();
         $systemcontext = context_system::instance();
        $params = self::validate_parameters(
            self::viewquizattempts_parameters(),
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
        $data = (new \local_masterdata\questionslib())->get_quiz_attempts_list($stable, $filtervalues, $dataoptions);
        $totalcount = $data['totalattempts'];
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'url' => $CFG->wwwroot,
            'nodata' => true,
        ];
    }

  /**
   * Returns description of method result value
   * @return external_description
   */
    public static function viewquizattempts_returns() {
        return new external_single_structure([
          'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
          'url' => new external_value(PARAM_RAW, 'url'),
          'nodata' => new external_value(PARAM_BOOL, 'nodata'),
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
                                'attemptid' => new external_value(PARAM_INT, 'attemptid'),
                                'cmid' => new external_value(PARAM_INT, 'cmid'),
                                'fullname' => new external_value(PARAM_RAW, 'fullname'),
                                'email' => new external_value(PARAM_RAW, 'email'),
                                'phone' => new external_value(PARAM_RAW, 'phone'),
                                'attemptstartdate' => new external_value(PARAM_RAW, 'attemptstartdate'),
                                'timetaken' => new external_value(PARAM_RAW, 'timetaken'),
                                'viewattempturl' => new external_value(PARAM_RAW, 'viewattempturl'),
                            )    
                        )
                    ),                   
                    'request_view' => new external_value(PARAM_INT, 'request_view', VALUE_OPTIONAL),
                    'totalattempts' => new external_value(PARAM_INT, 'totalattempts', VALUE_OPTIONAL),
                    'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                )
            )
        ]);
    }


}
