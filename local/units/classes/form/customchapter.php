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
namespace local_units\form;

use core_form\dynamic_form;
use moodle_url;
use context;
use context_system;
use html_writer;
use local_units\controller as unit;

/**
 * Custom chapter
 */
class customchapter extends dynamic_form {

    /**
     * Add elements to form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
        // $id = $this->_customdata['id'];
         $id = $this->optional_param('id', 0, PARAM_INT);
        $unitid = $this->_ajaxformdata['unitid'];

        $mform->addElement('hidden', 'id');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('id', $id);

        $mform->addElement('hidden', 'unitid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('unitid', $unitid);
        if($unitid != 'null'){
        $courseid = $DB->get_record('local_units', ['id' => $unitid], '*', MUST_EXIST);
        $mform->addElement('hidden', 'course');
        $mform->setDefault('course', $courseid->courseid);
        $displayunitname = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',lu.name,' (',lu.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_units} lu ON lu.courseid = sub.courseid
                WHERE 1 = 1 AND lu.id = $unitid");

        $mform->addElement('static', 'displayunitname', get_string('unit', 'local_units'), $displayunitname);
        $mform->addElement('hidden', 'dunitname', $displayunitname);
        }else{
           
        $unitid = $DB->get_field_sql("SELECT unitid FROM {local_chapters} WHERE id = $id");
        $displayunitname = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',lu.name,' (',lu.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_units} lu ON lu.courseid = sub.courseid
                WHERE 1 = 1 AND lu.id = $unitid");
        $mform->addElement('static', 'displayunitname', get_string('unit', 'local_units'), $displayunitname);
        $mform->addElement('hidden', 'dunitname', $displayunitname);
        }

        $mform->addElement('text', 'name', get_string('name', 'local_units')); // Add elements to your form.
        $mform->addRule('name', get_string('name_chaptererr', 'local_units'), 'required', null);
        $mform->setType('text', PARAM_ALPHANUM);
        if ($id > 0) {
            $chaptercode = $DB->get_field_sql("SELECT code FROM {local_chapters} WHERE id = $id");
            $mform->addElement('text', 'code', get_string('code', 'local_units'), $chaptercode);
            $mform->addElement('hidden', 'code', $chaptercode);
        } else {
        $mform->addElement('text', 'code', get_string('code', 'local_units')); // Add elements to your form.
        $mform->addRule('code', get_string('code_chaptererr', 'local_units'), 'required', null);
        $mform->addRule('code', get_string('onlynumberandletters', 'local_units'), 'alphanumeric', null);
        $mform->setType('text', PARAM_ALPHANUM);
        }

        $mform->addElement('hidden', 'status');
        $mform->setType('int', PARAM_INT);
        // Set type of element.
    }

    /**
     * Perform some moodle validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $code = $data['code'];
        $unitid = $data['unitid'];
        $name = $data['name'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('codespaceerr', 'local_units');
        }
        if (trim($name) == "") {
            $errors['name'] = get_string('name_chaptererr', 'local_units');
        }
        $chaptercode = $DB->get_record_sql("SELECT id, code FROM {local_chapters} WHERE code = '{$code}'");
        if ($chaptercode && (empty($data['id']) || $chaptercode->id != $data['id'])) {
            $errors['code'] = get_string('chapter_codeerr', 'local_units');
        }
        return $errors;
    }
    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        // require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
    }

    /**
     * Process dynamic submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        if ($data) {
            if ($data->id > 0) {
                $chapterdata = (new unit)->update_chapters($data);
            } else {
                $chapterdata = (new unit)->create_chapters($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_chapters', ['id' => $id], '*', MUST_EXIST);
            $this->set_data(['id' => $data->id, 'name' => $data->name, 'code' => $data->code]);
        }
    }
    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $id = $this->optional_param('id', 0, PARAM_INT);
        return new moodle_url('/local/units/index.php',
            ['action' => 'editchapter', 'id' => $id]);
    }

}

