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
 * units hierarchy
 *
 * This file defines the current version of the local_units Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_units
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_units;
use core_form\dynamic_form;
use dml_exception;
use moodle_url;
use context;
use context_system;
use html_writer;
use stdClass;
use local_units\controller as units;
use core_course_category;

require_once("{$CFG->dirroot}/course/lib.php");

/**
 * units controller class
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
     * Get all the list of units
     */
    public function get_units() {
        global $DB;
        $unitdata = array();
        $units = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name AS unit
        FROM {local_hierarchy} lh
        WHERE 1 = 1 AND lh.parent = 0 AND lh.depth = 1");
        foreach ($units as $unit) {
            $unitdata[] = ['unit' => $unit->unit];
        };
        return $unitdata;
    }

    /**
     * Create unit.
     * @param [type] $data [description]
     */
    public function create_units($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        try {
            $record = [
                   'name' => $data->name,
                   'code' => $data->code,
                   'courseid' => $data->course,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $unitid = $DB->insert_record('local_units', $record);
        } catch (dml_exception $e) {
            echo $e->message;
        }
        return $unitid;
    }

    /**
     * Update unit
     * @param [type] $data [description]
     */
    public function update_units($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_units', $record);
        return $id;
    }
 
    /**
     * Get all the list of chapters
     */
    public function get_chapters() {
   
        global $DB;
        $chapters = $DB->get_records_sql("SELECT DISTINCT lh.id, lh.name AS chapter
        FROM {local_hierarchy} lh
        WHERE 1 = 1 AND lh.depth = 2");
        foreach ($chapters as $chapter) {
            $chaptersdata[] = ['chapter' => $chapter->chapter];
        };
        return $chaptersdata;
    }
    /**
     * Create chapter.
     * @param [type] $data [description]
     */
    public function create_chapters($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        try {
            $record = [
                   'name' => $data->name,
                   'code' => $data->code,
                   'unitid' => $data->unitid,
                   'courseid' => $data->course,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $chapterid = $DB->insert_record('local_chapters', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        return $chapterid;
    }
    /**
     * Update chapter
     * @param [type] $data [description]
     */
    public function update_chapters($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_chapters', $record);
        return $id;
    }
    /**
     * Create concept.
     * @param [type] $data [description]
     */
    public function create_topic($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
       
        try {
            $record = [
                   'name' => $data->name,
                   'code' => $data->code,
                   'unitid' => $data->unitid,
                   'courseid' => $data->course,
                   'chapterid' => $data->chapterid,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $classessid = $DB->insert_record('local_topics', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        return $classessid;
    }
    /**
     * Update concept
     * @param [type] $data [description]
     */
    public function update_topic($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_topics', $record);
        return $id;
    } 

        /**
     * Create concept.
     * @param [type] $data [description]
     */
    public function create_concept($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
       
        try {
            $record = [
                   'name' => $data->name,
                   'code' => $data->code,
                   'unitid' => $data->unitid,
                   'courseid' => $data->course,
                   'chapterid' => $data->chapterid,
                   'topicid' => $data->topicid,
                   'timecreated' => time(),
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
            $conceptids = $DB->insert_record('local_concept', $record);
        } catch (dml_exception $e) {
            print_r($e);
        }
        return $conceptids;
    }
    /**
     * Update concept
     * @param [type] $data [description]
     */
    public function update_concept($data) {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $record = ['id' => $data->id,
                   'name' => $data->name,
                   'code' => $data->code,
                   'timemodified' => time(),
                   'usermodified' => $this->usermodified];
        $id = $DB->update_record('local_concept', $record);
        return $id;
    } 
    /**
     * Get_category
     * @param [type] $parentid [description]
     */
    public function get_parent_category($parentid) {
        global $DB;
        return $DB->get_field_sql("SELECT categoryid FROM {local_hierarchy} WHERE id = :id", ['id' => $parentid]);

    }
 
}
