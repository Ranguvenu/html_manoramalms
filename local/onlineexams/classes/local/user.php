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
 * local onlineexams
 *
 * @package    local_onlineexams
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_onlineexams\local;

class user{

    public function user_profile_content($userid,$return = false,$start =0,$limit =5){
        global $OUTPUT, $CFG;
        $returnobj = new \stdClass();
        $returnobj->onlineexamsexist = 1;
        $records = $this->enrol_get_users_onlineexams($userid,false,true,$start,$limit);
        $onlineexams = $records['data'];

        $data = array();
        foreach ($onlineexams as $onlineexam) {
            $onlineexamsarray = array();
            $onlineexamsarray["id"] = $onlineexam->id;
            $onlineexamsarray["name"] = $onlineexam->fullname;
            $url = new \moodle_url('/course/view.php', array('id' => $onlineexam->id));
            $urllink = $url->out();
            $onlineexamsarray["url"] = $urllink;
            $onlineexamsummary = $onlineexam->summary;
            $onlineexamsummary = \local_onlineexams\lib::strip_tags_custom($onlineexamsummary);
            $summarystring = strlen($onlineexamsummary) > 120 ? clean_text(substr($onlineexamsummary, 0, 120))."..." : $onlineexamsummary;
            $onlineexamsarray["description"] = $summarystring;
            $onlineexamsarray["percentage"] = round($this->user_onlineexam_completion_progress($onlineexam->id,$userid));

            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new \user_course_details();
            $onlineexam_record = get_course($onlineexam->id);
            $background_logourl= ($includes->course_summary_files($onlineexam_record));
            if(is_a($background_logourl, 'moodle_url')){
                $onlineexamsarray['module_img_url'] = $background_logourl->out();
            }else{
                $onlineexamsarray['module_img_url'] = $background_logourl;
            }
            $data[] = $onlineexamsarray;
        }

        $returnobj->sequence = 5;
        $returnobj->count = $records['count'];
        $returnobj->divid = 'user_onlineexams';
        $returnobj->moduletype = 'onlineexams';
        $returnobj->targetID = 'display_classroom';
        $returnobj->userid = $userid;
        $returnobj->string = get_string('onlineexams', 'local_users');
        $returnobj->navdata = $data;
        return $returnobj;
    }

    /**
     * Description: User onlineexam completion progress
     * @param  INT $onlineexamid onlineexam id whose completed percentage to be fetched
     * @param  INT $userid   userid whose completed onlineexam prcentage to be fetched
     * @return INT           percentage of completion.
     */
    public function user_onlineexam_completion_progress($onlineexamid, $userid) {
        global $DB, $USER, $CFG;
        if(empty($onlineexamid) || empty($userid)){
            return false;
        }

        $sql="SELECT id from {course_completions} where course= ? and userid= ? and  timecompleted IS NOT NULL";
        $completionenabled=$DB->get_record_sql($sql, [$onlineexamid, $userid]);
        $onlineexam_completion_percent = '';
       
        return 0;
    }

 
   

   
    public function inprogress_onlineexamnames($userid) {
        global $DB;
        $params = array();
        $couresparams = array();
        $sql = "SELECT DISTINCT(course.id),ue.userid, course.fullname, course.shortname as code, course.summary,ue.timecreated as enrolldate
                    FROM {course} AS course
                    JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                    JOIN {user_enrolments} AS ue ON e.id = ue.enrolid
                    WHERE ue.userid = :userid  AND course.id > 1 AND course.open_coursetype = 1";

        $params['userid'] = $userid;

        $completed_onlineexams = self::completed_onlineexamnames($userid);
        if(!empty($completed_onlineexams)){
            $complted_id = array();
            foreach($completed_onlineexams as $complted_onlineexam){
                $completed_id[] = $complted_onlineexam->id;
            }
            $completed_ids = implode(',', $completed_id);
            list($couressql, $couresparams) = $DB->get_in_or_equal($completed_id, SQL_PARAMS_NAMED, 'param', false, false);
            $sql .= " AND course.id $couressql";
        }

        $paramsarray = array_merge($params,$couresparams);
        $inprogress_onlineexams = $DB->get_records_sql($sql, $paramsarray);
        return $inprogress_onlineexams;
    }

    public function completed_onlineexamnames($userid) {
        global $DB;
        $sql = "SELECT distinct(cc.id) as completionid,c.id,c.fullname,c.shortname as code,c.summary,ue.timecreated as enrolldate,cc.timecompleted as completedate
            FROM {course_completions} AS cc
            JOIN {course} AS c ON c.id = cc.course
            JOIN {enrol} AS e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
            JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = cc.userid
            WHERE cc.timecompleted is not NULL AND c.visible=1 AND c.id>1 AND cc.userid = ? AND c.open_coursetype = 1
            ";

        $onlineexamnames = $DB->get_records_sql($sql, [$userid]);
        return $onlineexamnames;
    }

    public function enrol_get_users_onlineexams($userid, $count =false, $limityesno = false, $start = 0, $limit = 5, $source = false) {
        global $DB;
        $countsql = "SELECT count(DISTINCT(course.id)) ";
        $onlineexamssql = "SELECT course.id, course.fullname,course.shortname, course.summary,ue.timecreated as enrolldate , cc.timecompleted AS completiondate ";

        // $fromsql = "FROM {course} AS course
        //             JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
        //             JOIN {user_enrolments} ue ON e.id = ue.enrolid
        //             LEFT JOIN {course_completions} AS cc ON cc.course = course.id AND cc.userid = {$userid}
        //             WHERE ue.userid = ? AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',3,',%') AND course.id>1 ";

        $fromsql = " FROM {course} course
                    JOIN {enrol} e ON e.courseid = course.id AND
                                (e.enrol = 'manual' OR e.enrol = 'self')
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id
                    JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1
                                    AND u.deleted = 0 AND u.suspended = 0
                    JOIN {local_onlineexams} lc ON concat('/',u.open_path,'/') LIKE concat('%/',lc.id,'/%') AND lc.depth = 1
                    JOIN {role_assignments} as ra ON ra.userid = u.id
                    JOIN {context} AS cxt ON cxt.id=ra.contextid AND cxt.contextlevel = 50 AND cxt.instanceid=course.id
                    JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                    LEFT JOIN {course_completions} as cc ON cc.course = course.id AND u.id = cc.userid
                    WHERE course.id > 1 AND ue.userid = ? AND course.open_coursetype = 1 AND course.open_module = 'online_exams' ";
        // if ($source) {
        //     $fromsql .= " AND course.open_securecourse != 1 ";
        // }
        $ordersql = " ORDER BY ue.id DESC ";
        if ($limityesno)
            $records = $DB->get_records_sql($onlineexamssql.$fromsql.$ordersql, [$userid], $start, $limit);
        else
        $records = $DB->get_records_sql($onlineexamssql.$fromsql.$ordersql, [$userid]);

        $total = $DB->count_records_sql($countsql.$fromsql, [$userid]);

        return array('data'=>$records, 'count'=>$total);
    }

   
}
