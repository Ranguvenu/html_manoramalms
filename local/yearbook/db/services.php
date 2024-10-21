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
 * Web service for local yearbook
 * @package    local_yearbook
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_yearbook_begin_quiz' => array(
        'classname'   => 'local_yearbook_external', // Create this class in componentdir/classes/external.
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'begin_quiz', // Implement this function into the above class.
        'description' => 'This documentation will be displayed in the generated
                            API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
        'capabilities'  => 'mod/quiz:attempt',
        
        //'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
        // 'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    'local_yearbook_quiz_list' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'quiz_list', 
        'description' => 'This is used for the list of the quiz',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
    'local_yearbook_submit_answer' => array(
        'classname'   => 'local_yearbook_external', // Create this class in componentdir/classes/external.
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'submit_answer', // Implement this function into the above class.
        'description' => 'This is used for the quiz answer submission',
        'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
    'local_yearbook_finish_quiz' => array(
        'classname'   => 'local_yearbook_external', // Create this class in componentdir/classes/external.
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'finish_quiz', // Implement this function into the above class.
        'description' => 'This is for the quiz finish',
        'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
        'capabilities'  => 'mod/quiz:attempt',
        
        //'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
        // 'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
   'local_yearbook_review_answers' => array(
        'classname'   => 'local_yearbook_external', // Create this class in componentdir/classes/external.
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'review_answers', // Implement this function into the above class.
        'description' => 'This is for reviewing answers of the quiz',
        'type'        => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
        'capabilities'  => 'mod/quiz:reviewmyattempts',
        
        //'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
        // 'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    'local_yearbook_package_list' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'package_list', 
        'description' => 'This is used for package list of the active mocktest',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),

    'local_yearbook_package_test_list' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'package_test_list', 
        'description' => 'This is used for package tests list by packageid',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
    'local_yearbook_my_performance' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'my_performance', 
        'description' => 'This is used to get the loggedin user performance',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
    'local_yearbook_test_performance' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'test_performance', 
        'description' => 'This is used to get the loggedin user test performance',
        'type'        => 'read', 
        'capabilities'  => 'mod/quiz:reviewmyattempts',
        
    ),
    'local_yearbook_skip_question' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'skip_question', 
        'description' => 'This is skip question service',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
    'local_yearbook_skip_question_default' => array(
        'classname'   => 'local_yearbook_external', 
        'classpath'   => 'local/yearbook/classes/external.php',
        'methodname'  => 'skip_question_default', 
        'description' => 'This is skip question default service',
        'type'        => 'write', 
        'capabilities'  => 'mod/quiz:attempt',
        
    ),
);
$services = array(

  'Yearbook V2' => array(
    'functions' => [
            'local_yearbook_skip_question_default',
            'local_yearbook_skip_question',
            'local_yearbook_test_performance',
            'local_yearbook_my_performance',
            'local_yearbook_package_test_list',
            'local_yearbook_package_list',
            'local_yearbook_review_answers',
            'local_yearbook_finish_quiz',
            'local_yearbook_submit_answer',
            'local_yearbook_quiz_list',
            'local_yearbook_begin_quiz',
        ],
    'requiredcapability' => '',
    'restrictedusers' => 0,
    'enabled' => 1,
    'shortname' =>  'yearbookv2',
    'downloadfiles' => 0,
    'uploadfiles'  => 0
  ),
);
