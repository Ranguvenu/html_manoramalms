<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @package   local
 * @subpackage  onlineexams
 * @author eabyas  <info@eabyas.in>
**/
namespace local_onlineexams\local;
class general_lib{
	public function get_custom_data($fields = '*', $params){
		global $DB;
		$sql = "SELECT {$fields} FROM {course} WHERE 1=1 ";
		foreach($params AS $key => $value){
			if($key == 'unique_module')
				continue;
			$sql .= " AND {$key} =:{$key} ";
		}
		if((isset($params['unique_module']) && $params['unique_module'] ==  true) || (isset($params['id']) && $params['id'] > 0) ){
			$data = $DB->get_record_sql($sql, $params);
		}else{
			$data = $DB->get_records_sql($sql, $params);
		}
		return $data;
	}
	public function get_module_logo_url($courseid){
		global $CFG;
		if(file_exists($CFG->dirroot.'/local/includes.php')){
			require_once($CFG->dirroot.'/local/includes.php');
			$courseobject = get_course($courseid);
			$includes = new \user_course_details();
			$url_object = $includes->course_summary_files($courseobject);
			return $url_object;
		}
	}

	
	public function get_completion_count_from($moduleid, $userstatus, $date = NULL){
		global $DB;
		$params = array('moduleid' => $moduleid);
		switch($userstatus){
			case 'enrolled':
				$count_sql = "SELECT count(ue.id) FROM {user_enrolments} AS ue
					JOIN {enrol} AS e ON e.id = ue.enrolid
					WHERE e.enrol NOT IN ('classroom', 'program', 'learningplan') AND e.courseid = :moduleid ";
				if(!is_null($date)){
					$count_sql .= " AND ue.timecreated > :fromtime ";
					$params['fromtime'] = $date;
				}
			break;
			case 'completed':
				$count_sql = "SELECT count(cc.id) FROM {course_completions} AS cc
					JOIN {enrol} AS e ON e.courseid = cc.course AND e.enrol IN ('self', 'manual', 'auto', 'cohort')
					JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.userid = cc.userid
					WHERE cc.course = :moduleid AND cc.timecompleted IS NOT NULL ";
				if(!is_null($date)){
					$count_sql .= " AND cc.timecompleted > :fromtime ";
					$params['fromtime'] = $date;
				}
			break;
		}
		$count = $DB->count_records_sql($count_sql, $params);
		return $count;
	}
	public function get_custom_icon_details(){
		return ['componenticonclass' => 'fa fa-desktop', 'customimage_required' => False];
	}

