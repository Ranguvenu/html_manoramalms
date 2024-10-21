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
require('../../config.php');

use auth_lbssomoodle\lib as sso_lib;
global $DB, $CFG;

$userid = required_param('mdl_user_id', PARAM_INT);
$code = required_param('code', PARAM_RAW);
$courseid = optional_param('course_id', '', PARAM_INT);
$action = required_param('action', PARAM_TEXT);

$ssolib = new sso_lib();
$isvalidrequest = $ssolib->is_authorized_request($userid, $code);
$redirecto = $ssolib->get_laravel_site_url();
if ($isvalidrequest) {
    $params = $ssolib->retrive_params_from_hashdata($userid, $code);
    if ($action == 'login') {
        if ($DB->record_exists('user', array('id' => $userid))) {
            $user = get_complete_user_data('id', $userid);
            $auth = get_auth_plugin('lbssomoodle');
            if ($auth->user_login($user->username,  $user->password)) {
                $user->loggedin = true;
                $user->site = $CFG->wwwroot;
                complete_user_login($user);
                if($params && isset($params['login_redirect'])){
                    $redirecto = $params['login_redirect'];
                }else{
                    $redirecto = $CFG->wwwroot;
                }
            }
            if ($courseid) {
                $redirecto = $CFG->wwwroot.'/course/view.php?id='.$courseid;
            }
        }
    } else if ($action == 'logout') {
        if($params && isset($params['logout_redirect'])){
            $redirectto = $params['logout_redirect'];
        }
        require_logout();
    }
    $ssolib->unset_lbsso_user_session($userid);
}
redirect($redirecto);
