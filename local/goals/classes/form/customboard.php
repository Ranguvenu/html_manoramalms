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
 * Custom board
 */
class customboard extends dynamic_form {

    /**
     * Add elements to form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->_customdata['id'];
        $goalid = $this->_ajaxformdata['goalid'];

        $mform->addElement('hidden', 'id');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('id', $id);

        $mform->addElement('hidden', 'goalid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('goalid', $goalid);

        $mform->addElement('text', 'name', get_string('name', 'local_goals')); // Add elements to your form.
        $mform->addRule('name', get_string('name_boarderr', 'local_goals'), 'required', null);
        $mform->setType('text', PARAM_ALPHANUM);

        $mform->addElement('text', 'code', get_string('code', 'local_goals')); // Add elements to your form.
        $mform->addRule('code', get_string('code_boarderr', 'local_goals'), 'required', null);
        $mform->addRule('code', get_string('onlynumberandletters', 'local_goals'), 'alphanumeric', null);
        $mform->setType('text', PARAM_ALPHANUM);

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
        $goalid = $data['goalid'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('codespaceerr', 'local_goals');
        }
        $boardcode = $DB->get_record_sql("SELECT id, code FROM {local_hierarchy} WHERE code = '{$code}' AND depth = 2");
        if ($boardcode && (empty($data['id']) || $boardcode->id != $data['id'])) {
            $errors['code'] = get_string('board_codeerr', 'local_goals');
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
     * Process dynamic submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        if ($data) {
            if ($data->id > 0) {
                $boarddata = (new goal)->update_boards($data);
            } else {
                $boarddata = (new goal)->create_boards($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_hierarchy', ['id' => $id, 'depth' => 2], '*', MUST_EXIST);
            $this->set_data(['id' => $data->id, 'name' => $data->name, 'code' => $data->code ]);
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
            ['action' => 'editboard', 'id' => $id]);
    }

}

