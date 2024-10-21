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
 * local onlineexams
 *
 * @package    local_onlineexams
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_onlineexams_submit_create_onlineexams_form' => array(
        'classname'   => 'local_onlineexams_external',
        'methodname'  => 'submit_create_onlineexams_form',
        'classpath'   => 'local/onlineexams/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    
    'local_onlineexams_deleteonlineexams' => array(
        'classname' => 'local_onlineexams_external',
        'methodname' => 'delete_onlineexams',
        'classpath'   => 'local/onlineexams/classes/external.php',
        'description' => 'deletion of onlineexams',
        'ajax' => true,
        'type' => 'write'
    ),
    // 'local_onlineexams_form_option_selector' => array(
    //     'classname' => 'local_onlineexams_external',
    //     'methodname' => 'global_filters_form_option_selector',
    //     'classpath' => 'local/onlineexams/classes/external.php',
    //     'description' => 'All global filters forms event handling',
    //     'ajax' => true,
    //     'type' => 'read',
    // ),
    'local_onlineexams_onlineexams_view' => array(
        'classname' => 'local_onlineexams_external',
        'methodname' => 'onlineexams_view',
        'classpath' => 'local/onlineexams/classes/external.php',
        'description' => 'List all onlineexams in card view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_onlineexams_course_update_status' => array(
        'classname' => 'local_onlineexams_external',
        'methodname' => 'course_update_status',
        'classpath' => 'local/onlineexams/classes/external.php',
        'description' => 'List all onlineexams in card view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_onlineexams_userdashboard_content' => array(
        'classname'    => 'local_onlineexams_external',
        'methodname'   => 'data_for_onlineexams',
        'classpath'    => 'local/onlineexams/classes/external.php',
        'description'  => 'Load the data for the elearning onlineexams in Userdashboard.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'local_onlineexams_userdashboard_content_paginated' => array(
        'classname'    => 'local_onlineexams_external',
        'methodname'   => 'data_for_onlineexams_paginated',
        'classpath'    => 'local/onlineexams/classes/external.php',
        'description'  => 'Load the data for the elearning onlineexams in Userdashboard.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'local_onlineexams_get_users_onlineexams_information' => array(
        'classname'    => 'local_onlineexams_external',
        'methodname'   => 'get_users_onlineexams_information',
        'classpath'    => 'local/onlineexams/classes/external.php',
        'description'  => 'Load the data for the user with status in Userdashboard.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),

   );

