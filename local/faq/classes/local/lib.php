<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 *
 * @package    local_faq
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

namespace local_faq;

use core_form\dynamic_form;
use dml_exception;
use moodle_url;
use context;
use context_system;
use html_writer;
use stdClass;
use core_course_category;
use local_goals\controller as goals;

require_once("{$CFG->dirroot}/course/lib.php");

defined('MOODLE_INTERNAL') || die;
class lib
{

    private static $_lib;
    private $dbHandle;
    public static function getInstance()
    {
        if (!self::$_lib) {
            self::$_lib = new lib();
        }
        return self::$_lib;
    }
}
