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
 * renderer  for 'block_recommended_courses'.
 *
 * @package   block_recommended_courses
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// use plugin_renderer_base;
use core_course\external\course_summary_exporter;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir .'/externallib.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/../../config.php');

/**
 * block_recommended_courses_renderer
 */
class block_recommended_courses_renderer extends plugin_renderer_base
{
    /**
     * [render_recommended_courses description]
     */
    public function render_recommended_courses(){

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;
        $context = context_system::instance();
        $recomended_course = $this->get_recommended_courses();
        $count = 0;
        $params = array();
        foreach($recomended_course as $courses){
            
            $courseimage = course_summary_exporter::get_course_image($courses);
            if (!$courseimage) {
                $courseimage = $OUTPUT->get_generated_image_for_id($courses->id);
            }
       
            $params[$count]['courseid'] = $courses->id;
            $params[$count]['coursename'] = $courses->fullname;
            $params[$count]['startdate'] = userdate($courses->startdate, get_string('strftimedate', 'langconfig'));
            $params[$count]['enddate'] = userdate($courses->enddate, get_string('strftimedate', 'langconfig'));
            $params[$count]['courseimage'] = $courseimage;
  
            $count++;
        }
        $countrecords = count($params);
        $viewmore = false;
        if ($countrecords >= 5) {
            $viewmore = true;
        }
        $data = array(
            "rcursedetails" => $params,
            "viewmore" => $viewmore,
            
        );
        
        return  $this->render_from_template('block_recommended_courses/view', $data);
    }

    /**
     * [get_recommended_courses description]
     */
    public function get_recommended_courses(){

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;
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
        $recomendedsql = "SELECT c.* FROM {course} AS c
                            JOIN {course_categories} AS cc ON c.category = cc.id
                            JOIN {tag_instance} AS ti ON ti.itemid = c.id AND ti.itemtype = 'course' AND ti.component = 'core'
                            WHERE ti.tagid $tagsql AND c.enddate > :now AND c.id NOT IN (SELECT e.courseid FROM {enrol} AS e   
                            JOIN {user_enrolments} AS ue ON e.id = ue.enrolid WHERE ue.userid = :userid) ORDER BY c.id DESC ";
        $recomended = $DB->get_records_sql($recomendedsql, $tagparams, 0, 5);  
        return $recomended;
    }

    /**
     * [get_recommended_courses list with pagination]
     */
    public function list_of_recommended_courses($filter = false) {
        global $USER;
        $systemcontext = context_system::instance();
        $options = array(
            'targetID' => 'recommended_courses_view',
            'perPage' => 5,
            'cardClass' => 'col-md-12 col-12',
            'viewType' => 'card'
        );
        $options['methodName'] = 'block_recommended_courses_recommended_courses_view';
        $options['templateName'] = 'block_recommended_courses/courseview'; 
        $options = json_encode($options);
        $dataoptions = json_encode(array('userid' => $USER->id, 'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());
        $context = [
                'targetID' => 'recommended_courses_view',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if ($filter) {
            return $context;
        } else {
            return $this->render_from_template('block_recommended_courses/cardPaginate', $context);
        }
    }
}

    

