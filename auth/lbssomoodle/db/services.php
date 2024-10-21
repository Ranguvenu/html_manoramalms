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
 * Webservices
 *
 * @version    1.0.0
 * @package    auth_lbssomoodle
 * @author     Ranga Reddy<rangareddy@eabyas.com>
 * @copyright  2023 Ranga Reddy (https://eabyas.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'auth_lbssomoodle_test_connection'  => array(
        'classname'     => 'auth_lbssomoodle_external',
        'methodname'    => 'test_connection',
        'classpath'     => 'auth/lbssomoodle/classes/external.php',
        'description'   => 'Tests the connection between moodle & wordpress and validates encryption key',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => false,
        'loginrequired' => false
    ),
    'auth_lbssomoodle_set_user_preference'  => array(
        'classname'     => 'auth_lbssomoodle_external',
        'methodname'    => 'set_user_preference',
        'classpath'     => 'auth/lbssomoodle/classes/external.php',
        'description'   => 'Sets the hash data created on wordpress ',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => false,
        'loginrequired' => false
    ),
    'auth_lbssomoodle_check_user_existance' => array(
        'classname'     => 'auth_lbssomoodle_external',
        'methodname'    => 'check_user_existance',
        'classpath'     => 'auth/lbssomoodle/classes/external.php',
        'description'   => 'Sets the hash data created on wordpress ',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => false,
        'loginrequired' => false
    )
);
