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

$mdluser = required_param('mdluser', PARAM_INT);
$wpuser = required_param('wpuser', PARAM_INT);
$code = required_param('code', PARAM_RAW);
$action = required_param('action', PARAM_TEXT);
if ($action == 'login') {
    $ssolib = new sso_lib();
    $redirect = $ssolib->get_laravel_site_url().'?action=login&mdluser='.$mdluser.'&wpuser='.$wpuser.'&code='.$code;
    redirect($redirect);
} else if ($action == 'logout') {
    $ssolib = new sso_lib();
    $redirect = $ssolib->get_laravel_site_url().'/integrations/sso/logout';
    redirect($redirect);
}
