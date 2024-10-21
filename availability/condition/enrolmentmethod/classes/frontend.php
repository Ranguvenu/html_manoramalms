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
 * Front-end class.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_enrolmentmethod;

defined('MOODLE_INTERNAL') || die();
use course_enrolment_manager;

require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * Front-end class.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Get the initial parameters needed for JavaScript.
     *
     * @param \stdClass          $course
     * @param \cm_info|null      $cm
     * @param \section_info|null $section
     *
     * @return array
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $PAGE;

        $manager = new course_enrolment_manager($PAGE, $course);
        $enrolmentinstances = $manager->get_enrolment_instances(true);
        $enrolmentmethodnames = $manager->get_enrolment_instance_names(true);
        // Change to JS array format and return.
        $jsarray = array();
        $context = \context_course::instance($course->id);
        foreach ($enrolmentinstances as $rec) {
            $jsarray[] = (object) array('id' => $rec->id, 'name' =>
                    format_string($enrolmentmethodnames[$rec->id], true, array('context' => $context)));
        }
        return array($jsarray);
    }

    /**
     * Gets all enrolment methods for the given course.
     *
     * @param int $courseid Course id
     * @return array Array of all the enrolment method objects
     */
    protected function get_all_enrolmentmethods($courseid) {
        global $PAGE;
        $course = get_course($courseid);
        $manager = new course_enrolment_manager($PAGE, $course);
        $this->allenrolmentmethods = $manager->get_enrolment_instances(true);
        return $this->allenrolmentmethods;
    }

    /**
     * Decides whether this plugin should be available in a given course.
     *
     * @param \stdClass          $course
     * @param \cm_info|null      $cm
     * @param \section_info|null $section
     *
     * @return bool
     */
    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {
        // Only show this option if there are some enrolment methods.
        return count($this->get_all_enrolmentmethods($course->id)) > 0;
    }
}
