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
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_onlineexams_install(){
	global $CFG,$DB,$USER;
	$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	$table = new xmldb_table('course');
	if ($dbman->table_exists($table)) {

        $field1 = new xmldb_field('open_module');
        $field1->set_attributes(XMLDB_TYPE_CHAR, '255',null, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
		$field1 = new xmldb_field('open_coursetype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('isfeaturedexam', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
	}
	// $time = time();
    // $initcontent = array('name' => 'Online Exam','shortname' => 'onlineexam','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams');
    // $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'onlineexam'));
    // if(!$parentid){
    //     $parentid = $DB->insert_record('local_notification_type', $initcontent);
    // }
    // $notification_type_data = array(
    //     array('name' => 'Online Exam Enrollment','shortname' => 'onlineexam_enrol','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
    //     array('name' => 'Online Exam Completion','shortname' => 'onlineexam_complete','parent_module' => $parentid, 'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
    //     array('name' => 'Online Exam Unenrollment','shortname' => 'onlineexam_unenroll','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
        
    // );
    // foreach($notification_type_data as $notification_type){
    //     unset($notification_type['timecreated']);
    //     if(!$DB->record_exists('local_notification_type',  $notification_type)){
    //         $notification_type['timecreated'] = $time;
    //         $DB->insert_record('local_notification_type', $notification_type);
    //     }
    // }
	// $strings = array(
    //     array('name' => '[onlineexams_title]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[enroluser_fullname]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[enroluser_email]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_code]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_enrolstartdate]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_enrolenddate]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_completiondays]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_department]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_link]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_duedate]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_description]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_url]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_description]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_image]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_completiondate]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_reminderdays]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
    //     array('name' => '[onlineexams_categoryname]','module' => 'onlineexams','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL)
    // );
    // foreach($strings as $string){
    //     unset($string['timecreated']);
    //     if(!$DB->record_exists('local_notification_strings', $string)){
    //         $string_obj = (object)$string;
    //         $string_obj->timecreated = $time;
    //         $DB->insert_record('local_notification_strings', $string_obj);
    //     }
    // }
   }
