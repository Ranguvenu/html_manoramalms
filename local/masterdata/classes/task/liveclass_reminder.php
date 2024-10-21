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

namespace local_masterdata\task;

/**
 * Class liveclass_reminder
 *
 * @package    local_masterdata
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core\task;
use local_masterdata;
class liveclass_reminder extends \core\task\scheduled_task {
	/**
     * Returns name of task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('liveclassroom_reminder', 'local_masterdata');
    }
    /**
     * Get any new recordings from zoom and pushes to brightcove.
     *
     * @return void
     */
    public function execute() {
        global $DB;
        $starttime = strtotime(date('Y-m-d H:i', strtotime("today 7.30am")));
        $endtime = strtotime(date('Y-m-d H:i', strtotime("today 8.30pm")));
        $currenttime =  strtotime(date('Y-m-d H:i'));
        $module = $DB->get_field('modules','id',['name'=>'zoom']);
        $sql = "SELECT zm.id,zm.name,zm.alternative_hosts,zm.start_time,com.course,com.id AS coursemoduleid FROM mdl_zoom zm   
                                                JOIN mdl_course_modules com ON com.instance =zm.id
                                                WHERE com.module = :moduleid AND  (start_time BETWEEN $starttime AND $endtime) ";
        $zoomevents = $DB->get_records_sql($sql,['moduleid'=>$module]);
        if(COUNT($zoomevents) > 0) {
            foreach($zoomevents AS $zevent){
                $earliertime = strtotime('-30 minutes',$zevent->start_time);
                if($currenttime == $earliertime) {
                   (new local_masterdata\api())->send_zoom_remainder_notification($zevent); 
                }
            }
	    }
    }
}
