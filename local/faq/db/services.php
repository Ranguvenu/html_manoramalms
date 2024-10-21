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
 * Web service for local faq
 * @package    local_faq
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = array(
    'local_faq_delete_faqquery' => array(
        'classname' => 'local_faq_external',
        'methodname' => 'delete_faqquery',
        'classpath'   => 'local/faq/classes/external.php',
        'description' => 'Deleting Querie',
        'ajax' => true,
        'type' => 'read',
    ),

    'local_faq_delete_faqcategory' => array(
        'classname' => 'local_faq_external',
        'methodname' => 'delete_faqcategory',
        'classpath'   => 'local/faq/classes/external.php',
        'description' => 'Deleting Category',
        'ajax' => true,
        'type' => 'read',
    ),

    'local_faq_delete_faq_query'  => array(
        'classname'   => 'local_faq_external',
        'methodname'  => 'delete_faq_query',
        'classpath'   => 'local/faq/classes/external.php',
        'description' => 'category selector',
        'type'        => 'read',
        'ajax' => true,
    ),
    'local_faq_view_data'  => array(
        'classname'   => 'local_faq_external',
        'methodname'  => 'view_faq_data',
        'classpath'   => 'local/faq/classes/external.php',
        'description' => 'category and query data',
        'type'        => 'read',
        'ajax' => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);
