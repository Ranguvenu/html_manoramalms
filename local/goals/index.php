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
 * local_goals
 * @package local_goals
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');

global $CFG, $PAGE, $OUTPUT, $DB;
$PAGE->requires->jquery();
require_login();
$systemcontext = \context_system::instance();
require_capability('local/goals:manage', $systemcontext);
$PAGE->set_url(new moodle_url('/local/goals/index.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('hierarchy', 'local_goals'));
$PAGE->set_heading(get_string('hierarchy', 'local_goals'));
$PAGE->navbar->add(get_string('hierarchy', 'local_goals'), new moodle_url('/local/goals/index.php'));
echo $OUTPUT->header();
$goalsrender = $PAGE->get_renderer('local_goals');
$goalsdata = (new local_goals\controller)->get_goals();
$filterparams = $goalsrender->get_goals_view(true);
$filtercontent = $OUTPUT->render_from_template('theme_horizon/global_filter', $filterparams);
echo $OUTPUT->render_from_template('local_goals/form', ['filter_content' => $filtercontent]);
echo $goalsrender->get_goals_view();
echo $OUTPUT->footer();
