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
 * TODO describe file duplicatestudymaterial
 *
 * @package    local_studymaterial
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use local_studymaterial\local\studymaterial as studymaterial;

require_login();
$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$url = new moodle_url('/local/studymaterial/duplicatestudymaterial.php', ['id' => $id, 'courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);
$studymaterialid = (new studymaterial)->duplicate_studymaterial($id, $courseid);
echo "Duplicated Successfully..";die;
