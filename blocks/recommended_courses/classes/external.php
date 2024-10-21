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
 * @package    block_recommended_courses
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir. '/externallib.php');
require_once($CFG->dirroot . '/blocks/recommended_courses/lib.php');
class block_recommended_courses_external extends external_api {
	/**
     * Describes the parameters for recommended_courses_view webservice.
     * @return external_function_parameters
     */
    public static function recommendedcourses_view_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }

    /**
     * Describes the data for recommended_courses_view webservice.
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return external_function data.
     */
    public static function recommendedcourses_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        require_login();
        $PAGE->set_url('/blocks/recommended_courses/courseview.php');
        $PAGE->set_context($contextid);
        $params = self::validate_parameters(
            self::recommendedcourses_view_parameters(),
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
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $listofrecommendedcourses = listof_recommended_courses($stable, $filtervalues);
        $totalcount = $listofrecommendedcourses['totalcount'];
        $data = $listofrecommendedcourses['data'];

        return [
            'totalcount' => $totalcount,
            'records' => $data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

    }

    /**
     * Returns description of method result value.
     */
    public static function recommendedcourses_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of skills in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'courseid'),
                    'coursename' => new external_value(PARAM_RAW, 'coursename'),
                    'startdate' => new external_value(PARAM_RAW, 'startdate'),
                    'enddate' => new external_value(PARAM_RAW, 'enddate'),
                    'courseimage' => new external_value(PARAM_RAW, 'courseimage'),
                    'cfgwwwroot' => new external_value(PARAM_RAW, 'cfgwwwroot'),
                ])
            )
        ]);
    }
}