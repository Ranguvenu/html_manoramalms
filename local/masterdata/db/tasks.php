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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$tasks = [
    [
        'classname' => 'local_masterdata\task\get_mastercourse_structure',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '1',
        'day' => '1',
        'month' => '1',
        'dayofweek' => '1',
    ],
    [
		'classname' => 'local_masterdata\task\liveclass_invite',
		'blocking' => 0,
    'minute' => '0',
    'hour' => '7',
		'day' => '*/1',
		'dayofweek' => '*',
		'month' => '*',
    ],
    [
      'classname' => 'local_masterdata\task\liveclass_reminder',
      'blocking' => 0,
      'minute' => '*',
      'hour' => '*',
      'day' => '*/1',
      'dayofweek' => '*',
      'month' => '*',
    ]
];

