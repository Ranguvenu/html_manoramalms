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
 * renderer  for 'block_mycourses'.
 *
 * @package   block_mycourses
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
/**
 * block_mycourses_renderer
 */
class block_mycourses_renderer extends plugin_renderer_base
{
    /**
     * [render_mycourses description]
     */
    public function render_mycourses()
    {
        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

        $category = $this->get_category();
        $count = 0;
        $params = array();
        $cids = [];
        $catimg = $OUTPUT->image_url('cat', 'block_mycourses');
        $multicourses = $singlecourses = [];
        // $enrolled = enrol_get_my_courses();
        foreach ($category as $cat) {
            // if (is_siteadmin()) {
            //     $cat = core_course_category::get($cat->id);
            //     $cat_courses = $cat->get_courses();
            //     $courses = [];
            //     foreach ($cat_courses as $course) {
            //         $courseparams = [];
            //         $courseparams['url'] = $course->id;
            //         $courseparams['coursename'] = $course->fullname;
            //         $courses[] = $courseparams;
            //     }
            // } else {

                
                $courses = [];
                $percentage_of_completion = 0;
                $modules_in_course = 0;
                $completed_moduleincourse = 0;
                $total_completed_activity1 = [];
                $enrolled_courses = $this->get_my_enrolled_courses($USER->id, $cat->id, true);
                $viewfreetrail = true;
                foreach ($enrolled_courses as $ecourse) {
                    $check = core_completion\progress::get_course_progress_percentage($ecourse, $USER->id);
                    $course_completed = 0;
                    $courseparams = [];
                    $courseparams['url'] =  $ecourse->id;
                    $courseparams['coursename'] = $ecourse->fullname;
                    $courseparams['courseprogress'] = round($check);
                    $courseparams['courseenrolstartdate'] = $ecourse->enroltimestart > 0 ? date("jS M Y", $ecourse->enroltimestart) : 'N/A';
                    $courseparams['courseenrolenddate'] = $ecourse->enroltimeend > 0 ? date("jS M Y", $ecourse->enroltimeend) : 'N/A';
                    $courseparams['courseimage'] = $this->course_summary_files($ecourse);
                    $enrolments[$ecourse->id][] = $ecourse->enrol;
                    if ($viewfreetrail && $ecourse->enrol != 'trial') {
                        $viewfreetrail = false;
                    }
                    if (isset($courses[$ecourse->id])) {
                        if ($timeenddate[$ecourse->id] < $ecourse->enroltimeend) {
                            $timeenddate[$ecourse->id] = $ecourse->enroltimeend;
                        }
                    } else {
                        $courses[$ecourse->id] = $courseparams;
                        $timeenddate[$ecourse->id] = $ecourse->enroltimeend;
                    }
                }
            $categoryprogress = $this->get_category_progress($cat->id);
            if (filter_var($cat->image, FILTER_VALIDATE_URL)) {
                $catimg_url = $cat->image;
            }else{
                $catimg_url = $catimg->out();
            }
            if (count($courses) == 1) {
                $params[$count]['singlecourse'] = true;
            } else {
                $params[$count]['singlecourse'] = false;

                // foreach ($courses as $ckey => $cvalue) {
                //     $cids[] = $cvalue['url'];
                // }
                // $sql = "SELECT ue.timeend
                //           FROM {user_enrolments} ue
                //           JOIN {enrol} e ON e.id = ue.enrolid
                //          WHERE ue.userid = ? ";
                // if (!empty($cids)) {
                //     $courseids = implode(',', $cids);
                //     $sql .= " AND e.courseid IN ($courseids)";
                // }
                // $timeenddate = $DB->get_fieldset_sql($sql, [$USER->id]);

                // $freetrailsql = "SELECT e.enrol
                //           FROM {user_enrolments} ue
                //           JOIN {enrol} e ON e.id = ue.enrolid
                //          WHERE ue.userid = ? ";
                // if (!empty($cids)) {
                //     $courseids = implode(',', $cids);
                //     $freetrailsql .= " AND e.courseid IN ($courseids)";
                // }
                // $getfreetrail = $DB->get_fieldset_sql($freetrailsql, [$USER->id]);

            }
            if (!empty($timeenddate)) {
                $expirydateinunix = max($timeenddate);
                $expirydate = date("jS M Y", $expirydateinunix);
            } else {
                $expirydate = 'N/A';
            }
            
            //  if (!empty($getfreetrail) && !empty($timeenddate)) {
            //     $freetrial = max($getfreetrail);
            //    if($freetrial == "trial"){
            //     $daysdiff = max($timeenddate);
            //         $viewfreetrail = ($daysdiff - time())/86400;
            //          $viewfreetrail = ceil($viewfreetrail);
            //    }else{
            //          $viewfreetrail = false;
            //    }
            // } else {
            //     $viewfreetrail = false;
            // }
            $freetrial = 0;
            if ($viewfreetrail && !empty($timeenddate)) {
                $freetrialdays = ceil((max($timeenddate) - time())/86400);
            }
            $params[$count]['viewfreetrail'] = $viewfreetrail;
            $params[$count]['freetrialdays'] = $freetrialdays;
            $params[$count]['courses'] = array_values($courses);
            $params[$count]['categoryid'] = $cat->id;
            $params[$count]['categoryname'] = $cat->name;
            $params[$count]['getfreetrail'] = $viewfreetrail;
            $params[$count]['categoryprogress'] = round($categoryprogress);
            $params[$count]['coursecount'] = $cat->coursecount;
            $params[$count]['validtill'] = $expirydate;
            $params[$count]['catimg_url'] = $catimg_url;
            if ($cat->coursecount == 1) {
                $singlecourses[] = $params[$count];
            } else {
                $multicourses[] = $params[$count];
            }
            $count++;
        }
        $returnparams = array_merge($multicourses, $singlecourses); // To display multiple courses first and then single courses.

        $data = array(
            "categorydetails" => $returnparams,
        );

        return  $this->render_from_template('block_mycourses/view',  $data);
    }
    public function get_my_enrolled_courses($userid, $catgeoryid, $onlyactive = true){
        global $DB;
        $enrolledcoursesql = "SELECT ue.id AS userenrolid, ue.timeend AS enroltimeend, ue.timestart AS enroltimestart, e.enrol,  c.* FROM {course} c 
        JOIN {enrol} e ON e.courseid = c.id 
        JOIN {user_enrolments} ue on ue.enrolid = e.id 
        WHERE ue.userid = :userid AND c.category = :catgeoryid";
        $params = ['userid' => $userid, 'catgeoryid' => $catgeoryid];
        if ($onlyactive) {
            $enrolledcoursesql .= " AND (ue.timestart = 0 OR ue.timestart < :currenttime1) AND (ue.timeend = 0 OR ue.timeend > :currenttime2) ";
            $params['currenttime1'] = $params['currenttime2'] = time();
        }
        return $DB->get_records_sql($enrolledcoursesql, $params);
    }
    /**
     * [get_category description]
     */
    public function get_category()
    {

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

        // added hierarchy table inclusion as we do not get courses listed without creating the package
        $category = $DB->get_records_sql("SELECT cc.id,cc.name,lp.valid_to,count(c.id) as coursecount, lh.image FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packages} AS lp on lp.categoryid = cc.id 
        JOIN {local_hierarchy} AS lh on lh.categoryid = cc.id 
        WHERE ue.userid = :userid AND ue.status = 0 AND ue.timestart < :timenow1 AND (ue.timeend > :timenow2 OR ue.timeend = 0)  AND cc.visible=1 GROUP BY cc.id,cc.name ", ['userid' => $USER->id, 'timenow1' => time(), 'timenow2' => time()]);
        return $category;
    }
        /**
     * [get_completed_packages]
     */
    public function get_completed_package($userid)
    {

        global $DB, $USER;
        if(!$userid){
            $userid = $USER->id;
        }
        // added hierarchy table inclusion as we do not get courses listed without creating the package
        $package = $DB->get_records_sql("SELECT cc.id,lp.name,lp.valid_to,count(c.id) as coursecount FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packages} AS lp on lp.categoryid = cc.id 
        WHERE ue.userid = :userid AND ue.status = 0 AND  lp.valid_to < :timenow2   AND cc.visible=1 
        GROUP BY cc.id,lp.name ", ['userid' => $userid,  'timenow2' => time()]);
       return $package;
    }

        /**
     * [get_inprogress_packages]
     */
    public function get_inprogress_packages($userid)
    {
        global $DB, $USER;
       
        if(!$userid){
            $userid = $USER->id;
        }

        // added hierarchy table inclusion as we do not get courses listed without creating the package
        $package = $DB->get_records_sql("SELECT cc.id,lp.name,lp.valid_to,count(c.id) as coursecount FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packages} AS lp on lp.categoryid = cc.id 
        WHERE ue.userid = :userid AND ue.status = 0 AND lp.valid_from < :timenow1 AND lp.valid_to > :timenow2   AND cc.visible=1 GROUP BY cc.id,lp.name ", ['userid' => $userid, 'timenow1' => time(), 'timenow2' => time()]);
        return $package;
    }
    /**
     * [get_category_progress description]
     */
    public function get_category_progress($catid)
    {
        global $DB, $USER;
        $enrolled = $DB->get_records_sql("SELECT e.courseid,c.* FROM {user_enrolments} ue 
                JOIN {enrol} e ON e.id = ue.enrolid 
                JOIN {course} AS c ON c.id = e.courseid 
                JOIN {course_categories} AS cc ON c.category = cc.id 
                WHERE ue.userid=$USER->id AND cc.id=$catid");
        $coursetot = count($enrolled);
        //print_r( $coursetot);
        $courses = [];
        $percentage_of_completion = 0;
        $modules_in_course = 0;
        $completed_moduleincourse = 0;

        foreach ($enrolled as $ecourse) {
            $courses[] = core_completion\progress::get_course_progress_percentage($ecourse, $USER->id);
            $total_cou = array_sum($courses);
            $percentage_of_completion = ($total_cou / $coursetot);
        }


        return $percentage_of_completion;
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
				$url = $OUTPUT->image_url('courseimg', 'block_mycourses');//send_file_not_found();
			}
        }
		if(empty($url)){
			$url = $OUTPUT->image_url('courseimg', 'block_mycourses');//send_file_not_found();
		}
        return $url;
    }
    // To get inprogress packages.
    public function render_inprogress_packages($userid)
    {
        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

        $category = $this->get_inprogress_packages($userid);
        $count = 0;
        $params = array();
        $cids = [];
        $catimg = $OUTPUT->image_url('cat', 'block_mycourses');
        foreach ($category as $cat) {
                // $enrolled = enrol_get_my_courses();
                $enrolled = $this->get_my_enrolled_courses($USER->id, $cat->id, true);
                $courses = [];
                foreach ($enrolled as $ecourse) {
                    $check = core_completion\progress::get_course_progress_percentage($ecourse, $USER->id);
                    $courseendate = $DB->get_field('course','enddate',['id'=>$ecourse->id]);

                    if ($ecourse->category == $cat->id) {
                        $course_completed = 0;
                        $courseparams = [];
                        $courseparams['url'] =  $ecourse->id;
                        $courseparams['coursename'] = $ecourse->fullname;
                        $courseparams['courseprogress'] = round($check);
                        $courseparams['courseenrolstartdate'] = $ecourse->enroltimestart > 0 ? date("jS M Y", $ecourse->enroltimestart) : 'N/A';

                        $courseparams['courseenrolenddate'] = $ecourse->enroltimeend > 0 ? date("jS M Y", $ecourse->enroltimeend) : 'N/A';
                        $courseparams['coursestartdate'] = date("jS M Y", $ecourse->startdate);
                        $courseparams['courseenddate'] = date("jS M Y", $courseendate);
                        $courseparams['courseimage'] = $this->course_summary_files($ecourse);
                        $courses[] = $courseparams;
                    }
                }
        
            $categoryprogress = $this->get_category_progress($cat->id);
            if (filter_var($cat->image, FILTER_VALIDATE_URL)) {
                $catimg_url = $cat->image;
            }else{
                $catimg_url = $catimg->out();
            }
            if ($cat->coursecount == 1) {
                $params[$count]['singlecourse'] = true;
            } else {
                $params[$count]['singlecourse'] = false;

                foreach ($courses as $ckey => $cvalue) {
                    $cids[] = $cvalue['url'];
                }
                $sql = "SELECT ue.timeend
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE ue.userid = ? ";
                if (!empty($cids)) {
                    $courseids = implode(',', $cids);
                    $sql .= " AND e.courseid IN ($courseids)";
                }
                $timeenddate = $DB->get_fieldset_sql($sql, [$USER->id]);
            }
            if (!empty($timeenddate)) {
                $expirydateinunix = max($timeenddate);
                $expirydate = date("jS M Y", $expirydateinunix);
            } else {
                $expirydate = 'N/A';
            }
            $params[$count]['courses'] = $courses;
            $params[$count]['categoryid'] = $cat->id;
            $params[$count]['categoryname'] = $cat->name;
            $params[$count]['categoryprogress'] = round($categoryprogress);
            $params[$count]['coursecount'] = $cat->coursecount;
            $params[$count]['validtill'] =  $expirydate;
            $params[$count]['catimg_url'] = $catimg_url;
            $count++;
        }

        $data = array(
            "categorydetails" => $params,
        );

        return  $this->render_from_template('block_mycourses/view',  $data);
    }

    // To get completed packages.
    public function render_completed_packages($userid)
    {
        global $DB, $USER, $OUTPUT, $CFG, $PAGE;
        $category = $this->get_completed_package($userid);
        $count = 0;
        $params = array();
        $cids = [];
        $catimg = $OUTPUT->image_url('cat', 'block_mycourses');

        foreach ($category as $cat) {
                // $enrolled = enrol_get_my_courses();
                $enrolled = $this->get_my_enrolled_courses($USER->id, $cat->id, true);
                $courses = [];
                foreach ($enrolled as $ecourse) {
                    $check = core_completion\progress::get_course_progress_percentage($ecourse, $USER->id);
                    $courseendate = $DB->get_field('course','enddate',['id'=>$ecourse->id]);
                    if ($ecourse->category == $cat->id ) {
                        $course_completed = 0;
                        $courseparams = [];
                        $courseparams['url'] =  $ecourse->id;
                        $courseparams['coursename'] = $ecourse->fullname;
                        $courseparams['courseprogress'] = round($check);

                        $courseparams['courseenrolstartdate'] = $ecourse->enroltimestart > 0 ? date("jS M Y", $ecourse->enroltimestart) : 'N/A';

                        $courseparams['courseenrolenddate'] = $ecourse->enroltimeend > 0 ? date("jS M Y", $ecourse->enroltimeend) : 'N/A';
                        $courseparams['coursestartdate'] = date("jS M Y", $ecourse->startdate);
                        $courseparams['courseenddate'] = date("jS M Y", $courseendate);
                        $courseparams['courseimage'] = $this->course_summary_files($ecourse);
                        $courses[] = $courseparams;
                    }

                }

            $categoryprogress = $this->get_category_progress($cat->id);
            if (filter_var($cat->image, FILTER_VALIDATE_URL)) {
                $catimg_url = $cat->image;
            }else{
                $catimg_url = $catimg->out();
            }
            if ($cat->coursecount == 1) {
                $params[$count]['singlecourse'] = true;
            } else {
                $params[$count]['singlecourse'] = false;

                foreach ($courses as $ckey => $cvalue) {
                    $cids[] = $cvalue['url'];
                }
                $sql = "SELECT ue.timeend
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE ue.userid = ? ";
                if (!empty($cids)) {
                    $courseids = implode(',', $cids);
                    $sql .= " AND e.courseid IN ($courseids)";
                }
                $timeenddate = $DB->get_fieldset_sql($sql, [$USER->id]);
            }
            if (!empty($timeenddate)) {
                $expirydateinunix = max($timeenddate);
                $expirydate = date("jS M Y", $expirydateinunix);
            } else {
                $expirydate = 'N/A';
            }
            $params[$count]['courses'] = $courses;
            $params[$count]['categoryid'] = $cat->id;
            $params[$count]['categoryname'] = $cat->name;
            $params[$count]['categoryprogress'] = round($categoryprogress);
            $params[$count]['coursecount'] = $cat->coursecount;
            $params[$count]['validtill'] = $expirydate;
            $params[$count]['catimg_url'] = $catimg_url;
            $count++;
        }
        $data = array(
            "categorydetails" => $params,
        );
        return  $this->render_from_template('block_mycourses/view',  $data);
    }
}
