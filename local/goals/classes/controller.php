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
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_goals
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_goals;
use core_form\dynamic_form;
use dml_exception;
use moodle_url;
use context;
use context_system;
use html_writer;
use stdClass;
use local_goals\controller as goals;
use core_course_category;

require_once("{$CFG->dirroot}/course/lib.php");

/**
 * Goals controller class
 */
class controller {

    /** @var $usermodified */
    private $usermodified;

    /** Construct */
    public function __construct() {
        global $USER;
        $this->usermodified = $USER->id;
    }

    /**
     * Get all the list of goals
     */
    public function get_goals() {
        global $DB;
        $goaldata = array();
        $goals = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name AS goal
        FROM {local_hierarchy} lh
        WHERE 1 = 1 AND lh.parent = 0 AND lh.depth = 1");
        foreach ($goals as $goal) {
            $goaldata[] = ['goal' => $goal->goal];
        };
        return $goaldata;
    }

    /**
     * Create goal.
     * @param [type] $data [description]
     */
    public function create_goals($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $category = array('name' => $data->name, 'idnumber' => $data->code, 'description' => 'This category is related to goals', 'parent' => '0', 'visible' => 1, 'depth' => 1, 'timemodified' => $time);
        try {
            $category = core_course_category::create($category);
            $record = ['categoryid' => $category->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'parent' => 0,
                   'depth' => 1,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $goalid = $DB->insert_record('local_hierarchy', $record);
        } catch (dml_exception $e) {
            echo $e->message;
        }
        return $goalid;
    }

    /**
     * Update goal
     * @param [type] $data [description]
     */
    public function update_goals($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_hierarchy', $record);

        $categoryid = $DB->get_field_sql("SELECT categoryid FROM {local_hierarchy} WHERE id = $data->id");
        $categoryrecord = ['id' => $categoryid,
                            'name' => $data->name,
                            'idnumber' => $data->code,
                            'timemodified' => time()];
        $DB->update_record('course_categories', $categoryrecord);

        return $id;
    }
 
    /**
     * Get all the list of boards
     */
    public function get_boards() {
        global $DB;
        $boards = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name AS board
        FROM {local_hierarchy} lh
        WHERE 1 = 1 AND lh.depth = 2");
        foreach ($boards as $board) {
            $boardsdata[] = ['board' => $board->board];
        };
        return $boardsdata;
    }
    /**
     * Create board.
     * @param [type] $data [description]
     */
    public function create_boards($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $parentid = $this->get_parent_category($data->goalid);
        $category = array('name' => $data->name, 'idnumber' => $data->code, 'description' => 'This category is related to boards', 'parent' => $parentid, 'visible' => 1, 'depth' => 2, 'timemodified' => $time);
        try {
            $category = core_course_category::create($category);
            $record = ['categoryid' => $category->id,
                   'parent' => $data->goalid,
                   'depth' => 2,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $boardid = $DB->insert_record('local_hierarchy', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        return $boardid;
    }
    /**
     * Update board
     * @param [type] $data [description]
     */
    public function update_boards($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_hierarchy', $record);
        $categoryid = $DB->get_field_sql("SELECT categoryid FROM {local_hierarchy} WHERE id = $data->id");
        $categoryrecord = ['id' => $categoryid,
                           'name' => $data->name,
                           'idnumber' => $data->code,
                           'timemodified' => time()];
        $DB->update_record('course_categories', $categoryrecord);

        return $id;
    }
    /**
     * Create class.
     * @param [type] $data [description]
     */
    public function create_class($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $parentid = $this->get_parent_category($data->boardid);
        $category = array('name' => $data->name, 'idnumber' => $data->code, 'description' => $data->description['text'], 'parent' => $parentid, 'visible' => 1, 'depth' => 3, 'timemodified' => time());
        try {
            $category = core_course_category::create($category);
            $record = ['categoryid' => $category->id,
                   'parent' => $data->boardid,
                   'depth' => 3,
                   'name' => $data->name,
                   'code' => $data->code,
                   'image' => $data->image,
                   'description' => $data->description['text'],
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $classessid = $DB->insert_record('local_hierarchy', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        return $classessid;
    }
    /**
     * Update class
     * @param [type] $data [description]
     */
    public function update_class($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'image' => $data->image,
                   'description' => $data->description['text'],
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_hierarchy', $record);
        $categoryid = $DB->get_field_sql("SELECT categoryid FROM {local_hierarchy} WHERE id = $data->id");
        $categoryrecord = ['id'           => $categoryid,
                           'name'         => $data->name,
                           'idnumber'     => $data->code,
                           'description'  => $data->description['text'],
                           'timemodified' => time()];
        $DB->update_record('course_categories', $categoryrecord);

        return $id;
    }
    /**
     * Get all the list of classess
     */
    public function get_classess() {
        global $DB;
        $classess = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name FROM {local_hierarchy} ls WHERE 1 = 1 AND lh.depth = 3");
        foreach ($classess as $class) {
            $classessdata[] = ['name' => $class->name];
        };
        return $classessdata;
    }

    /**
     * Get category
     * @param [type] $classessid [description]
     */
    public function get_category($classessid) {
        global $DB;
        $categoryid = $DB->get_field_sql("SELECT g.categoryid
            FROM {local_hierarchy} g
            WHERE g.id = $classessid ");
        return $categoryid;
    }
    /**
     * Create subject
     * @param [type] $data [description]
     */
    public function create_subject($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $tags = unserialize(base64_decode($data->tags));
        $parentid = $this->get_parent_category($data->classessid);

        $courseconfig = get_config('moodlecourse');

        $courserecord = new stdClass();
        $courserecord->category = $parentid;
        $courserecord->fullname = $data->name;
        $courserecord->shortname = $data->code;
        $courserecord->idnumber = ($data && isset($data->old_id)) ? $data->old_id : 0;
        $courserecord->summary = $data->description['text'];
        $courserecord->summary_format = true;
        $courserecord->tags = $tags;
        $courserecord->startdate = time();
        $courserecord->enddate = strtotime(date('Y-m-d', strtotime('+1 years')));
        $courserecord->timecreated = time();
        $courserecord->timemodified = time();

        // Apply course default settings
        $courserecord->format             = $courseconfig->format;
        $courserecord->newsitems          = $courseconfig->newsitems;
        $courserecord->showgrades         = $courseconfig->showgrades;
        $courserecord->showreports        = $courseconfig->showreports;
        $courserecord->maxbytes           = $courseconfig->maxbytes;
        $courserecord->groupmode          = $courseconfig->groupmode;
        $courserecord->groupmodeforce     = $courseconfig->groupmodeforce;
        $courserecord->visible            = $courseconfig->visible;
        $courserecord->visibleold         = $courserecord->visible;
        $courserecord->lang               = $courseconfig->lang;
        $courserecord->enablecompletion   = $courseconfig->enablecompletion;
        $courserecord->numsections        = $courseconfig->numsections;

        try {
            $courseinfo = create_course($courserecord);
        } catch (dml_exception $e) {
            print_r($e);
        }

        if ($courseinfo) {
            $record = ['classessid' => $data->classessid,
                        'courseid' => $courseinfo->id,
                       'name' => $data->name,
                       'code' => $data->code,
                       'image' => $data->image,
                       'description' => $data->description['text'],
                       'is_active' => !empty($data->is_active) ? $data->is_active : 1,
                       'timecreated' => time(),
                       'timemodified' => time(),
                       'usermodified' => $this->usermodified];
            try {
                $subjectid = $DB->insert_record('local_subjects', $record);
            } catch (dml_exception $e) {
                print_r($e);
            }
        }
        return $courseinfo->id;
    }
    /**
     * Update subject
     * @param [type] $data [description]
     */
    public function update_subject($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $DB->get_field('local_subjects', 'id', ['courseid' => $data->id]),
                   'name' => $data->name,
                   'code' => $data->code,
                   'image' => $data->image,
                   'description' => $data->description['text'],
                   'is_active' => !empty($data->is_active) ? $data->is_active : 1,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        try {
            $subjectid = $DB->update_record('local_subjects', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        $courserecord = ['id' => $data->id,
                        'fullname' => $data->name,
                        'shortname' => $data->code,
                        'summary' => $data->description['text'],
                        'visible' => 1,
                        'timemodified' => time()];
        try {
            $courseid = $DB->update_record('course', $courserecord);
        } catch (dml_exception $e) {
            print_r($e);
        }
        
        return $courseid;
    }
    /**
     * Get_category
     * @param [type] $parentid [description]
     */
    public function get_parent_category($parentid) {
        global $DB;
        return $DB->get_field_sql("SELECT categoryid FROM {local_hierarchy} WHERE id = :id", ['id' => $parentid]);

    }
    /**
     * Deleting Hierarchy
     * @param $data [Array]
     */
    public function delete_hierarchy($data) {
        global $DB;

        if ($data['type'] == 'Course') {
            try {
                $DB->delete_records('local_subjects', ['courseid' => $data['id']]);
                $DB->delete_records('course', ['id' => $data['id']]);
            } catch (dml_exception $e) {
                print_r($e);
            }
        } else {
            try {
                $categoryid = $DB->get_field('local_hierarchy', 'categoryid', ['id' => $data['id']]);
                $ccategory = new stdClass();
                $ccategory->id = $categoryid;
                $ccategory->idnumber = uniqid();
                $DB->update_record('course_categories', $ccategory);
                $DB->delete_records('local_hierarchy', ['id' => $data['id']]);
            } catch (dml_exception $e) {
                print_r($e);
            }
        }

        return true;
    }
}
