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
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage local_onlineexams
 */
namespace local_onlineexams;
class notification{
	public $db;
	public $user;
	public function __construct($db=null, $user=null){
		global $DB, $USER;
		$this->db = $db ? $db :$DB;
		$this->user = $user ? $user :$USER;
	}
    public function get_onlineexams_strings($emailtype){
        

    }
    public function onlineexams_notification($emailtype, $touser, $fromuser, $onlineexamsinstance){
        if($notification = $this->get_existing_notification($onlineexamsinstance, $emailtype)){
            $this->send_onlineexams_notification($onlineexamsinstance, $touser, $fromuser, $emailtype, $notification);
        }
        $this->mobile_notifications($emailtype, $onlineexamsinstance, $touser, $fromuser);
    }
    public function get_existing_notification($onlineexamsinstance, $emailtype){
        $corecomponent = new \core_component();
        $costcenterexist = $corecomponent::get_plugin_directory('local','costcenter');
        $params = array();
        $notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni
            JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid
            WHERE concat(',',lni.moduleid,',') LIKE concat('%,',:moduleid,',%') AND lnt.shortname LIKE :emailtype AND lni.active=1 ";
        $params['moduleid'] = $onlineexamsinstance->id;
        $params['emailtype'] = $emailtype;
        if($costcenterexist){
            $notification_typesql .= " AND concat('/',lni.open_path,'/') LIKE :costcenter";
            $params['costcenter'] = '%'.$onlineexamsinstance->costcenter.'%';
        } 
        $notification = $this->db->get_record_sql($notification_typesql, $params);
        if(empty($notification)){ // sends the default notification for the type.
            $params = array();
            $notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni
                JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid
                WHERE (lni.moduleid IS NULL OR lni.moduleid LIKE '0')
                AND lnt.shortname LIKE :emailtype AND lni.active=1 ";
            $params['emailtype'] = $emailtype;
            if($costcenterexist){
                $notification_typesql .= " AND concat('/',lni.open_path,'/') LIKE :costcenter ";
                $params['costcenter'] = '%'.$onlineexamsinstance->costcenter.'%';
            }
            $notification = $this->db->get_record_sql($notification_typesql, $params);
        }
        if(empty($notification)){
            return false;
        }else{
            return $notification;
        }
    }
	public function send_onlineexams_notification($onlineexams, $user, $fromuser, $emailtype, $notification){
        global $DB;
        $datamailobj = new \stdclass();
        $datamailobj->onlineexams_title = $onlineexams->fullname;
        $datamailobj->onlineexamsid = $onlineexams->id;
        $datamailobj->onlineexams_enrolstartdate = $onlineexams->startdate ? \local_onlineexams\lib::get_mail_userdate($user, "d/m/Y", $onlineexams->startdate) : 'N/A';
        $datamailobj->onlineexams_enrolenddate = $onlineexams->enddate ? \local_onlineexams\lib::get_mail_userdate($user, "d/m/Y", $onlineexams->enddate) : 'N/A';
        $datamailobj->onlineexams_completiondays = $onlineexams->open_onlineexamscompletiondays ? $onlineexams->open_onlineexamscompletiondays : 'N/A';
        $datamailobj->notification_infoid = $notification->id;

        // $datamailobj->onlineexams_department = $department;
        $datamailobj->onlineexams_department = $onlineexams->open_departmentid ? 
        	$this->db->get_field('local_onlineexams', 'fullname', array('id' => $onlineexams->open_departmentid)) : 'N/A' ;
        $datamailobj->onlineexams_categoryname = $this->db->get_field('course_categories', 'name', array('id' => $onlineexams->category));
        $quizid = $DB->get_field('quiz','id',['course' => $onlineexams->id]);
        $url = new \moodle_url('/mod/quiz/view.php?id='.$quizid);
        $datamailobj->onlineexams_url = \html_writer::link($url, $url);
        $datamailobj->onlineexams_description = $onlineexams->summary ? $onlineexams->summary : 'N/A' ;
        if($onlineexams->open_coursecreator){
            $datamailobj->course_creator = $this->db->get_field_sql("SELECT concat(firstname,' ',lastname) FROM {user} WHERE id=:creatorid", array('creatorid' => $onlineexams->open_coursecreator));
        }else{
            $datamailobj->course_creator = 'N/A';
        }
        if($emailtype == 'onlineexams_complete'){
        	$datamailobj->onlineexams_completiondate = \local_onlineexams\lib::get_mail_userdate($user,"d/m/Y", time());	
        }
        // $includes = new \user_course_details();
        // $courseimage = $includes->course_summary_files($course);
        // $datamailobj->course_image = \html_writer::img($courseimage, $course->fullname,array());
        $datamailobj->enroluser_fullname = $user->firstname.' '.$user->lastname;
	    $datamailobj->enroluser_email = $user->email;
		$datamailobj->onlineexams_reminderdays = $notification->reminderdays;
	    $datamailobj->adminbody = NULL;
	    $datamailobj->body = $notification->body;
	    $datamailobj->subject = $notification->subject;
	    $datamailobj->touserid = $user->id;


	    $fromuser = \core_user::get_support_user();
	    $datamailobj->fromuserid = $fromuser->id;
	    $datamailobj->teammemberid = 0;
	    if(!empty($notification->adminbody) && !empty($user->open_supervisorid)){
	    	$superuser = \core_user::get_user($user->open_supervisorid);
	    	$datamailobj->supervisor_name = fullname($superuser);
	    }else{
	    	$superuser = false;
	    	$datamailobj->supervisor_name = '';
	    }

        if($user->suspended == 0 && $user->deleted == 0){
            $this->log_email_notification($user, $fromuser, $datamailobj, $emailtype);
        }
        if($superuser && $superuser->suspended == 0 && $superuser->deleted == 0){
            $datamailobj->body = $notification->adminbody;
            $datamailobj->touserid = $superuser->id;
            $datamailobj->teammemberid = $user->id;
            $this->log_email_notification($superuser, $fromuser, $datamailobj, $emailtype);
        }
	
	}
    public function get_rolename_inonlineexams($onlineexamsinstance, $touserid){
        $touser = '';
        $istrainer = $this->db->record_exists('local_course_trainers', array('onlineexamsid' => $onlineexamsinstance->id, 'trainerid' => $touserid));
        if(!$istrainer){
            return get_string('employeerolestring', 'local_onlineexams');
        }else{
            return get_string('trainerrolestring', 'local_onlineexams');
        }
    }
    public function log_email_notification($touser, $fromuser, $datamailobj){
        if(!$touser){
            return true;
        }
        $dataobject = clone $datamailobj;
        $dataobject->subject = $this->replace_strings($datamailobj, $datamailobj->subject);
        $emailbody = $this->replace_strings($datamailobj, $datamailobj->body);
        $dataobject->emailbody = $emailbody;
        $dataobject->from_emailid = $fromuser->email;
        $dataobject->from_userid = $fromuser->id;
        $dataobject->to_emailid = $touser->email;
        $dataobject->to_userid = $touser->id;
        $dataobject->ccto = 0;
        $dataobject->sentdate = 0;
        $dataobject->sent_by = $this->user->id;
        $dataobject->moduleid = $datamailobj->onlineexamsid;
        if($logid = $this->check_pending_mail_exists($touser, $fromuser, $datamailobj)){
            $dataobject->id = $logid;
            $dataobject->timemodified = time();
            $dataobject->usermodified = $this->user->id;
            $logid = $this->db->update_record('local_emaillogs', $dataobject);
        }else{
            $dataobject->timecreated = time();
            $dataobject->usercreated = $this->user->id;
            $this->db->insert_record('local_emaillogs', $dataobject);
        }
    }
    public function check_pending_mail_exists($user, $fromuser, $datamailobj){
        $sql =  " SELECT id FROM {local_emaillogs} WHERE to_userid = :userid AND notification_infoid = :infoid AND from_userid = :fromuserid AND subject = :subject AND status = 0";
        $params['userid'] = $datamailobj->touserid;
        $params['subject'] = $datamailobj->subject;
        $params['fromuserid'] = $datamailobj->fromuserid;
        $params['infoid'] = $datamailobj->notification_infoid;
        if($datamailobj->onlineexamsid){
            $sql .= " AND moduleid=:onlineexamsid";
            $params['onlineexamsid'] = $datamailobj->onlineexamsid;
        }
        if($datamailobj->teammemberid){
            $sql .= " AND teammemberid=:teammemberid";
            $params['teammemberid'] = $datamailobj->teammemberid;
        }
        return $this->db->get_field_sql($sql ,$params);
    }
    public function replace_strings($dataobject, $data){
        $strings = $this->db->get_records('local_notification_strings', array('module' => 'onlineexams'));
        if($strings){
            foreach($strings as $string){
                foreach($dataobject as $key => $dataval){
                    $key = '['.$key.']';
                    if("$string->name" == "$key"){
                        $data = str_replace("$string->name", "$dataval", $data);
                    }
                }
            }
        }
        return $data;
    }
    public function mobile_notifications($type, $instance, $userto, $userfrom) {
        global $CFG;
        if ($type == 'onlineexams_enrol') {
            $component = 'local_onlineexams';
            $name = 'onlineexamsenrolment';
            $instance->onlineexamsurl = '<a href="' . $CFG->wwwroot . '/mod/quiz/view.php?cid=' . $instance->id . '"></a>';
            $messagesubject = get_string('onlineexamsenrolmentsub', 'local_onlineexams');
            $message = get_string('onlineexamsenrolment', 'local_onlineexams', $instance);
        }
        $plaintext = html_to_text($message);
        $eventdata = new \core\message\message();
        $eventdata->courseid          = SITEID;
        $eventdata->component         = $component;
        $eventdata->name              = $name;
        $eventdata->userfrom          = $userfrom;
        $eventdata->userto            = $userto;
        $eventdata->notification      = 1;
        $eventdata->subject           = $messagesubject;
        $eventdata->fullmessage       = $plaintext;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message;
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }
}
