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
 * Web service for local studymaterial
 * @package    local_studymaterial
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = array(
        'local_studymaterial_view' => array(
                'classname'   => 'local_studymaterial_external', // Create this class in componentdir/classes/external.
                'classpath'   => 'local/studymaterial/classes/external.php',
                'methodname'  => 'studymaterial_view', // Implement this function into the above class.
                'description' => 'This documentation will be displayed in the generated
                                        API documentationAdministration > Plugins > Webservices > API documentation)',
                'type'        => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax'        => true, // True/false if you allow this web service function to be callable via ajax.
        ),
        'local_studymaterial_deletestudymaterial' => array(
                'classname' => 'local_studymaterial_external',
                'methodname' => 'deletestudymaterial',
                'classpath'   => 'local/studymaterial/classes/external.php',
                'description' => 'Deleting Study Material',
                'ajax' => true,
                'type' => 'read',
        ),
        
);
