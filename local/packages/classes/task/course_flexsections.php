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
 * Packages plugin "local_packages" - Scheduled tasks
 *
 * @package    local_packages
 * @copyright  2024 Moodle India Information Solutions.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_packages\task;
use stdClass;
/**
 * A class which is a Scheduled job related to the course flexsections.
 */
class course_flexsections extends \core\task\scheduled_task {
    
    /**
     * Return localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('courseflexsections', 'local_packages');
    }

    /**
     * Execute scheduled task
     *
     * @return boolean
     */
    public function execute() {
        self::course_flexsections(true);
    }

    /**
     * Syncronizes user fron external APAC API server to moodle user table
     *    
     * Retrieving API response and processing the user object
     */
    public static function course_flexsections() {
    	global $CFG, $DB, $USER;
		$coursesectionidsql = "SELECT cs.id
							     FROM {course_sections} cs 
							     JOIN {course} c ON c.id = cs.course
							    WHERE c.format = 'flexsections'
							      AND cs.id NOT IN (
												  	SELECT sectionid
													  FROM {course_format_options}
													 WHERE format = 'flexsections'
													   AND name LIKE 'parent'
												 )";
		$coursesectionids = $DB->get_fieldset_sql($coursesectionidsql);
		foreach ($coursesectionids as $value) {

			$course = $DB->get_field('course_sections', 'course', ['id' => $value]);

			$params = [];
			$params['courseid'] = $course;
			$params['format'] = 'flexsections';
			$params['sectionid'] = $value;
			$params['name'] = 'parent';
			// $params['value'] = 0; // Need not be the section at parent value 0.
			$recodexistssql = "SELECT id
								 FROM {course_format_options}
								WHERE courseid = :courseid
								  AND format = :format
								  AND sectionid = :sectionid
								  AND name = :name";//AND value = :value
			$existid = $DB->get_field_sql($recodexistssql, $params);
			
			if ($existid) {
				// Need not perform any update to such sections.
				// $id = $DB->get_field('course_format_options', 'id', $params);
				// $id = $existid;
				// $updtobject->id = $id;
				// $updtobject->courseid = $course;
				// $updtobject->format = 'flexsections';
				// $updtobject->sectionid = $value;
				// $updtobject->name = 'parent';
				// $updtobject->value = 0;
				// $changed = $DB->update_record('course_format_options', $updtobject);
			} else {
				$object = new stdClass();
				$object->courseid = $course;
				$object->format = 'flexsections';
				$object->sectionid = $value;
				$object->name = 'parent';
				$object->value = 0;
				$changed = $DB->insert_record('course_format_options', $object);
			}
		}
    	return $changed;
    }
}