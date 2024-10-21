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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_masterdata\task;

/**
 * Scheduled task to get the meeting recordings.
 */
use core\task;
use local_masterdata;
class liveclass_invite extends \core\task\scheduled_task {
	/**
     * Returns name of task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('liveclassroom_notification', 'local_masterdata');
    }
    /**
     * Get any new recordings from zoom and pushes to brightcove.
     *
     * @return void
     */
    public function execute() {
        global $DB,$USER;
        $starttime = strtotime(date('Y-m-d H:i', strtotime("today 7am")));
        $endtime = strtotime(date('Y-m-d H:i', strtotime("today 8pm")));
        $module = $DB->get_field('modules','id',['name'=>'zoom']);
        $sql = "SELECT zm.id,zm.name,zm.start_time,zm.alternative_hosts,com.course,com.id AS coursemoduleid FROM mdl_zoom zm   
                                                JOIN mdl_course_modules com ON com.instance =zm.id
                                                WHERE com.module = :moduleid AND (start_time BETWEEN $starttime AND $endtime)";
        $zoomevents = $DB->get_records_sql($sql,['moduleid'=>$module]);
        if(COUNT($zoomevents) > 0) {
            foreach($zoomevents AS $zevent){                
                (new local_masterdata\api())->send_zoom_invite_notification($zevent);                 

            }
        }
	}
}
