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
 * External functions and service declaration for Notification
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    block_notification
 * @category   webservice
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_notification_calendar_details' => array(
        'classname'   => 'block_notification_external', // Create this class in componentdir/classes/external.
        'classpath'   => 'blocks/notification/classes/external.php',
        'methodname'  => 'get_calendar_details', // Implement this function into the above class.
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
        'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
),
];

$services = [
];