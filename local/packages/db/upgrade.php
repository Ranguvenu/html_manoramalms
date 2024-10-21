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
 * Upgrade steps for Packages
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    local_packages
 * @category   upgrade
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_packages_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if($oldversion < 2023092100.02){

        $packagesessionstable = new xmldb_table('local_package_sessions');
    
        $packagesessionstable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $packagesessionstable->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('packageid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('sessionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('sectionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('batchid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('schedulecode', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $packagesessionstable->add_field('startdate', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('enddate', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('starttime', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('endtime', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('teacher', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('co_presenter', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $packagesessionstable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($packagesessionstable)) {
            $dbman->create_table($packagesessionstable);
        }
        upgrade_plugin_savepoint(true, 2023092100.02, 'local', 'packages');
    }
    if($oldversion < 2023092100.06){

        $table = new xmldb_table('local_coursegroup_section');
    
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lb_id', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('code',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('enrol_start_date', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrol_end_date', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('studentlimit', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('provider', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023092100.06, 'local', 'packages');
    }

    if ($oldversion <  2023092100.08) {

        $table = new xmldb_table('local_coursegroup_section');
        $fieldA = new xmldb_field('name',XMLDB_TYPE_CHAR, '255',  null, null, null, null,'batchid');
        $fieldB = new xmldb_field('code',XMLDB_TYPE_CHAR, '255',  null, null, null, null,'name');
        $fieldC = new xmldb_field('enrol_start_date',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','code');
        $fieldD = new xmldb_field('enrol_end_date',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','enrol_start_date');
        $fieldE = new xmldb_field('duration',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','enrol_end_date');
        $fieldF = new xmldb_field('studentlimit',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','duration');
        $fieldG = new xmldb_field('provider',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','studentlimit');
        if (!$dbman->field_exists($table, $fieldA)) {
            $dbman->add_field($table, $fieldA);
        } 
        if (!$dbman->field_exists($table, $fieldB)) {
            $dbman->add_field($table, $fieldB);
        } 
        if (!$dbman->field_exists($table, $fieldC)) {
            $dbman->add_field($table, $fieldC);
        } 
        if (!$dbman->field_exists($table, $fieldD)) {
            $dbman->add_field($table, $fieldD);
        } 
        if (!$dbman->field_exists($table, $fieldE)) {
            $dbman->add_field($table, $fieldE);
        } 
        if (!$dbman->field_exists($table, $fieldF)) {
            $dbman->add_field($table, $fieldF);
        } 
        if (!$dbman->field_exists($table, $fieldG)) {
            $dbman->add_field($table, $fieldG);
        }
        upgrade_plugin_savepoint(true, 2023092100.08, 'local', 'trainingprogram');

    }

    if($oldversion < 2023092100.21){
        
        $table = new xmldb_table('local_batch_courses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('batchid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null,null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
         $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_batches');
    
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('code',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('enrol_start_date', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrol_end_date', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('studentlimit', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('provider', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hierarchy_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023092100.21, 'local', 'packages');
    }
    
    if ($oldversion <  2023092100.72) {

        $table = new xmldb_table('local_packagecourses');
        $field = new xmldb_field('batchid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0', 'lp_id');
        $oldid = new xmldb_field('old_id', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0', 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } 
        if (!$dbman->field_exists($table, $oldid)) {
            $dbman->add_field($table, $oldid);
        } 

        upgrade_plugin_savepoint(true, 2023092100.72, 'local', 'trainingprogram');
    }

    if($oldversion < 2023092100.74){
        $table = new xmldb_table('local_packages');
    
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lp_id', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('boardid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('classid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('code',XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description',XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valid_from', XMLDB_TYPE_DATETIME, '12', null, XMLDB_NOTNULL, false, null);
        $table->add_field('valid_to', XMLDB_TYPE_DATETIME, '12', null, XMLDB_NOTNULL, false, null);
        $table->add_field('package_type', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023092100.74, 'local', 'packages');
    }
    if ($oldversion <  2023092100.75) {

        $table = new xmldb_table('local_packages');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0', 'classid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } 
        $field1 = new xmldb_field('valid_from', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if ($dbman->field_exists($table, $field1)) {
            $dbman->change_field_default($table, $field1);
        }
        $field2 = new xmldb_field('valid_to', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if ($dbman->field_exists($table, $field2)) {
            $dbman->change_field_default($table, $field2);
        }
        upgrade_plugin_savepoint(true, 2023092100.75, 'local', 'packages');
    }
    return true;
}
