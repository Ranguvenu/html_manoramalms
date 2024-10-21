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

function xmldb_local_onlineexams_upgrade($oldversion)
{
    global $DB, $CFG;
    $dbman = $DB->get_manager();    
    if ($oldversion < 2023021500.08) {
        $time = time();
        $initcontent = array('name' => 'Online Exam','shortname' => 'onlineexam','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'onlineexam'));
        if(!$parentid){
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }
        $notification_type_data = array(
            array('name' => 'Online Exam Enrollment','shortname' => 'onlineexam_enrol','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
            array('name' => 'Online Exam Completion','shortname' => 'onlineexam_complete','parent_module' => $parentid, 'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
            array('name' => 'Online Exam Unenrollment','shortname' => 'onlineexam_unenroll','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlineexams'),
            
        );
        foreach($notification_type_data as $notification_type){
            unset($notification_type['timecreated']);
            if(!$DB->record_exists('local_notification_type',  $notification_type)){
                $notification_type['timecreated'] = $time;
                $DB->insert_record('local_notification_type', $notification_type);
            }
        }
        upgrade_plugin_savepoint(true, 2023021500.08, 'local', 'onlineexams');
    }
    if ($oldversion < 2023021500.14) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('isfeaturedexam', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023021500.14, 'local', 'onlineexams');
    }
    return true;
}
