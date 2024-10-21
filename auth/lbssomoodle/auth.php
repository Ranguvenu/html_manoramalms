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
require_once($CFG->libdir.'/authlib.php');
use auth_lbssomoodle\lib as sso_lib;

class auth_plugin_lbssomoodle extends auth_plugin_base {
    
    /**
     * Main Constructor
     *
     */
    public function __construct() {
        $this->authtype = 'lbssomoodle';
        $this->config = get_config('auth_lbssomoodle');
        // $this->sso_lib = new sso_lib();
    }

    public function user_login($username, $password) {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username, 'password' => $password));
        if ($user) {
            return true;
        }
        return false;
    }

    public function user_authenticated_hook(&$user, $username, $password) {
        return true;
    }

    public function logoutpage_hook() {
        global $USER, $redirect, $CFG;
        $redirect = $CFG->wwwroot.'/auth/lbssomoodle/lblogin.php?action=logout';
        $redirect .= '&mdluser='.$USER->id.'&wpuser='.$payload['wp_user_id'].'&code='.$payload['hash'];
    }
}