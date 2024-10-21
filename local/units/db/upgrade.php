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
 * @author     Ramanjaneyulu  <ramanjaneyulu.chinni@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_units_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if($oldversion < 2023042402.50){
        $table = new xmldb_table('local_concept');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null,null);
        $table->add_field('code', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null,null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('unitid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('chapterid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('topicid', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023042402.50, 'local', 'units');
    }

    if ($oldversion < 2023042402.7) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_units');
        $table1 = new xmldb_table('local_chapters');
        $table2 = new xmldb_table('local_topics');
        $table3 = new xmldb_table('local_concept');
        $field = new xmldb_field('name');
        $field->set_attributes(XMLDB_TYPE_TEXT, null, null, null, null, null);
        try {
            $dbman->change_field_type($table, $field);
            $dbman->change_field_type($table1, $field);
            $dbman->change_field_type($table2, $field);
            $dbman->change_field_type($table3, $field);
        } catch (moodle_exception $e) {

        }
        upgrade_plugin_savepoint(true, 2023042402.7, 'local', 'units');
    }
    return true;
}