	/******Function to the show the inprogress course names in the E-learning Tab********/
    public static function inprogress_onlineexamnames($filter_text='', $offset = 0, $limit = 10,$source = '') {
        global $DB, $USER;

        $sqlquery = "SELECT course.* ";
        $sql = " FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid ";

        $sql .= " WHERE ue.userid = {$USER->id} AND course.id <> 1 AND course.visible=1 ";
        if($source == 'mobile'){
            $sql .= " AND course.open_securecourse != 1   ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('course.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }

        $sql .= " AND course.id NOT IN(SELECT DISTINCT(course) FROM {course_modules} cm
        JOIN   {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id 
        WHERE cmc.userid = {$USER->id} AND cmc.completionstate > 0 ) AND course.open_coursetype = 1 AND course.open_module = 'online_exams'  ";

        $sql .= ' order by ue.timecreated desc';
        $inprogress_onlineexams = $DB->get_records_sql($sqlquery . $sql, $params, $offset, $limit);
        return $inprogress_onlineexams;

    }

    public static function inprogress_onlineexamnames_count($filter_text = '', $source = ''){
        global $USER, $DB;
        $sql = "SELECT COUNT(DISTINCT(course.id))  FROM {course} AS course
            JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            WHERE ue.userid = {$USER->id}
            AND course.id <> 1 AND course.visible = 1 AND course.id NOT IN(SELECT DISTINCT(course) FROM {course_modules} cm
        JOIN   {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id 
        WHERE cmc.userid = {$USER->id} AND cmc.completionstate > 0 AND course = course.id) AND course.open_coursetype = 1 AND course.open_module = 'online_exams'  ";
        if($source == 'mobile'){
            $sql .= " AND course.open_securecourse != 1 ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('course.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }
        $inprogress_count = $DB->count_records_sql($sql, $params);
        return $inprogress_count;
    }
    /*********End of the Function*********/

    /******Function to the show the Completed course names in the E-learning Tab********/
    public static function completed_onlineexamnames($filter_text='', $offset = 0, $limit = 10, $source = '') {
        global $DB, $USER;
        $sql = '';
        $sqlquery = "SELECT c.*";
        $sql .= " FROM {course} c
                JOIN {course_modules} as cm ON cm.course = c.id                
                JOIN {enrol} e ON c.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND ue.userid = cmc.userid
                WHERE ue.userid = {$USER->id}
                AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1 AND c.open_coursetype = 1 AND c.open_module = 'online_exams' ";
        if($source == 'mobile'){
           $sql .= " AND c.open_securecourse != 1 ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('c.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }
        $sql .= " ORDER BY c.id DESC ";
        $onlineexamnames = $DB->get_records_sql($sqlquery . $sql, $params, $offset, $limit);
        return $onlineexamnames;
    }
    /********end of the Function****/

    public static function completed_onlineexamnames_count($filter_text = '', $source = ''){
    	global $DB, $USER;

        $sql = "SELECT COUNT(DISTINCT(c.id))
                FROM {course} c
                JOIN {course_modules} as cm ON cm.course = c.id                
                JOIN {enrol} e ON c.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND ue.userid = cmc.userid
                WHERE ue.userid = {$USER->id}
                AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1 AND c.open_coursetype = 1 AND c.open_module = 'online_exams'";

        if($source == 'mobile'){
            $sql .= " AND c.open_securecourse != 1 ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('c.fullname', ':coursename', false);
            $params['coursename'] = '%'.$filter_text.'%';
        }
        $completed_count = $DB->count_records_sql($sql, $params);
        return $completed_count;
    }
    public function get_onlineexams_having_completion_criteria($courseid, $query = '', $offset = 0, $limit = 0){
    	global $DB, $CFG;
    	require_once($CFG->libdir.'/completionlib.php');
        $onlineexamsSql = "SELECT DISTINCT c.id, c.fullname
            FROM {course} c
            LEFT JOIN {course_completion_criteria} cc ON cc.courseinstance = c.id AND cc.course = {$courseid}
            INNER JOIN {course_completion_criteria} ccc ON ccc.course = c.id
            WHERE c.enablecompletion = ".COMPLETION_ENABLED."  AND c.id <> :courseid

            AND c.open_path = (SELECT open_path FROM {course} WHERE id = :thiscourseid) AND c.open_coursetype = 1 AND c.open_module = 'online_exams' ";
        $params = array('courseid' => $courseid, 'thiscourseid' => $courseid);
        if($query != ''){
            $onlineexamsSql .= " AND ".$DB->sql_like('c.fullname', ":search", false);
            $params['search'] = "%$query%";
        }
        $onlineexams = $DB->get_records_sql($onlineexamsSql, $params, $offset, $limit);
        return $onlineexams;
    }
    /******Function to the show the enrolled course names in the E-learning Tab********/
    public static function enrolled_onlineexamnames($filter_text='', $offset = 0, $limit = 10, $source = '') {
        global $DB, $USER;
        $sqlquery = "SELECT course.*";

        $sql = " FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid ";

        $sql .= " WHERE ue.userid = {$USER->id} AND course.id <> 1 AND course.visible=1 AND course.open_coursetype = 1 AND course.open_module = 'online_exams' ";
        if($source == 'mobile'){
            $sql .= " AND course.open_securecourse != 1 ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('course.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }

        $sql .= ' order by ue.timecreated desc';
        $enrolled_onlineexams = $DB->get_records_sql($sqlquery . $sql, $params, $offset, $limit);

        return $enrolled_onlineexams;

    }
    public static function enrolled_onlineexamnames_count($filter_text='', $source = ''){
        global $USER, $DB;
        $sql = "SELECT COUNT(DISTINCT(course.id))  FROM {course} AS course
            JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            JOIN {user} u ON u.id = ue.userid
            WHERE ue.userid = {$USER->id}
            AND course.id <> 1 AND course.visible = 1 AND u.id > 2 AND u.suspended = 0 AND u.deleted = 0 AND course.open_coursetype = 1 AND course.open_module = 'online_exams' ";
        if($source == 'mobile'){
            $sql .= " AND course.open_securecourse != 1 ";
        }
        $params = [];
        if(!empty($filter_text)){
           $sql .= "   AND ".$DB->sql_like('course.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }
        $enrolled_count = $DB->count_records_sql($sql, $params);
        return $enrolled_count;
    }
    public static function enrolled_onlineexamnames_formobile($filter_text='', $offset = 0, $limit = 10, $type = '', $source = '') {
        global $DB, $USER;

        $sqlquery = "SELECT course.*";

        $sql = " FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol NOT IN ('classroom', 'program', 'learningplan')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid ";
        if($type == 'recentlyaccessed'){
            $sql .= " JOIN {user_lastaccess} as ul ON ul.courseid = course.id AND ul.userid = $USER->id";
        }
        $sql .= " WHERE ue.userid = {$USER->id} AND course.id <> 1 AND course.visible=1 AND course.open_coursetype = 1 AND course.open_module = 'online_exams' ";
        if($source == 'mobile'){
            $sql .= " AND course.open_securecourse = 0 ";
        }
        $params = [];
        if(!empty($filter_text)){
            $sql .= "   AND ".$DB->sql_like('course.fullname', ':coursename', false);
           $params['coursename'] = '%'.$filter_text.'%';
        }
        if ($type == 'recentlyaccessed') {
            $sql .= " ORDER BY ul.timeaccess DESC "; 
        } else {
            $sql .= ' order by ue.timecreated desc';
        }

        $enrolled_onlineexams = $DB->get_records_sql($sqlquery . $sql, $params, $offset, $limit);

        return $enrolled_onlineexams;

    }
    public function get_course_info($id){
        global $DB, $CFG;
        require_once($CFG->dirroot.'/local/search/lib.php');
        $course = $DB->get_record('course', array('id' => $id));
        if($course){
            $course->points = $course->open_points;
            $course->category = ($DB->get_field('local_custom_fields','fullname',array('id' => $course->open_category))) ;
            $course->isenrolled = is_enrolled(\context_course::instance($course->id), $USER->id, '', true);
            if($course->isenrolled){
                $course->requeststatus = MODULE_ENROLLED;
            }else{
                $course->requeststatus = MODULE_NOT_ENROLLED;
                if($course->approvalreqd == 1){
                    $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
                    $requeststatus = $DB->get_field_sql($sql, array('componentid' => $course->id,'compname' => 'elearning', 'createdbyid'=>$USER->id));
                    if($requeststatus == 'PENDING'){
                        $course->requeststatus = MODULE_ENROLMENT_PENDING;
                    }
                }
            }
            $course->bannerimage = \local_search\output\searchlib::convert_urlobject_intoplainurl($course);

            $ratinginfo = $DB->get_record('local_ratings_likes', array('module_id' => $course->id, 'module_area' => 'local_onlineexams'));
            if($ratinginfo){
                $course->avgrating = $ratinginfo->module_rating;
                $course->ratedusers = $ratinginfo->module_rating_users;                
            }

            if($course->open_skill)
                $course->skill = ($DB->get_field('local_skill','name',array('id' => $course->open_skill))) ;

            if($course->open_level)
                $course->level = ($DB->get_field('local_course_levels','name',array('id' => $course->open_level))) ;

            $course->module = 'local_onlineexams';
            return $course;
        }else{
            throw new \Exception('Course not found');
        }
    }
}
