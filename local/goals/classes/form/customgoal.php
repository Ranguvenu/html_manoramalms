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
 * Custom goal class.
 */
class customgoal extends dynamic_form {

    /**
     * Form defination
     */
    public function definition() {
        global $CFG, $DB, $USER;

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->optional_param('id', 0, PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'local_goals')); // Add elements to your form.
        $mform->addRule('name', get_string('name_goalerr', 'local_goals'), 'required', null);
        $mform->setType('text', PARAM_ALPHANUM);

        $cangoaledit = $DB->record_exists_sql("SELECT id FROM {local_hierarchy} WHERE  id = $id AND parent = 0");

        if ($id > 0  && $cangoaledit) {
            $goalcode = $DB->get_field_sql('SELECT code FROM {local_hierarchy} WHERE id = '.$id.' AND parent = 0');
            $mform->addElement('static', 'goalcode', get_string('code', 'local_goals'), $goalcode);
            $mform->addElement('hidden', 'code', $goalcode);
        } else {
            $mform->addElement('text', 'code', get_string('code', 'local_goals')); // Add elements to your form.
            $mform->addRule('code', get_string('name_codeerr', 'local_goals'), 'required', null);
            $mform->addRule('code', get_string('onlynumberandletters', 'local_goals'), 'alphanumeric', null);
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
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('board_codespaceerr', 'local_goals');
        }
        $goalcode = $DB->get_record_sql("SELECT id, code FROM {local_hierarchy} where code ='{$code}'");
        if ($goalcode && (empty($data['id']) || $goalcode->id != $data['id'])) {
            $errors['code'] = get_string('goal_codeerr', 'local_goals');
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
        global $CFG, $DB, $USER;

        $data = $this->get_data();
        $usermodified = $USER->id;

        if ($data) {
            if ($data->id > 0) {
                $goaldata = (new goal)->update_goals($data);
            } else {
                $goaldata = (new goal)->create_goals($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_hierarchy', ['id' => $id, 'depth' => 1], '*', MUST_EXIST);
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
        return new moodle_url('/local/goals/index.php',
            ['action' => 'editgoals', 'id' => $id]);
    }

}

