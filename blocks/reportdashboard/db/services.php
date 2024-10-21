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
 * Web service for mod assign
 * @package    mod_assign
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
'block_reportdashboard_userlist' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'userlist',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case userlist',
        'ajax' => true
    ),
'block_reportdashboard_reportlist' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'reportlist',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case reportlist',
        'ajax' => true
    ),
'block_reportdashboard_sendemails' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'sendemails',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case sendemails',
        'ajax' => true
    ),
'block_reportdashboard_inplace_editable_dashboard' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'inplace_editable_dashboard',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case dashboard name edit',
        'ajax' => true
    ),
'block_reportdashboard_addtiles_to_dashboard' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'addtiles_to_dashboard',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case Add Tiles to Dashboard',
        'ajax' => true
    ),
'block_reportdashboard_addwidget_to_dashboard' => array(
        'classname' => 'block_reportdashboard_external',
        'methodname' => 'addwidget_to_dashboard',
        'classpath' => 'blocks/reportdashboard/externallib.php',
        'description' => 'case Add widget to Dashboard',
        'ajax' => true
    ),
'block_reportdashboard_studentprofiledata_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'studentprofiledata_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
   ),
'block_reportdashboard_coursetabs_data' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'coursetabs_data',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_studentsdetails_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'studentsdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_student_profile' => array(
    'classname'     => 'block_reportdashboard_external',
    'classpath'     => 'blocks/reportdashboard/externallib.php',
    'methodname'    => 'student_profile',
    'description'   => 'This documentation will be displayed in the generated
    API documentationAdministration > Plugins > Webservices > API documentation)',
    'ajax'          => true,
    'type' => 'read',
    'services' => [
        MOODLE_OFFICIAL_MOBILE_SERVICE,
    ]
    ),
    'block_reportdashboard_package_list' => array(
        'classname'     => 'block_reportdashboard_external',
        'classpath'     => 'blocks/reportdashboard/externallib.php',
        'methodname'    => 'package_list',
        'description'   => 'This documentation will be displayed in the generated
        API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'          => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
),
'block_reportdashboard_reading_report_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'reading_report_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ),
'block_reportdashboard_liveclass_report_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'liveclass_report_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ),
'block_reportdashboard_testscore_report_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'testscore_report_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ),
'block_reportdashboard_practicequestions_report_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'practicequestions_report_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ),
'block_reportdashboard_chapterwisereport_report_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'chapterwisereport_report_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
        'type'          => 'read',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ),
'block_reportdashboard_coursedatedateselect_view' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'coursedatedateselect_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_liveclassdetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'liveclassdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_readingdetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'readingdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_practicetestdetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'practicetestdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_testscoredetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'testscoredetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_forumdetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'forumdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),
'block_reportdashboard_studentchapterdetails' => array(
        'classname'   => 'block_reportdashboard_external',
        'classpath'   => 'blocks/reportdashboard/externallib.php',
        'methodname'  => 'studentchapterdetails_view',
        'description' => 'This documentation will be displayed in the generated
                                API documentationAdministration > Plugins > Webservices > API documentation)',
        'ajax'        => true,
    ),

);
