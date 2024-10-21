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
 * Languages configuration for the local_packages plugin.
 *
 * @package   local_packages
 * @copyright 2023, MOODLE India <jahnavi.nanduri@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
$string['pluginname'] = 'Packages';
$string['nooftopics'] = 'No. of Topics';
$string['totalseats'] = 'Total seats';
$string['addsessions'] = 'Add Sessions';
$string['viewsessions'] = 'View Sessions';
$string['totalclassrooms'] = 'Total Classrooms';
$string['practisetest'] = 'Practise Test';
$string['totalsessions'] = 'Total Sessions';
$string['subjects'] = 'Subjects';
$string['batches'] = 'Batches';
$string['batchcode'] = 'Code';
$string['enroldate'] = 'Enrol Date';
$string['duration'] = 'Duration';
$string['studentslimit'] = 'Student Limit';
$string['package'] = 'Packages';
$string['search'] = 'Search';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['courses'] = 'Courses';
$string['batchname'] = 'Batch Name';
$string['schedulecode'] = 'Schedule Code';
$string['starttime'] = 'Start Time';
$string['endtime'] = 'End Time';
$string['teacher'] = 'Teacher';
$string['batchrequired'] = 'Please select batch.';
$string['schedulecoderequired'] = 'Schedule code can not be empty.';
$string['teacherrequired'] = 'Please select teacher.';
$string['schedulecodeexists'] = 'Given schedule code "<b>{$a}</b>" already exists.';
$string['viewcoursesessions'] = 'View "<b>{$a}</b>" course sessions';
$string['am'] = 'AM';
$string['pm'] = 'PM';
$string['nosessions'] = 'No Sessions Found.';
$string['code'] = 'Code';
$string['action'] = 'Action';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['deleteconfirm'] = 'Delete Confirm!';
$string['sessiondeletebodymessage'] = 'Do you want to delete the session having batch name "<b>{$a->batchname}</b>" and schedule code  "<b>{$a->schedulecode}</b>"?';
$string['deletetext'] = 'Yes! Delete';
$string['addsession'] = 'Add Session';
$string['updatesession'] = 'Update Session';
$string['todatelower'] = 'End date must not be lower than the start date';
$string['previousdate'] = 'Please select future date';
$string['starttimecannotbelessthannow']='Start time can not be the past time';
$string['endtimecannotbelessthannow']='End time can not be the past time';
$string['endtimeshuldhigher'] = 'End time need to be higher than start time';
$string['nopackages'] = 'No Packages Found.';
$string['cantbelowerthanbatchstartdate'] = 'Session start date must be equal or higher than selected batch ("<b>{$a->name}</b>") start date "<b>{$a->batchstartdate}</b>"';
$string['cantbehigherthanbatchenddate'] = 'Session end date must be lower than selected batch ("<b>{$a->name}</b>") end date "<b>{$a->batchenddate}</b>"';
$string['course_only'] = 'Course Only';
$string['test_only'] = 'Test Only';
$string['midnight'] = 'Midnight';
$string['packagesurl'] = 'Packages Url';
$string['packagesurl_help'] = 'Packages Url from laravel instance';
$string['hierarchyurl'] = 'Hierarchy Url';
$string['hierarchyurl_help'] = 'Hierarchy Url from laravel instance';
$string['displayablegoals'] = 'Displayable Goals';
$string['displayablegoals_help'] = 'Displayable Goals from laravel instance';
$string['settings'] = 'Package Service Settings';
$string['courseflexsections'] = 'Course Flex-Sections';
$string['packages:accesssitefromlms'] = 'Navigate to Laravel Site';
