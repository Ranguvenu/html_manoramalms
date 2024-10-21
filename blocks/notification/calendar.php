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
 * TODO describe file calendar
 *
 * @package    block_notification
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

global $CFG, $PAGE, $OUTPUT, $DB;
$PAGE->requires->jquery();

$context = context_system::instance();
$url = new moodle_url('/blocks/notification/calendar.php', []);
$PAGE->set_url($url);
$PAGE->set_context($context);

// $PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('block_notification');
$courseid = 1;
$PAGE->requires->js_call_amd('block_notification/calendarcards', 'init');
$PAGE->requires->js_call_amd('block_notification/calendarcards', 'load');
$PAGE->requires->js_call_amd(
	'block_notification/dueactivity',
	'init',
	array(
		array(
			'contextid' => $context->id,
			'selector' => '#viewactivity',
			'userid' => $USER->id
		)
	)
);
// $PAGE->requires->js_call_amd('block_notification/calendarcards', 'customCalendar');
// echo "Madhu...";
// $setdata = new stdClass();
// if ($year == 0 && $month == 0) {
//     $year = date('Y');
//     $month = date('m');
//     $day = date('d');
//     $setdata->year = $year;
//     $setdata->month = $month;
//     $setdata->day = $day;
// }
// $mform = new block_notification\form\filters_form(null,['day'=>date('d')]);
// $mform->set_data($setdata);

// $mform->display();
// $years = [2023 => '2023', 2024 => '2024'];
// $select = new single_select(new moodle_url('/blocks/notification/calendar.php', ['month' => $month]), 'year', $years, $year, null);
// $select->class = 'pull-left clearfix';
// $select->label = 'Select Year';
// $singleselect = '<div class="w-100">' . $OUTPUT->render($select) . '</div>';
// $months = [1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December"];
// echo $singleselect;
// $select = new single_select(new moodle_url('/blocks/notification/calendar.php', ['year' => $year]), 'month', $months, $month, null);
// $select->class = 'pull-right clearfix';
// $select->label = 'Select Month';
// $singleselect1 = '<div class="w-100">' . $OUTPUT->render($select) . '</div>';
// echo $singleselect1;

// print_object($data);die;

$data['filters'] =  $renderer->filters();
echo $renderer->calendar_view($data);
echo $OUTPUT->footer();
