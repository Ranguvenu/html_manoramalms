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
 * Return token
 * @package    moodlecore
 * @copyright  2011 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');

global $DB, $CFG, $USER;
require_once($CFG->dirroot.'/local/yearbook/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

// Allow CORS requests.
header('Access-Control-Allow-Origin: *');

if (!$CFG->enablewebservices) {
    throw new yearbook_exception('enablewsdescription', 'webservice');
}

// This script is used by the mobile app to check that the site is available and web services
// are allowed. In this mode, no further action is needed.
if (optional_param('appsitecheck', 0, PARAM_INT)) {
    echo json_encode((object)['appsitecheck' => 'ok']);
    exit;
}

$mobile = required_param('mobile', core_user::get_property_type('phone2'));
$ssoid = required_param('ssoid', PARAM_RAW);
$clientid = required_param('clientid', PARAM_RAW);
$email = optional_param('email', '', core_user::get_property_type('email'));
$mailid = trim($email);
$username = optional_param('username', $mailid, PARAM_USERNAME);
$password = optional_param('password', 'Welcome#3', core_user::get_property_type('password'));
$uflname = explode('@', $email);
$firstname = optional_param('firstname', $uflname[0], core_user::get_property_type('firstname'));
$lastname = optional_param('lastname', $uflname[0], core_user::get_property_type('lastname'));

$localsettings = get_config('local_yearbook');
$serviceshortname = 'yearbookv2';
echo $OUTPUT->header();

if ($username) {
    $username = trim(core_text::strtolower($username));
    if (is_restored_user($username)) {
        throw new yearbook_exception('restoredaccountresetpassword', 'webservice');
    }
}
if (empty($email)) {
    $errorobj = new stdClass();
    $errorobj->status = 'failed';
    $errorobj->message = get_string('emailnotfound', 'local_yearbook');
    $errorobj->code = 0;
    echo json_encode($errorobj);
    exit;
}
$systemcontext = context_system::instance();

$reason = null;
$user = null;
if ($localsettings->clientid == $clientid) {
    $params = [];
    $idnum = trim($ssoid);
    $params['ssoval'] = $idnum;
    $params['email'] = $mailid;
    $params['shortname'] = 'ssoid';
    $params['deleted'] = 0;
    $params['suspended'] = 0;
    $usrsql = "SELECT u.id 
                 FROM {user} u
                 JOIN {user_info_data} uid ON uid.userid = u.id
                 JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE (uid.data = :ssoval
                       OR u.email = :email)
                  AND uif.shortname = :shortname
                  AND u.deleted = :deleted AND u.suspended = :suspended ";
    $users = $DB->get_fieldset_sql($usrsql, $params);
    if (count($users) == 1) {
        $userobj = core_user::get_user($users[0]);
        $userobj->email = $mailid;
        $userobj->confirmed = 1;
        unset($userobj->password);
        if (!empty($mobile)) {
            $mobile = trim($mobile);
            $userobj->phone1 = $mobile;
        }

        if (!empty($firstname)) {
            $firstname = trim($firstname);
            $userobj->firstname = $firstname;
        }

        if (!empty($lastname)) {
            $lastname = trim($lastname);
            $userobj->lastname = $lastname;
        }
        $username = $mailid;
        if (!empty($username)) {
            $userobj->username = $username;
        }
        user_update_user($userobj, false);
        $userobj->profile_field_ssoid = $idnum;
        profile_save_data($userobj);
    } else if (count($users) == 0) {
        $userobj = new stdClass();
        $userobj->confirmed = 1;
        $userobj->policyagreed = 0;
        $userobj->deleted = 0;
        $userobj->suspended = 0;
        $userobj->mnethostid = 1;
        $uname = $mailid;
        if (!empty($username)) {
            $uname = trim($username);
        }
        $userobj->password = md5($password);
        $userobj->username = $uname;
        $userobj->firstname = $firstname;
        $userobj->lastname = $lastname;
        $userobj->email = $mailid;
        $userobj->emailstop = 0;
        $userobj->phone1 = trim($mobile);
        $userobj->descriptionformat = 1;
        $userobj->mailformat = 1;
        $userobj->maildigest = 0;
        $userobj->maildisplay = 2;
        $userobj->autosubscribe = 1;
        $userobj->trackforums = 0;
        $userobj->trustbitmask = 0;
        $userobj->timecreated = time();
        $userobj->id = user_create_user($userobj, false);
        $userobj->profile_field_ssoid = $idnum;
        profile_save_data($userobj);
    } else {
        throw new yearbook_exception('This user already exists with other details, please provide correct details and try again');
    }
    $user = \core_user::get_user($userobj->id);
    // Cannot authenticate unless maintenance access is granted.
    $hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', $systemcontext, $user);
    if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
        throw new yearbook_exception('sitemaintenance', 'admin');
    }

    // if (isguestuser($user)) {
    //     throw new yearbook_exception('noguest');
    // }
    // if (empty($user->confirmed)) {
    //     throw new yearbook_exception('usernotconfirmed', 'moodle', '', $user->username);
    // }
    // check credential expiry
    $userauth = get_auth_plugin($user->auth);
    if (!empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
        $days2expire = $userauth->password_expire($user->username);
        if (intval($days2expire) < 0 ) {
            throw new yearbook_exception('passwordisexpired', 'webservice');
        }
    }

    // let enrol plugins deal with new enrolments if necessary
    enrol_check_plugins($user);

    // setup user session to check capability
    \core\session\manager::set_user($user);

    //check if the service exists and is enabled
    $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
    if (empty($service)) {
        // will throw exception if no token found
        throw new yearbook_exception('servicenotavailable', 'webservice');
    }

    $yearbookcatid = $DB->get_field('course_categories', 'id', ['idnumber' => 'yearbookv2']);
    // enrolment happens if not enrolled to the yearbook courses.
    // $enrolment = isenrolled_to_yearbook2_category_courses($yearbookcatid, $user);
    $roleid = $DB->get_field('role', 'id', ['archetype' => 'student', 'shortname' => 'student']);
    $categorycontext = context_coursecat::instance($yearbookcatid);
    $enrolment = role_assign($roleid, $user->id, $categorycontext->id);
    if ($enrolment) {
        // Get an existing token or create a new one.
        $token = \core_external\util::generate_token_for_current_user($service);
        $privatetoken = $token->privatetoken;
        \core_external\util::log_token_request($token);

        $siteadmin = has_capability('moodle/site:config', $systemcontext, $USER->id);

        $usertoken = new stdClass();
        $usertoken->status = 'success';
        $usertoken->token = $token->token;
        $usertoken->code = 1;
        $usertoken->first_name = $user->firstname;
        // Private token, only transmitted to https sites and non-admin users.
        // if (is_https() and !$siteadmin) {
        //     $usertoken->privatetoken = $privatetoken;
        // } else {
        //     $usertoken->privatetoken = null;
        // }
        echo json_encode($usertoken);
    } else {
        throw new yearbook_exception('There is a problem with the enrolment process, please contact admin for more info.');
    }
} else {
    $usrtoken = new stdClass();
    $usrtoken->status = 'failed';
    $usrtoken->message = get_string('clientidmismatch', 'local_yearbook');
    $usrtoken->code = 0;
    echo json_encode($usrtoken);
    exit;
}
