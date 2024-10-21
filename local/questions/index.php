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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE, $OUTPUT, $DB;
$systemcontext = context_system::instance();
require_capability('local/questions:questionallow', $systemcontext);
$PAGE->set_url(new moodle_url('/local/questions/index.php'));
$PAGE->set_context($systemcontext);
require_login();
$PAGE->set_title(get_string('pluginname', 'local_questions'));
$PAGE->set_heading(get_string('pluginname', 'local_questions'));
echo $OUTPUT->header();
  //(new local_questions\local\questionbank)->questionsinfo();

$pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
$category = $pcategory.','.$systemcontext->id;
redirect(new moodle_url('/local/questions/questionbank_view.php?courseid=1&cat='.$category));

echo $OUTPUT->footer();
