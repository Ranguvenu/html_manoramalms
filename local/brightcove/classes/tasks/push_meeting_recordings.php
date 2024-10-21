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
 * local_brightcove
 * @package local_brightcove
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_brightcove\tasks;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/zoom/locallib.php');

/**
 * Scheduled task to get the meeting recordings.
 */
class push_meeting_recordings extends \core\task\scheduled_task {
	/**
     * Returns name of task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('getmeetingrecordings', 'mod_zoom');
    }

    /**
     * Get any new recordings from zoom and pushes to brightcove.
     *
     * @return void
     */
    public function execute() {
	    $api = new \local_brightcove\api();
	    $service = new \mod_zoom_webservice();
	    $zoommeetings = zoom_get_all_meeting_records();
	    $uploadsresponse = array();
	    foreach ($zoommeetings as $zoom) {
            $now = time();
            if ($zoom->recurring || $now > (intval($zoom->start_time) + intval($zoom->duration))) {
                $recordings = zoom_get_meeting_recordings($zoom->id);
                foreach($recordings as $recording){
                    $recordingurls = $service->get_meetings_download_links($zoom->meeting_id);
                    if(isset($recordingurls['download_access_token'])){
	                    $downloadtoken = $recordingurls['download_access_token'];
	                    unset($recordingurls['download_access_token']);
	                    foreach($recordingurls as $key => $value){
                            if(is_array($value)){
                                foreach($value as $index => $recordingurlinfo){
                                    if($recordingurlinfo->filetype == "MP4"){
                                    	$api->push_video_to_brightcove($downloadtoken, $recordingurlinfo, $recording);
                                    }
                                }
                            }
	                    }
                    }
                }
            }
        }
	    return $uploadsresponse;
	}
}