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
 * Goals hierarchy
 *
 * This file defines the current version of the local_faq Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_faq
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

use local_faq\controller as controller;
use cache;
use \core_external\external_api;
use context_system;
use external_value;
use external_function_parameters;

require_once("{$CFG->dirroot}/course/externallib.php");

/**
 * local_faq_external [description]
 */
class local_faq_external extends external_api
{

    public static function delete_faqquery_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'type' => new external_value(PARAM_RAW, 'type'),
            )
        );
    }
    public static function delete_faqquery($id, $type)
    {
        $systemcontext = context_system::instance();
        $params = self::validate_parameters(
            self::delete_faqquery_parameters(),
            [
                'id' => $id,
                'type' => $type,
            ]
        );

        $result = (new local_faq\controller)->delete_faqquery($params);

        return ['result' => $result];
    }
    public static function delete_faqquery_returns()
    {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'result'),
        ]);
    }

    public static function delete_faqcategory_parameters()
    {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }
    public static function delete_faqcategory($action, $id, $confirm)
    {
        $systemcontext = context_system::instance();
        $params = self::validate_parameters(
            self::delete_faqcategory_parameters(),
            [
                'id' => $id,
            ]
        );
        if ($confirm) {
            $result = (new controller)->delete_faqcategory($params);

            return ['result' => $result];
        } else {
            $return = false;
        }
    }
    public static function delete_faqcategory_returns()
    {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'result'),
        ]);
    }

    public static function delete_faq_query_parameters()
    {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }
    public static function delete_faq_query($action, $id, $confirm)
    {
        $systemcontext = context_system::instance();
        $params = self::validate_parameters(
            self::delete_faq_query_parameters(),
            [
                'id' => $id,
            ]
        );
        if ($confirm) {
            $result = (new controller)->delete_faqquery($params);

            return ['result' => $result];
        } else {
            $return = false;
        }
    }
    public static function delete_faq_query_returns()
    {
        return new external_single_structure([]);
    }

    public static function view_faq_data_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function view_faq_data()
    {
        $systemcontext = context_system::instance();

        $result = (new controller)->view_faq_data();
        return $result;
    }

    public static function view_faq_data_returns()
    {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Category ID'),
                    'name' => new external_value(PARAM_TEXT, 'Category Name'),
                    'description' => new external_value(PARAM_RAW, 'Category Description'),
                    'queries' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Query ID'),
                            'name' => new external_value(PARAM_TEXT, 'Query Name'),
                            'description' => new external_value(PARAM_RAW, 'Query Description'),
                            'attachments' => new external_value(PARAM_RAW, 'Query Attachments'),
                        ])
                    ),
                ])
            ),
        ]);
    }
}
