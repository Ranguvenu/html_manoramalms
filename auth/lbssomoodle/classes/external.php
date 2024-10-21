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
 * Version information
 *
 * @version    1.0.0
 * @package    auth_lbssomoodle
 * @author     Ranga Reddy<ranga.seguri@moodle.com>
 * @copyright  2023 Ranga Reddy (https://moodle.com/in)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use auth_lbssomoodle\lib as sso_lib;

class auth_lbssomoodle_external extends external_api {

    public static function test_connection_parameters() {
        return new external_function_parameters(
            array(
                'encryptionkey'    => new external_value(PARAM_TEXT, 'encryptionkey')
            )
        );
    }

    public static function test_connection($encryptionkey) {
        $lib = new sso_lib();
        return $lib->validate_encryption_key($encryptionkey);
    }

    public static function test_connection_returns() {
        return new external_single_structure(
            array(
                'success'   => new external_value(PARAM_BOOL, 'success'),
                'message'   => new external_value(PARAM_RAW, 'message')
            )
        );
    }

    public static function set_user_preference_parameters() {
        return new external_function_parameters(
            array(
                'userid'       => new external_value(PARAM_INT, 'userid', VALUE_REQUIRED),
                'hashdata'     => new external_value(PARAM_RAW, 'hashdata', VALUE_REQUIRED),
            )
        );
    }

    public static function set_user_preference($userid, $hashdata) {
        $lib = new sso_lib();
        $lib->set_lbsso_user_session($userid, $hashdata);
        return array(
            'success'   => true,
            'message'   => get_string('set_token', 'lbssomoodle')
        );
    }

    public static function set_user_preference_returns() {
        return new external_single_structure(
            array(
                'success'   => new external_value(PARAM_BOOL, 'success'),
                'message'   => new external_value(PARAM_RAW, 'message')
            )
        );
    }

    public static function check_user_existance_parameters() {
        return new external_function_parameters(
            array(
                'username'       => new external_value(PARAM_RAW, 'username', VALUE_REQUIRED),
            )
        );
    }

    public static function check_user_existance($username) {
        global $DB;
        $userid = $DB->get_field('user', 'id', array('email' => $username), IGNORE_MISSING);
        return array(
            'user_id'       => $userid
        );
    }

    public static function check_user_existance_returns() {
        return new external_single_structure(
            array(
                'user_id'   => new external_value(PARAM_RAW, 'user_id'),
            )
        );
    }
}
