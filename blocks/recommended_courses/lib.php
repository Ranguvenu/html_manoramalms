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
 * Callback implementations for Notification
 *
 * @package    block_recommended_courses
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core_course\external\course_summary_exporter;

defined('MOODLE_INTERNAL') || die();

/**
 * To get the list of recommended courses.
 * @param $stable for start limit and end limit.
 * @param $filtervalues user search values.
 * @return array of data to the external function.
 */
function listof_recommended_courses($stable, $filtervalues) {
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
// echo "<pre>";
// print_r($stable);echo "<pre>";
// print_r($filtervalues);exit;
	$filteredcoursesparams = [];
    $myactivetagsql = "SELECT DISTINCT(ti.tagid)
                         FROM {tag_instance} AS ti
                         JOIN {enrol} AS e on e.courseid = ti.itemid
                         JOIN {user_enrolments} AS ue ON e.id = ue.enrolid
                        WHERE ue.userid = {$USER->id} ";
    $myactivetags = $DB->get_fieldset_sql($myactivetagsql);

    if ($myactivetags) {
        list($tagsql, $tagparams) = $DB->get_in_or_equal($myactivetags, SQL_PARAMS_NAMED, 'tagids');
    } else {
        $tagsql = ' = 0 ';
        $tagparams = [];
    }
    $tagparams['userid'] = $USER->id;
    $tagparams['now'] = time();
    $countrecomendedsql = "SELECT count(c.id) ";
    $selectrecomendedsql = "SELECT c.* ";
    $fromrecomendedsql = " FROM {course} AS c ";

    $joinrecomendedsql = " JOIN {course_categories} AS cc ON c.category = cc.id
						   JOIN {tag_instance} AS ti ON ti.itemid = c.id AND ti.itemtype = 'course' AND ti.component = 'core' ";
	$whererecomendedsql = " WHERE ti.tagid $tagsql AND c.enddate > :now AND c.id NOT IN (
								SELECT e.courseid FROM {enrol} AS e
								  JOIN {user_enrolments} AS ue ON e.id = ue.enrolid
								 WHERE ue.userid = :userid
								) ";
	// Course name filter.
    // if (!empty($filtervalues->recommended_courses)) {
    //     $whererecomendedsql .= " AND c.fullname LIKE '%$filtervalues->recommended_courses%' ";
    // }
	if (!empty($filtervalues->recommended_courses)) {
        $filteredcourses = explode(',', $filtervalues->recommended_courses);
        $filteredcourses = array_filter($filteredcourses, function($value) {
            if ($value != '_qf__force_multiselect_submission') {
                return $value;
            }
        });
        
        if ($filteredcourses != NULL) {
	        list($filteredcoursessql, $filteredcoursesparams) = $DB->get_in_or_equal($filteredcourses, 
	                                                        SQL_PARAMS_NAMED, 'recommended_courses', true, false);
	        $whererecomendedsql .= " AND c.id $filteredcoursessql";
        }
    }

    // For global search.
    $searchparams = [];
    if (isset($filtervalues->search_query) && trim($filtervalues->search_query) != '') {
            $whererecomendedsql .= " AND (c.fullname LIKE :coursename) ";
            $searchparams = array(
                'coursename' => '%'.trim($filtervalues->search_query).'%',
            );
    }
    $tagparams = array_merge($tagparams, $searchparams, $filteredcoursesparams);
	$recomendedsql = $selectrecomendedsql . $fromrecomendedsql . $joinrecomendedsql . $whererecomendedsql;

	$countsql = $countrecomendedsql . $fromrecomendedsql . $joinrecomendedsql . $whererecomendedsql;

	// Total recommended courses count.
	$recomendedcount = $DB->count_records_sql($countsql, $tagparams);

	// Total recommended courses.
    $recomendedcourses = $DB->get_records_sql($recomendedsql, $tagparams, $stable->start, $stable->length);
    $count = 0;
    $data = array();
    foreach ($recomendedcourses as $courses) {
        // To get course image..
        $courseimage = course_summary_exporter::get_course_image($courses);
        if (!$courseimage) {
            $courseimage = $OUTPUT->get_generated_image_for_id($courses->id);
        }
   
        $data[$count]['courseid'] = $courses->id;
        $data[$count]['coursename'] = $courses->fullname;
        $data[$count]['startdate'] = userdate($courses->startdate, get_string('strftimedate', 'langconfig'));
        $data[$count]['enddate'] = userdate($courses->enddate, get_string('strftimedate', 'langconfig'));
        $data[$count]['courseimage'] = $courseimage;
        $data[$count]['cfgwwwroot'] = $CFG->wwwroot;

        $count++;
    }
    return [
    	'data' => $data,
    	'length' => count($data),
    	'totalcount' => $recomendedcount,
    ];
}

/**
 * To get the filter of recommended courses.
 * @param $mform form object.
 * @return filtered data.
 */
function recommended_courses_filter($mform) {
	global $DB, $USER;
    $myactivetags = $DB->get_fieldset_sql("SELECT DISTINCT(ti.tagid) FROM {tag_instance} AS ti 
        JOIN {enrol} AS e on e.courseid = ti.itemid
    JOIN {user_enrolments} AS ue ON  e.id = ue.enrolid WHERE ue.userid = {$USER->id} ");
    if ($myactivetags) {
        list($tagsql, $tagparams) = $DB->get_in_or_equal($myactivetags, SQL_PARAMS_NAMED, 'tagids');
    } else {
        $tagsql = ' = 0 ';
        $tagparams = [];
    }

    $tagparams['userid'] = $USER->id;
    $tagparams['now'] = time();
    $recomendedsql = "SELECT c.id, c.fullname FROM {course} AS c
                        JOIN {course_categories} AS cc ON c.category = cc.id
                        JOIN {tag_instance} AS ti ON ti.itemid = c.id AND ti.itemtype = 'course' AND ti.component = 'core'
                        WHERE ti.tagid $tagsql AND c.enddate > :now AND c.id NOT IN (SELECT e.courseid FROM {enrol} AS e   
                        JOIN {user_enrolments} AS ue ON e.id = ue.enrolid WHERE ue.userid = :userid) ORDER BY c.id DESC ";
    $courseslist = $DB->get_records_sql_menu($recomendedsql, $tagparams);

    $select = $mform->addElement('autocomplete', 'recommended_courses', '', $courseslist, array('placeholder' => get_string('course')));
    $mform->setType('recommended_courses', PARAM_RAW);
    $select->setMultiple(true);
}