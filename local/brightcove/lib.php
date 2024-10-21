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
 * library file
 * @package   local_brightcove
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function local_brightcove_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea == 'zoomvideo') {
        
        $itemid = array_shift($args);

        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/'.implode('/', $args).'/';
        }
        // var_dump($filepath);exit;

        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_brightcove', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false;
        }
        send_file($file, $filename);
    }
}
// function local_brightcove_render_navbar_output() {
//     global $PAGE, $USER;
//     // var_dump(get_config('local_brightcove'));exit;
//     $PAGE->requires->js_call_amd('local_brightcove/brightcove', 'init', [json_encode(get_config('local_brightcove')), json_encode($USER)]);
// }
function local_brightcove_get_video_content($identifier){
    global $OUTPUT, $USER;
    $videoid = str_replace('brighcove_recording_for_streaming_', '', $identifier);
    return $OUTPUT->render_from_template('local_brightcove/embedd_video', ['settings' => get_config('local_brightcove'), 'recordingobj' => ['videoid' => $videoid], 'userinfo' => $USER]);
}