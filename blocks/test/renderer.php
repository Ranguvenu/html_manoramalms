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
 * renderer  for 'block_test'.
 *
 * @package   block_test
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');

/**
 * block_test_renderer
 */
class block_test_renderer extends plugin_renderer_base
{
    /**
     * [render_test description]
     */
    public function render_test(){

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

      $enrolledcourses = $this->enrolled_tests();  
      foreach($enrolledcourses as $course){
            $courseparams = [];
            $courseparams['url'] =  $course->id;
            $courseparams['coursename'] = $course->fullname;
            $courseparams['coursestartdate'] = date("jS M Y",$course->startdate);
            $courseparams['courseenddate'] = date("jS M Y",$course->enddate);
            $courseparams['courseimage'] = $this->course_summary_files($course);
            $courses[] = $courseparams;
        }
        $params['courses'] = $courses;
        // print_object($params);die;
        return  $this->render_from_template('block_test/view', $params); 
    }
    
    public function enrolled_tests(){

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;
       
        $sql = "SELECT c.* FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packagecourses} AS lpc ON lpc.courseid = c.id
        WHERE ue.userid=:userid AND cc.visible=1 AND lpc.package_type = 2 ";//2 for test only packages
        $courses = $DB->get_records_sql($sql,['userid'=>$USER->id]);
        return $courses;
    }
    function course_summary_files($courserecord){
        global $DB, $CFG, $OUTPUT;
        if ($courserecord instanceof stdClass) {
           // require_once($CFG->libdir . '/coursecatlib.php');
            $courserecord = new core_course_list_element($courserecord);
        }
        
        // set default course image
        //$url = $OUTPUT->pix_url('/course_images/courseimg', 'local_costcenter');
        foreach ($courserecord->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage){
				$url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' .
					$file->get_component() . '/' .$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
			}else{
				$url = $OUTPUT->image_url('courseimg', 'block_test');//send_file_not_found();
			}
        }
		if(empty($url)){
			$url = $OUTPUT->image_url('courseimg', 'block_test');//send_file_not_found();
		}
        return $url;
    }
}

    

