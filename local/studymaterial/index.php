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
 * @package    local_studymaterial
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE, $OUTPUT, $DB;
$systemcontext = \context_system::instance();
$courseid = required_param('courseid', PARAM_INT);
$PAGE->set_url(new moodle_url('/local/studymaterial/index.php'), ['courseid' => $courseid]);
$PAGE->set_context($systemcontext);
require_login();
// require_capability('local/studymaterial:manage', $systemcontext);
$PAGE->set_title(get_string('pluginname', 'local_studymaterial'));
$PAGE->set_heading(get_string('pluginname', 'local_studymaterial'));
$studymaterialrender = $PAGE->get_renderer('local_studymaterial');
echo $OUTPUT->header();
if (has_capability('local/studymaterial:create', $systemcontext) || is_siteadmin()) {
    echo $OUTPUT->render_from_template('local_studymaterial/form', ['courseid'=>$courseid]);
}
echo $studymaterialrender->get_studymaterial_view($courseid);
echo $OUTPUT->footer();
