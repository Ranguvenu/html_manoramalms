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
 * local_packages
 * @package local_packages
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');

global $CFG, $PAGE, $OUTPUT, $DB;
$PAGE->requires->jquery();
require_login();
$systemcontext = context_system::instance();
require_capability('local/packages:manage', $systemcontext);
$PAGE->set_url(new moodle_url('/local/packages/index.php'));
$PAGE->set_context(context_system::instance());

$PAGE->set_title(get_string('package', 'local_packages'));
$PAGE->set_heading(get_string('package', 'local_packages'));
$PAGE->navbar->add(get_string('package', 'local_packages'), new moodle_url('/local/packages/index.php'));
echo $OUTPUT->header();
$packagesrender = $PAGE->get_renderer('local_packages');
//$packagesdata = (new local_packages\controller)->get_packages();
$filterparams = $packagesrender->get_packages_view(true);
$filtercontent = $OUTPUT->render_from_template('theme_horizon/global_filter', $filterparams);
echo $OUTPUT->render_from_template('local_packages/form', ['filter_content' => $filtercontent]);
echo $packagesrender->get_packages_view();
echo $OUTPUT->footer();
