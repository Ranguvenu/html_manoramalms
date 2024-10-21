<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_questions_viewquestions' => array(
	   'classname' => 'local_questions_external',
	   'methodname' => 'viewquestions',
	   'classpath'   => 'local/questions/classes/external.php',
	   'description' => 'Questions View',
	   'type' => 'write',
	   'ajax' => true,
    ),
    'local_questions_topic_selector' => array(
	   'classname' => 'local_questions_external',
	   'methodname' => 'topic_selector',
	   'classpath'   => 'local/questions/classes/external.php',
	   'description' => 'Questions View',
	   'type' => 'write',
	   'ajax' => true,
    ),
     'local_questions_concept_selector' => array(
	   'classname' => 'local_questions_external',
	   'methodname' => 'concept_selector',
	   'classpath'   => 'local/questions/classes/external.php',
	   'description' => 'Questions View',
	   'type' => 'write',
	   'ajax' => true,
    ),
	'local_questions_chapter_selector' => array(
		'classname' => 'local_questions_external',
		'methodname' => 'chapter_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Questions View',
		'type' => 'write',
		'ajax' => true,
	 ),
	 'local_questions_unit_selector' => array(
		'classname' => 'local_questions_external',
		'methodname' => 'unit_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Questions View',
		'type' => 'write',
		'ajax' => true,
	 ),
    'local_questions_course_selector' => array(
	   'classname' => 'local_questions_external',
	   'methodname' => 'course_selector',
	   'classpath'   => 'local/questions/classes/external.php',
	   'description' => 'Questions View',
	   'type' => 'write',
	   'ajax' => true,
    ),
    'local_questions_changequestionstatus'  => array(
	    'classname' => 'local_questions_external',
	    'methodname' => 'changequestionstatus',
	    'classpath'   => 'local/questions/classes/external.php',
	    'description' => 'Change Question Status',
	    'ajax' => true,
	    'type' => 'write',
   	),
	'local_questions_goal_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'goal_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Goal list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_board_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'board_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Board list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_class_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'class_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Board list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_questionid_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'questionid_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'Questions Id list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_allcourseslist_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'allcourseslist_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'All course list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_difficulty_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'difficulty_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'difficulty list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_cognitive_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'cognitive_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'cognitive list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_source_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'source_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'source list',
		'ajax' => true,
		'type' => 'write',
	),
	'local_questions_qstatus_selector'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'qstatus_selector',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'qstatus list',
		'ajax' => true,
		'type' => 'write',
	),
	'quiz_questions'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'quiz_questions',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'This service retrives the Questions in the Quiz',
		'ajax' => true,
		'type' => 'write',
	),
	'createuser_withsources'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'createuser_withsources',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'This service is used to Sync Souces',
		'ajax' => true,
		'type' => 'write',
	),
	'delete_userwithsources'  => array(
		'classname' => 'local_questions_external',
		'methodname' => 'delete_userwithsources',
		'classpath'   => 'local/questions/classes/external.php',
		'description' => 'This service is used to Sync Souces',
		'ajax' => true,
		'type' => 'write',
	),
    'local_questions_get_random_question_summaries' => array(
        'classname' => 'local_questions_external',
        'methodname' => 'get_random_question_summaries',
        'classpath'   => 'local/questions/classes/external.php',
        'description' => 'Get the random question set for a criteria',
        'type' => 'read',
        'ajax' => true,
    ),
	'local_questions_create_sources' => array(
        'classname' => 'local_questions_external',
        'methodname' => 'create_sources',
        'classpath'   => 'local/questions/classes/external.php',
        'description' => 'Sources from Laravel',
        'type' => 'read',
        'ajax' => true,
    ),
    'local_questions_regrade_all_questions' => array(
        'classname' => 'local_questions_external',
        'methodname' => 'regrade_all_questions',
        'classpath'   => 'local/questions/classes/external.php',
        'description' => 'Regrade questions as per configurations',
        'type' => 'write',
        'ajax' => true,
    ),
);
