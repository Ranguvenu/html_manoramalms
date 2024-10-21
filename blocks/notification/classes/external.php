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


defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/notification/lib.php');

use \core_external\external_api;
use core_course_external;
use advanced_testcase;
use block_recentlyaccesseditems\external;
use context_system;

/**
 * Class external
 *
 * @package    block_notification
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_notification_external extends external_api
{
    /**
     * Get data for the daily calendar view.
     *
     * @param   int     $year The year to be shown
     * @param   int     $month The month to be shown
     * @param   int     $day The day to be shown
     * @param   int     $courseid The course to be included
     * @return  array
     */
    public static function get_calendar_details($year, $month, $day, $courseid, $categoryid)
    {
        global $DB, $USER, $PAGE;

        // Parameter validation.
        $params = self::validate_parameters(self::get_calendar_details_parameters(), [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'courseid' => $courseid,
            'categoryid' => $categoryid,
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        $data  = block_notification_calendar_events($year, $month, $day, $courseid, $categoryid);

        return $data;
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_calendar_details_parameters()
    {
        return new external_function_parameters(
            [
                'year' => new external_value(PARAM_INT, 'Year to be viewed', VALUE_REQUIRED),
                'month' => new external_value(PARAM_INT, 'Month to be viewed', VALUE_REQUIRED),
                'day' => new external_value(PARAM_INT, 'Day to be viewed', VALUE_REQUIRED),
                'courseid' => new external_value(PARAM_INT, 'Course being viewed', VALUE_DEFAULT, SITEID, NULL_ALLOWED),
                'categoryid' => new external_value(PARAM_INT, 'Category being viewed', VALUE_DEFAULT, null, NULL_ALLOWED),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return \core_external\external_description
     */
    public static function get_calendar_details_returns()
    {
        return new external_single_structure([
            'events' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT,'id',VALUE_REQUIRED),
                        'name' => new external_value(PARAM_TEXT,'name',VALUE_OPTIONAL),
                        'description' => new external_value(PARAM_RAW,'description',VALUE_OPTIONAL),
                        'descriptionformat' =>  new external_value(PARAM_INT,'descriptionformat',VALUE_OPTIONAL),
                        'location' => new external_value(PARAM_RAW,'location',VALUE_OPTIONAL),
                        'categoryid' => new external_value(PARAM_INT,'categoryid',VALUE_OPTIONAL),
                        'groupid' =>  new external_value(PARAM_INT,'groupid',VALUE_OPTIONAL),
                        'userid' =>  new external_value(PARAM_INT,'userid',VALUE_OPTIONAL),
                        'repeatid' =>  new external_value(PARAM_INT,'repeatid',VALUE_OPTIONAL),
                        'eventcount' =>  new external_value(PARAM_INT,'eventcount',VALUE_OPTIONAL),
                        'component' =>  new external_value(PARAM_COMPONENT,'groupid',VALUE_OPTIONAL),
                        'modulename' => new external_value(PARAM_TEXT,'modulename',VALUE_OPTIONAL),
                        // 'activityname' => new external_value(PARAM_TEXT,'activityname',VALUE_OPTIONAL),
                        // 'activitystr' => new external_value(PARAM_TEXT,'activitystr',VALUE_OPTIONAL),
                        'instance' => new external_value(PARAM_INT,'instance',VALUE_OPTIONAL),
                        'eventtype' => new external_value(PARAM_TEXT,'eventtype',VALUE_OPTIONAL),
                        'timestart' => new external_value(PARAM_INT,'timestart',VALUE_OPTIONAL),
                        'timeend' => new external_value(PARAM_INT,'timeend',VALUE_OPTIONAL),
                        'timeduration' => new external_value(PARAM_INT,'timeduration',VALUE_OPTIONAL),
                        'timesort' => new external_value(PARAM_INT,'timesort',VALUE_OPTIONAL),
                        // 'timeusermidnight' => new external_value(PARAM_INT,'timeusermidnight',VALUE_OPTIONAL),
                        'visible' => new external_value(PARAM_INT,'visible',VALUE_OPTIONAL),
                        'timemodified' =>new external_value(PARAM_INT,'timemodified',VALUE_OPTIONAL),
                        // 'overdue' => new external_value(PARAM_BOOL,'overdue',VALUE_OPTIONAL),
                        'typeofevent' => new external_value(PARAM_RAW,'typeofevent',VALUE_OPTIONAL),
                        'iszoom' => new external_value(PARAM_BOOL,'iszoom',VALUE_OPTIONAL),
                        'isquiz' => new external_value(PARAM_BOOL,'isquiz',VALUE_OPTIONAL),
                        'isforum' => new external_value(PARAM_BOOL,'isforum',VALUE_OPTIONAL),
                        'isassign' => new external_value(PARAM_BOOL,'isassign',VALUE_OPTIONAL),
                        'lessonplanurl' =>new external_value(PARAM_URL,'lessonplanurl',VALUE_OPTIONAL),
                        'classnotesurl' =>new external_value(PARAM_URL,'classnotesurl',VALUE_OPTIONAL),
                        'linkurl' =>new external_value(PARAM_URL,'linkurl',VALUE_OPTIONAL),
                        'recordingsurl' =>new external_value(PARAM_URL,'recordingsurl',VALUE_OPTIONAL),
                        'viewpageurl' =>new external_value(PARAM_URL,'viewpageurl',VALUE_OPTIONAL),
                        'qcount' =>new external_value(PARAM_INT,'qcount',VALUE_OPTIONAL),
                        'qmarks' =>new external_value(PARAM_INT,'qmarks',VALUE_OPTIONAL),
                        'duration' =>new external_value(PARAM_RAW,'duration',VALUE_OPTIONAL),
                        'url' =>new external_value(PARAM_RAW,'url',VALUE_OPTIONAL),
                    )
                )
            )
        ]);
    }
}
