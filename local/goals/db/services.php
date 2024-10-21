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
 * @package    local_goals
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = array(
        'local_goals_view' => array(
                'classname'   => 'local_goals_external', // Create this class in componentdir/classes/external.
                'classpath'   => 'local/goals/classes/external.php',
                'methodname'  => 'goals_view', // Implement this function into the above class.
                'description' => 'This documentation will be displayed in the generated
                                        API documentationAdministration > Plugins > Webservices > API documentation)',
                'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
        ),
        'local_goals_createbatch' => array( // LOCAL_PLUGINNAME_FUNCTIONNAME.
                'classname'   => 'local_goals_external', // Create this class in componentdir/classes/external.
                'classpath'   => 'local/goals/classes/external.php',
                'methodname'  => 'create_batch', // Implement this function into the above class.
                'description' => 'This documentation will be displayed in the generated
                                        API documentationAdministration > Plugins > Webservices > API documentation)',
                'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.

        ),
        'local_goals_deletecomponent' => array(
                'classname' => 'local_goals_external',
                'methodname' => 'deletecomponent',
                'classpath'   => 'local/goals/classes/external.php',
                'description' => 'Deleting all component like goals/classes/subjects',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_goals_candeletecomponent' => array(
                'classname' => 'local_goals_external',
                'methodname' => 'candeletecomponent',
                'classpath'   => 'local/goals/classes/external.php',
                'description' => 'Responsibility view',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_goals_create_hierarchies' => array(
                'classname' => 'local_goals_external',
                'methodname' => 'create_hierarchies',
                'classpath'   => 'local/goals/classes/external.php',
                'description' => 'Creating hierarchies',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_goals_delete_hierarchies' => array(
                'classname' => 'local_goals_external',
                'methodname' => 'delete_hierarchy',
                'classpath'   => 'local/goals/classes/external.php',
                'description' => 'Deleting hierarchies',
                'ajax' => true,
                'type' => 'read',
        ),        
);
