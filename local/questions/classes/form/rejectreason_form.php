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
 * @author     Moodle India Information Solutions.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_questions\form;
// use moodleform;
use core_form\dynamic_form;
use context_system;
use moodle_url;
use context;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
// require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

/**
 * Reject reason submission form.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rejectreason_form extends dynamic_form {

	/**
     * Form definition. Abstract method - always override!
     */
    public function get_context_for_dynamic_submission(): \context {

        $contextid = $this->optional_param('contextid', \context_system::instance()->id, PARAM_INT);
        return \context::instance_by_id($contextid);
    }

	/**
	 * Form definition.  
	 */
	protected function definition() {
		global $OUTPUT, $PAGE, $CFG, $DB;
		$mform = &$this->_form;
		$questionid = $this->_ajaxformdata['id'];
        $qbankeid = $this->_ajaxformdata['qbankeid'];
        $workshopid = $this->_ajaxformdata['workshopid'];

		$mform->addElement('textarea', 'rejectreason', get_string('reason', 'local_questions'), array('maxlength' => 250));
        $mform->addRule('rejectreason', get_string('errorreason', 'local_questions'), 'required', null, 'client');
        $mform->setType('rejectreason', PARAM_RAW);

        $mform->addElement('hidden', 'questionid');
        $mform->setType('questionid', PARAM_INT);
        $mform->setDefault('questionid',  $questionid);

        $mform->addElement('hidden', 'questionbankentryid');
        $mform->setType('questionbankentryid', PARAM_INT);
        $mform->setDefault('questionbankentryid',  $qbankeid);

        // $mform->addElement('hidden', 'workshopid');
        // $mform->setType('workshopid', PARAM_INT);
        // $mform->setDefault('workshopid',  $workshopid);
	}

	/**
     * Require access.
     */
    public function require_access(): void {}

    /**
     * Check if current user has access to this form.
     */
    public function check_access_for_dynamic_submission(): void {}

    /**
     * Process the form submission
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $DB, $USER;
        $data = $this->get_data();
        $data->reason = $data->rejectreason;
        $data->timecreated = time();
        $data->usercreated = $USER->id;
        // $params = [];
        // $params['questionid'] = $data->questionid;
        // $params['questionbankentryid'] = $data->questionbankentryid;
        // $params['usercreated'] = $USER->id;
        // $existssql = "SELECT *
        //                 FROM {local_rejected_questions}
        //                WHERE questionid = :questionid
        //                  AND questionbankentryid = :questionbankentryid
        //                  AND usercreated = :usercreated";
        // $exists = $DB->record_exists_sql($existssql, $params);
        // if ($exists) {
        //     $previousid = $DB->get_field('local_rejected_questions', 'id', $params);
        //     $updaterec = new stdClass();
        //     $updaterec->id = $previousid;
        //     $updaterec->reason = $data->rejectreason;
        //     $executed = $DB->update_record('local_rejected_questions', $updaterec);
        // } else {
            $executed = $DB->insert_record('local_rejected_questions', $data);
        // }
        return $executed;
    }

    /**
     * Load in existing data as form defaults.
     */
    public function set_data_for_dynamic_submission(): void {}

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/local/questions/questionbank_view.php', [
            'courseid' => $this->optional_param('id', 0, PARAM_INT),
            'cat' => $this->optional_param('cat', '', PARAM_RAW),
        ]);
    }

	/**
	 * Function validation.
	 * returns errors if any.  
	 */
	public function validation($data, $files) {
		global $OUTPUT, $PAGE, $CFG, $DB;
		$errors = parent::validation($data, $files);
		return $errors;
	}
}