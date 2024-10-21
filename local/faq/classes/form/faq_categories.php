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

namespace local_faq\form;

use core_form\dynamic_form;
use moodle_url;
use context;
use context_system;
use html_writer;
use local_faq\controller as controller;

/**
 * Custom goal class.
 */
class faq_categories extends dynamic_form
{

    /**
     * Form defination
     */
    public function definition()
    {
        global $CFG, $DB, $USER;
        $context = context_system::instance();

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->optional_param('id', 0, PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $cangoaledit = $DB->record_exists_sql("SELECT id FROM {local_faq_categories} WHERE  id = $id");
        if ($id > 0  && $cangoaledit) {
            $mform->addElement('text', 'name', get_string('name', 'local_faq')); // Add elements to your form.
            $mform->addRule('name', get_string('name_goalerr', 'local_faq'), 'required', null);
            $mform->setType('text', PARAM_ALPHANUM);
        } else {
            $mform->addElement('text', 'name', get_string('name', 'local_faq')); // Add elements to your form.
            $mform->addRule('name', get_string('name_goalerr', 'local_faq'), 'required', null);
            $mform->setType('text', PARAM_ALPHANUM);
        }

        if ($id > 0  && $cangoaledit) {
            $goalcode = $DB->get_field_sql('SELECT code FROM {local_faq_categories} WHERE id = ' . $id);
            $mform->addElement('static', 'goalcode', get_string('code', 'local_faq'), $goalcode);
            $mform->addElement('hidden', 'code', $goalcode);
        } else {
            $mform->addElement('text', 'code', get_string('code', 'local_faq')); // Add elements to your form.
            $mform->addRule('code', get_string('name_codeerr', 'local_faq'), 'required', null);
            $mform->addRule('code', get_string('onlynumberandletters', 'local_faq'), 'alphanumeric', null);
            $mform->setType('text', PARAM_ALPHANUM);
        }

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_faq'));
        $mform->addRule('sortorder', get_string('missingsortorder', 'local_faq'), 'required', '', 'server');
        $mform->addRule('sortorder', get_string('numericonly', 'local_faq'), 'numeric', null, 'client');
        $mform->setType('sortorder', PARAM_RAW);

        $textfieldoptions = array('trusttext' => true, 'subdirs' => true, 'maxfiles' => -1, 'maxbytes' => $CFG->maxbytes, 'context' => $context);
        $mform->addElement('editor', 'description_editor', get_string('description', 'local_faq'), null, $textfieldoptions);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addRule('description_editor', get_string('required'), 'required', null, 'client');

        $mform->addElement('filemanager', 'logo', get_string('logo', 'local_faq'), null, array('subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => array('*')));

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
    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);
        $code = $data['code'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('board_codespaceerr', 'local_faq');
        }
        if (empty($data['name'])) {
            $errors['code'] = get_string('nameempty', 'local_faq');
        }
        if (!is_numeric($data['sortorder'])) {
            $errors['sortorder'] = get_string('acceptedtype', 'local_faq');
        }
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('board_codespaceerr', 'local_faq');
        }
        $goalcode = $DB->get_record_sql("SELECT id, code FROM {local_faq_categories} where code ='{$code}'");
        if ($goalcode && (empty($data['id']) || $goalcode->id != $data['id'])) {
            $errors['code'] = get_string('goal_codeerr', 'local_faq');
        }
        return $errors;
    }
    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context
    {
        return \context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void
    {
        require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
    }

    /**
     * Process dynamic submission
     */
    public function process_dynamic_submission()
    {
        global $CFG, $DB, $USER;

        $data = $this->get_data();
        $usermodified = $USER->id;

        if ($data) {

            $faqdata = (new controller)->create_update_category($data);
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void
    {
        global $DB, $CFG;
        $context = context_system::instance();
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {

            $data = (new controller)->set_faq_category($id);

            if (!empty($data)) {
                if (!empty($data)) {
                    $textfieldoptions = array('trusttext' => true, 'subdirs' => true, 'maxfiles' => -1, 'maxbytes' => $CFG->maxbytes, 'context' => $context);
                    $data = file_prepare_standard_editor(
                        // The existing data.
                        $data,

                        // The field name in the database.
                        'description',

                        // The options.
                        $textfieldoptions,

                        // The combination of contextid, component, filearea, and itemid.
                        \context_system::instance(),
                        'local_faq',
                        'description',
                        $data->id
                    );
                }
                $this->set_data($data);
            }
        }
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url
    {
        $id = $this->optional_param('id', 0, PARAM_INT);
        return new moodle_url(
            '/local/faq/index.php',
            ['action' => 'editquery', 'id' => $id]
        );
    }
}
