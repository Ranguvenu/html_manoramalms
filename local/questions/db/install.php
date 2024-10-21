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
defined('MOODLE_INTERNAL') || die();
function xmldb_local_questions_install(){
    global $CFG,$DB;
    require_once($CFG->libdir . '/questionlib.php');
    $thiscontext = context_system::instance();

    $edittab = 'categories';
     if ($thiscontext){
                    $contexts = new question_edit_contexts($thiscontext);
                    $contexts->require_one_edit_tab_cap($edittab);
    } else {
                    $contexts = null;
    }
    $defaultcategory = question_make_default_categories($contexts->all());
    $question_category = $DB->get_record_sql("SELECT * FROM {question_categories} where name ='top' and parent= 0 AND contextid=$thiscontext->id");
    
    $thispageurl = new moodle_url($CFG->wwwroot);
    $qcobject = new question_category_object($thiscontext->id, $thispageurl,
    $contexts->having_one_edit_tab_cap('categories'), $param->edit,
                                $question_category->id, $param->delete, $contexts->having_cap('moodle/question:add'));
    if ($question_category) {//new category
        $newparent = $question_category->id.','.$thiscontext->id;
        $categoryid=$qcobject->add_category($newparent,'Local Questions Categories',
                               $question_category->info, $thiscontext->id, $question_category->infoformat, 'local_questions_categories');
       
    } 
    
}
