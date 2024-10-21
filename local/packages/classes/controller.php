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
 * Goals hierarchy
 *
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_packages
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_packages;
use core_form\dynamic_form;
use moodle_url;
use context;
use context_system;
use html_writer;
use stdClass;
use local_packages\controller as packages;
use core_course_category;

require_once("{$CFG->dirroot}/course/lib.php");

/**
 * Goals controller class
 */
class controller {

    /** @var $usermodified */
    private $usermodified;

    /** Construct */
    public function __construct() {
        global $USER;
        $this->usermodified = $USER->id;
    }

    /**
     * Get all the list of goals
     */
    public function get_goals() {
        global $DB;
        $packagesdata = array();
        $packages = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name AS package
        FROM {local_hierarchy} lh
        WHERE 1 = 1 AND lh.depth = 3");
        foreach ($packages as $package) {
            $packagesdata[] = ['goal' => $package->package];
        };
        return $packagesdata;
    }

}
