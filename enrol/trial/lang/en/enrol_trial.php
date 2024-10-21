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
 * Strings for component 'enrol_trial', language 'en'.
 *
 * @package    enrol_trial
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['advanced'] = 'Advanced';
$string['alterstatus'] = 'Alter status';
$string['altertimeend'] = 'Alter end time';
$string['altertimestart'] = 'Alter start time';
$string['assignrole'] = 'Assign role';
$string['assignroles'] = 'Assign roles';
$string['browseusers'] = 'Browse users';
$string['browsecohorts'] = 'Browse cohorts';
$string['confirmbulkdeleteenrolment'] = 'Are you sure you want to delete these user enrolments?';
$string['defaultstart'] = 'Default enrolment start';
$string['defaultperiod'] = 'Default enrolment duration';
$string['defaultperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['defaultperiod_help'] = 'Default length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited by default.';
$string['deleteselectedusers'] = 'Delete selected user enrolments';
$string['editselectedusers'] = 'Edit selected user enrolments';
$string['enrolledincourserole'] = 'Enrolled in "{$a->course}" as "{$a->role}"';
$string['enrolusers'] = 'Enrol users';
$string['enroluserscohorts'] = 'Enrol selected users and cohorts';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['expirymessageenrollersubject'] = 'Enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'Enrolment expiry notification';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['managetrialenrolements'] = 'Manage Trial enrolments';
$string['trial:config'] = 'Configure trial enrol instances';
$string['trial:enrol'] = 'Enrol users';
$string['trial:manage'] = 'Manage user enrolments';
$string['trial:unenrol'] = 'Unenrol users from the course';
$string['trial:unenrolself'] = 'Unenrol self from the course';
$string['messageprovider:expiry_notification'] = 'trial enrolment expiry notifications';
$string['now'] = 'Now';
$string['pluginname'] = 'Trial enrolments';
$string['pluginname_desc'] = 'The Trial enrolments plugin allows users to be enrolled via a link in the course administration settings, by a user with appropriate permissions such as a teacher. The plugin should normally be enabled, since certain other enrolment plugins, such as self enrolment, require it.';
$string['selection'] = 'Selection';
$string['selectusers'] = 'Select users';
$string['selectcohorts'] = 'Select cohorts';
$string['sendexpirynotificationstask'] = "trial enrolment send expiry notifications task";
$string['status'] = 'Enable trial enrolments';
$string['status_desc'] = 'Allow course access of internally enrolled users. This should be kept enabled in most cases.';
$string['status_help'] = 'This setting determines whether users can be enrolled, via a link in the course administration settings, by a user with appropriate permissions such as a teacher.';
$string['statusenabled'] = 'Enabled';
$string['statusdisabled'] = 'Disabled';
$string['syncenrolmentstask'] = 'Synchronise Trial enrolments task';
$string['unenrol'] = 'Unenrol user';
$string['unenrolselectedusers'] = 'Unenrol selected users';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenroluser'] = 'Do you really want to unenrol "{$a->user}" from course "{$a->course}"?';
$string['unenrolusers'] = 'Unenrol users';
$string['wscannotenrol'] = 'Plugin instance cannot enrol a user in the course id = {$a->courseid}';
$string['wsnoinstance'] = 'trial enrolment plugin instance doesn\'t exist or is disabled for the course (id = {$a->courseid})';
$string['wsusercannotassign'] = 'You don\'t have the permission to assign this role ({$a->roleid}) to this user ({$a->userid}) in this course ({$a->courseid}).';
$string['trialpluginnotinstalled'] = 'The "trial" plugin has not yet been installed';
$string['privacy:metadata'] = 'The Trial enrolments plugin does not store any personal data.';
