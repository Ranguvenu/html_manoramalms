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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Moodle India Information Solutions
 * @package local_units
 */

require('../../../config.php');
global $CFG, $DB, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

@set_time_limit(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('upload'));
$PAGE->set_heading(get_string('upload'));
$returnurl = new moodle_url('/local/units/index.php');

$stdfields = ['goal', 'board', 'class', 'subject', 'unit', 'chapter', 'topic'];
$prffields = [];
$mform = new local_units\form\hrms_async();

if ($mform->is_cancelled()) {
	redirect($returnurl);
}
if ($formdata = $mform->get_data()) {
	echo $OUTPUT->header();
	$mform->display();
	$iid = csv_import_reader::get_new_iid('userfile');
	$cir = new csv_import_reader($iid, 'userfile');
	$content = $mform->get_file_content('userfile');
	$readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
	$cir->init();
	$linenum = 1; // column header is first line.
	$progresslibfunctions = new local_units\upload\progresslibfunctions();
	$filecolumns = $progresslibfunctions->local_units_validate_hierarchy_columns($cir, $stdfields, $prffields, $returnurl);
	$hrms = new local_units\upload\syncfunctionality();
	$hrms->main_hrms_frontendform_method($cir, $filecolumns, $formdata);
	echo $OUTPUT->footer();
} else {
	echo $OUTPUT->header();
	echo html_writer::link(new moodle_url('/local/units/sample.php?format=csv'), 'Sample', array('id' => 'download_hierarchysheet', 'class' => 'btn btn-primary mr-2'));
	$mform->display();
	echo $OUTPUT->footer();
	die;
}