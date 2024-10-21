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
namespace local_goals\form;

use core_form\dynamic_form;
use moodle_url;
use context;
use context_system;
use html_writer;
use local_goals\controller as goal;

/**
 * Custom subject
 */
class customsubject extends  dynamic_form {

    /**
     * Add elements to form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->_customdata['id'];
        $classessid = $this->_ajaxformdata['classessid'];

        $mform->addElement('hidden', 'id');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('id', $id);

        $mform->addElement('hidden', 'classessid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('classessid', $classessid);

        $mform->addElement('text', 'name', get_string('name', 'local_goals')); // Add elements to your form.
        $mform->addRule('name', get_string('name_subjecterr', 'local_goals'), 'required', null);
        $mform->setType('text', PARAM_ALPHANUM);

        $mform->addElement('text', 'code', get_string('code', 'local_goals')); // Add elements to your form.
        $mform->addRule('code', get_string('code_subjecterr', 'local_goals'), 'required', null);
        $mform->addRule('code', get_string('onlynumberandletters', 'local_goals'), 'alphanumeric', null);
        $mform->setType('text', PARAM_ALPHANUM);

        $mform->addElement('editor','description', get_string('description', 'local_goals'));
        $mform->addRule('description', get_string('required'), 'required', null, 'server');
        $mform->setType('description', PARAM_RAW);

        $filemanageroptions = array(
            'accepted_types' => array(get_string('png_format', 'local_goals'), 
                get_string('jpg_format', 'local_goals'), get_string('jpeg_format', 'local_goals')),
            'maxbytes' => 0,
            'maxfiles' => 1,
        );
        $mform->addElement('filepicker', 'image', get_string('image', 'local_goals'), null, $filemanageroptions);
        $mform->addRule('image', get_string('required'), 'required', null);

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
        $classessid = $data['classessid'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('codespaceerr', 'local_goals');
        }
        $subjectcode = $DB->get_record_sql("SELECT id, code FROM {local_subjects} where code = '{$code}' ");
        if ($subjectcode && (empty($data['id']) || $subjectcode->id != $data['id'])) {
            $errors['code'] = get_string('subject_codeerr', 'local_goals');
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
        require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
    }

    /**
     * Form dynamic submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $context = context_system::instance();
        if ($data) {
            if ($data->id > 0) {
                $subjectdata = (new goal)->update_subject($data);
                $this->save_stored_file('image', $context->id, 'local_goals', 'subjectimage',  $data->image, '/', null, true);
            } else {
                $subjectdata = (new goal)->create_subject($data);
                if ($subjectdata) {
                    $this->save_stored_file('image', $context->id, 'local_goals', 'subjectimage',  $data->image, '/', null, true);
                }
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $context = context_system::instance();
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_subjects', ['id' => $id], '*', MUST_EXIST);
            $draftitemid = file_get_submitted_draft_itemid('subjectimage');
            file_prepare_draft_area($draftitemid, $context->id, 'local_goals', 'subjectimage', $data->image, null);
            $data->image = $draftitemid;
            $data->description = ['text' => $data->description];
            $this->set_data(['id' => $data->id, 'name' => $data->name, 'code' => $data->code, 'description' => $data->description, 'image' => $data->image]);
        }
    }
    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $id = $this->optional_param('id', 0, PARAM_INT);
        return new moodle_url('/local/goals/index.php',
            ['action' => 'editsubject', 'id' => $id]);
    }
}
