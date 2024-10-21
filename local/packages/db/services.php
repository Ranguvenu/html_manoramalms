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
 * Web service for local goals
 * @package    local_packages
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = array(
        'local_packages_view' => array(
                'classname'   => 'local_packages_external',
                'classpath'   => 'local/packages/classes/external.php',
                'methodname'  => 'package_view',
                'description' => 'This documentation will be displayed in the generated
                                        API documentationAdministration > Plugins > Webservices > API documentation)',
                'type'        => 'write',
                'ajax'        => true,
        ),
        'local_packages_ajaxdatalist' => array(
                'classname' => 'local_packages_external',
                'methodname'  => 'ajaxdatalist',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Competencies data',
                'ajax' => true,
                'type' => 'read',
                'loginrequired' => false,
        ),
        'display_sessions' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'sessionsinfo',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Sessions view',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_packages_deletesession' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'deletesession',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Delete Session',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_package_createorupdate_batch' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'createorupdate_batch',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Create Course group and section while creating the batch',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_packagecreation' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'packagecreation',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Package Creation',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_package_deletepackage' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'delete_package',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Package Deletion',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_package_check_course_enrollment' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'check_course_enrollment',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Package Deletion',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_package_get_coursemodules' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'get_module_info',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Course activities info',
                'ajax' => true,
                'type' => 'read',
        ),    
        'local_packages_mycourses' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'mycourses_info',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Users Enrolled Course info',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    // A standard Moodle install includes one default service:
                    // - MOODLE_OFFICIAL_MOBILE_SERVICE.
                    // Specifying this service means that your function will be available for
                    // use in the Moodle Mobile App.
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),   
        'local_packages_recommended_courses' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'recommended_courses',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Recommended Course info',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),
        'local_packages_test_courses' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'test_courses',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Test info',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),
        'local_packages_due_activities' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'due_activities',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Due Activities information',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),
        'local_packages_data' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'get_packages_data',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Package information',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),
        'local_packages_hierarchy' => array(
                'classname' => 'local_packages_external',
                'methodname' => 'get_hierarchy_data',
                'classpath'   => 'local/packages/classes/external.php',
                'description' => 'Package information',
                'ajax' => true,
                'type' => 'read',
                'services' => [
                    MOODLE_OFFICIAL_MOBILE_SERVICE,
                ]
        ),
        'local_package_create_batchcourse' => array(
            'classname' => 'local_packages_external',
            'methodname' => 'create_batchcourse',
            'classpath'   => 'local/packages/classes/external.php',
            'description' => 'Service for Batch courses Migrartion',
            'ajax' => true,
            'type' => 'read',
            'services' => [
                MOODLE_OFFICIAL_MOBILE_SERVICE,
            ]
        ),
        'local_packages_get_quiz_attempt_data' => array(
            'classname' => 'local_packages_external',
            'methodname' => 'quiz_attempt_submit',
            'classpath'   => 'local/packages/classes/external.php',
            'description' => 'Quiz submit data',
            'ajax' => true,
            'type' => 'read',
            'services' => [
                MOODLE_OFFICIAL_MOBILE_SERVICE,
            ]
        ),
);
