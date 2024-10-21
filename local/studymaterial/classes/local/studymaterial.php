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
 * studymaterial hierarchy
 *
 * This file defines the current version of the local_studymaterial Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_studymaterial
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studymaterial\local;

use moodle_exception;
use stdClass;

/**
 * studymaterial library file
 */
class studymaterial
{
    /**
     * Get list of all studymaterial
     *
     * @param [type] $fields       [description]
     * @param [type] $limitfrom    [description]
     * @param [type] $limitnum     [description]
     * @param [type] $filtervalues [description]
     */
    public function get_studymaterial($fields = array(), $limitfrom = 0, $limitnum = 0, $filtervalues = null, $courseid = null)
    {
        global $DB, $CFG;
        !empty($fields) ? $select = implode(',', $fields) : $select = '*';
        // if (empty($filtervalues)) {
        $params = [];
        if ($courseid) {
            $params['course'] = $courseid;
        }
        $studymaterial = $DB->get_records('local_studymaterial', $params, ' id DESC', $select, $limitfrom, $limitnum);

        return $studymaterial;
        // }
    }

    /**
     * Create studymaterial.
     * @param [type] $data [description]
     */
    public function create_studymaterial($data)
    {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $systemcontext = \context_system::instance();
        try {
            $record = [
                'course' => $data->courseid,
                'name' => $data->name,
                'intro' => $data->description,
                'content' => $data->content['text'],
                'contentformat' => $data->content['format'],
                'revision' => $data->revision,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $studymaterialid = $DB->insert_record('local_studymaterial', $record);
            if (!empty($data->content['itemid'])) {
                $record['id'] = $studymaterialid;
                $draftitemid = $data->content['itemid'];
                $record['content'] = file_save_draft_area_files($draftitemid, $systemcontext->id, 'local_studymaterial', 'content', 0, $this->page_get_editor_options($systemcontext), $data->content['text']);
                $DB->update_record('local_studymaterial', $record);
            }
        } catch (dml_exception $e) {
            echo $e->message;
        }
        return $studymaterialid;
    }

    /**
     * Update studymaterial
     * @param [type] $data [description]
     */
    public function update_studymaterial($data)
    {
        global $DB;
        if (!is_object($data)) {
            $data = (object)$data;
        }
        $systemcontext = \context_system::instance();
        $draftitemid = $data->content['itemid'];
        $record = [
            'id' => $data->id,
            'course' => $data->courseid,
            'name' => $data->name,
            'intro' => $data->description,
            'content' => $data->content['text'],
            'contentformat' => $data->content['format'],
            'revision' => $data->revision,
            'timemodified' => time(),
        ];
        // print_r($record);
        $record['content'] = file_save_draft_area_files(
            $draftitemid,
            $systemcontext->id,
            'local_studymaterial',
            'content',
            0,
            $this->page_get_editor_options($systemcontext),
            $data->content['text']
        );
        // var_dump($record);die;
        $id = $DB->update_record('local_studymaterial', $record);
        return $id;
    }
    public function page_get_editor_options($systemcontext)
    {
        global $CFG;
        return array('subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $systemcontext, 'noclean' => 1, 'trusttext' => 0);
    }
    public function remove_studymaterial($id)
    {
        global $DB;
        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('local_studymaterial', ['id' => $id]);
            $transaction->allow_commit();
            return true;
        } catch (moodle_exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    public function duplicate_studymaterial($id, $courseid)
    {
        global $DB, $CFG;
        $systemcontext = \context_system::instance();
        $data = $DB->get_record('local_studymaterial', ['id' => $id]);
        try {
            $record = [
                'course' => $courseid,
                'name' => $data->name,
                'intro' => $data->intro,
                'content' => $data->content,
                'contentformat' => $data->contentformat,
                'revision' => $data->revision,
                'timecreated' => time(),
            ];
            $studymaterialid = $DB->insert_record('local_studymaterial', $record);
            return $studymaterialid;
        } catch (dml_exception $e) {
            echo $e->message;
        }
    }
}
