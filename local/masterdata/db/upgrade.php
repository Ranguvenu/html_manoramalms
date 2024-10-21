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
 * Upgrade steps for Master Data Import
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    local_masterdata
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
function xmldb_local_masterdata_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023042402.1) {
    
        $dbman = $DB->get_manager();

        $table = new xmldb_table('local_question_attempts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attemptsinfo',  XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('attempt_start_date',XMLDB_TYPE_CHAR, '255',  null, null, null, null, null);
        $table->add_field('timetaken',XMLDB_TYPE_CHAR, '255',  null, null, null, null, null);
        $table->add_field('last_try_date',XMLDB_TYPE_CHAR, '255',  null, null, null, null, null);
        $table->add_field('difficulty_level', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('mark', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('viewed_questions', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('questions_under_review',  XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('is_exam_finished', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('exam_mode', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('no_of_qns', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('is_exam_paused', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('is_module_wise_test', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('total_mark', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023042402.1, 'local', 'masterdata');
    }

    if ($oldversion <  2023042402.2) {

        $table = new xmldb_table('local_question_attempts');
        $fieldA = new xmldb_field('attempt_start_date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $fieldB = new xmldb_field('timetaken', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $fieldC = new xmldb_field('last_try_date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $fieldD = new xmldb_field('attemptid',XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0','quizid');
        if ($dbman->field_exists($table, $fieldA) ) {
            $dbman->change_field_type($table, $fieldA);
        }
        if ($dbman->field_exists($table, $fieldB) ) {
            $dbman->change_field_type($table, $fieldB);
        }
        if ($dbman->field_exists($table, $fieldC) ) {
            $dbman->change_field_type($table, $fieldC);
        }
        if (!$dbman->field_exists($table, $fieldD) ) {
            $dbman->add_field($table, $fieldD);
        }
        upgrade_plugin_savepoint(true, 2023042402.2, 'local', 'masterdata');

    }

    if ($oldversion <  2023042402.7) {

        $table = new xmldb_table('local_masterdata_log');
        $field = new xmldb_field('status_message',XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        } 
        upgrade_plugin_savepoint(true, 2023042402.7, 'local', 'masterdata');

    }

    if ($oldversion < 2023042403) {
    
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_zoom_notifcation_logs');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('zoomid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('notification_log',  XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('type',XMLDB_TYPE_CHAR, '255',  null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true,2023042403, 'local', 'masterdata');
    }

    if ($oldversion < 2023042404) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_notification_logs');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notification_type',XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
        $table->add_field('from_userid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('to_userid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cc_userid', XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {    
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true,2023042404, 'local', 'masterdata');
    
    }
    if ($oldversion < 2023042405) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_notification_logs');
        $field = new xmldb_field('messagebody', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field) ) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true,2023042405, 'local', 'masterdata');
    
}




    return true;
}
