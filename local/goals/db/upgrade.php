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
 * Goals hierarchy upgrade file
 *
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_goals
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * xmldb_local_goals_upgrade [description]
 * @param [type] $oldversion [description]
 */
function xmldb_local_goals_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if($oldversion < 2023042401.17){

        $table = new xmldb_table('local_packagecourses');
    
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('boardid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('classid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hierarchyid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lp_id', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('parentcourseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('package_type',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0',);
        $table->add_field('startdate',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0',);
        $table->add_field('enddate',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0',);
        $table->add_field('validity_type',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0',);
        $table->add_field('validity',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0',);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023042401.17, 'local', 'goals');
    }

    if ($oldversion <  2023042401.24) {

        $table = new xmldb_table('local_subjects');
        $field = new xmldb_field('is_active', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } 

        $table = new xmldb_table('local_hierarchy');
        $field = new xmldb_field('is_active', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } 

        upgrade_plugin_savepoint(true, 2023042401.24, 'local', 'goals');
    }

    return true;
}
