<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Courses external API
 *
 * @package    local_courses
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */
 
defined('MOODLE_INTERNAL') || die();
function xmldb_local_onlineexams_uninstall(){
	global $DB;
	$dbman = $DB->get_manager();
    $table = new xmldb_table('course');
	// if ($dbman->table_exists($table)) {
	// 	$fields = array('open_module','open_coursetype');
	// 	foreach($fields AS $field){
	// 		$field = new xmldb_field($field);
	// 		if($dbman->field_exists($table, $field)){
	// 			$dbman->drop_field($table, $field);
	// 		}
	// 	}

	// }
}