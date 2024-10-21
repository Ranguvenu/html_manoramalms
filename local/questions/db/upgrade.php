<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_questions_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    
    if($oldversion < 2023042400.2){

        $table = new xmldb_table('local_questions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('question_type', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('difficulty_level', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cognitive_level', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('learning_objective', XMLDB_TYPE_CHAR, '255',  null, null, null, null, null, null);
        $table->add_field('marks', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('weightage', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('source', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('class', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('subject', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('topic', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('solution', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);
        $table->add_field('hint', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
   
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042400.2, 'local', 'questions');
    }

        if($oldversion < 2023042400.9){
        $table = new xmldb_table('local_questions_courses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questionbankid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null,null);
        $table->add_field('topicid', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042400.9, 'local', 'questions');
    }

      if ($oldversion < 2023042401) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field =  new xmldb_field('classid',XMLDB_TYPE_TEXT,250, null, null, null, null, null);
        $field1 =  new xmldb_field('difficulty_level',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $field2 =  new xmldb_field('cognitive_level',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $field3 = new xmldb_field('learning_objective',XMLDB_TYPE_TEXT,250, null, null, null, null, null);
        $field4 =  new xmldb_field('source',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        } 
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        } 
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }         
        upgrade_plugin_savepoint(true, 2023042401, 'local', 'questions'); 
    }


    if($oldversion < 2023042401.02){
        $table = new xmldb_table('local_qb_questionreview');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionbankid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reviewdby', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('assignedreviewer', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reviewdon', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('qstatus', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null,null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042401.02, 'local', 'questions');
    }
    if ($oldversion < 2023042401.05) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field1 =  new xmldb_field('goalid',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $field2 =  new xmldb_field('boardid',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }      
        upgrade_plugin_savepoint(true, 2023042401.05, 'local', 'questions'); 
    }
    if ($oldversion < 2023042401.08) {
        $dbman = $DB->get_manager();
        $tableA = new xmldb_table('local_questions_courses');
        $fieldA = new xmldb_field('learning_objective',XMLDB_TYPE_TEXT, '250', null, null, null, '0');
        if ($dbman->field_exists($tableA, $fieldA)) {
            $dbman->change_field_type($tableA, $fieldA);
        }     
        upgrade_plugin_savepoint(true, 2023042401.08, 'local', 'questions'); 
    }
    if($oldversion < 2023042401.13){
        $table = new xmldb_table('local_question_sources');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null,null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null,null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
   
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('user_sources');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('sourceid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null,null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
   
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042402.21, 'local', 'questions');
    }
      if ($oldversion < 2023042401.22) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field1 =  new xmldb_field('chapterid',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }    
        upgrade_plugin_savepoint(true, 2023042401.22, 'local', 'questions'); 
    }
 if ($oldversion < 2023042401.23) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field1 =  new xmldb_field('unitid',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }    
        upgrade_plugin_savepoint(true, 2023042401.23, 'local', 'questions'); 
    }

     if ($oldversion < 2023042401.26) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field1 =  new xmldb_field('underreviewby',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $field2 =  new xmldb_field('reviewby',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $field3 =  new xmldb_field('finalstatusby',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }       
        upgrade_plugin_savepoint(true, 2023042401.26, 'local', 'questions'); 
    }
     if($oldversion < 2023042401.27){
        $table = new xmldb_table('local_questions_import_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('qinfo', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('idnumber', XMLDB_TYPE_TEXT, '50', null, XMLDB_NOTNULL, null,null);
        $table->add_field('importstatus', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042401.27, 'local', 'questions');
    }
    if ($oldversion < 2023042401.32){
        $table = new xmldb_table('local_rejected_questions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questionbankentryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reason', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023042401.32, 'local', 'questions');
    }
    if ($oldversion < 2023042401.33) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_rejected_questions');
        $field1 =  new xmldb_field('reason', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null, null);
        $field2 =  new xmldb_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if ($dbman->field_exists($table, $field1)) {
            $dbman->drop_field($table, $field1);
            $dbman->add_field($table, $field2);
        }
        upgrade_plugin_savepoint(true, 2023042401.33, 'local', 'questions'); 
    }
    if ($oldversion < 2023042401.37) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_questions_courses');
        $field1 =  new xmldb_field('conceptid',XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }    
        upgrade_plugin_savepoint(true, 2023042401.37, 'local', 'questions'); 
    }
    if ($oldversion < 2023042401.42) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('user_sources');
        $fieldA = new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, null, null, '0');
    
        if ($dbman->field_exists($table, $fieldA) ) {
            $index = new xmldb_index('local_question_sources', XMLDB_INDEX_NOTUNIQUE,['sourceid']);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }          
           $dbman->change_field_type($table, $fieldA);
        }
        upgrade_plugin_savepoint(true, 2023042401.42, 'local', 'questions'); 
    }



    return true;
}
