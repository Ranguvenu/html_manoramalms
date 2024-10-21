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
 * Package details view
 *
 * @package   local_packages
 * @copyright 2023, MOODLE India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $OUTPUT, $PAGE;
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();
$packageid = optional_param('packageid', 0, PARAM_INT);
$packagerecord = $DB->get_record('local_hierarchy', array('id' => $packageid, 'depth' => 4), '*');
$PAGE->set_title($packagerecord->name);
$PAGE->set_url(new moodle_url('/local/packages/packagedetails.php?packageid='.$packageid));
$returnurl = new moodle_url('/local/packages/packagedetails.php', array('packageid' => $packageid));
$PAGE->navbar->add(get_string('package', 'local_packages'), new moodle_url('/local/packages/index.php'));
$PAGE->navbar->add($packagerecord->name, $returnurl);
$PAGE->set_heading($packagerecord->name);
echo $OUTPUT->header();
(new local_packages\local\packages)->packagedetails_overview($packagerecord);
echo $OUTPUT->footer();
