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
 * Custom class
 */
class customconcept extends  dynamic_form {

    /**
     * Add elements to form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
        //$id = $this->_customdata['id'];
        $id = $this->optional_param('id', 0, PARAM_INT);
        $chapterid = $this->_ajaxformdata['chapterid'];
        $unitid = $this->_ajaxformdata['unitid'];
        $topicid = $this->_ajaxformdata['topicid'];

        $mform->addElement('hidden', 'id');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('id', $id);

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('chapterid', $chapterid);

        $mform->addElement('hidden', 'unitid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('unitid', $unitid); 

        $mform->addElement('hidden', 'topicid');
        $mform->setType('int', PARAM_INT);
        $mform->setDefault('topicid', $topicid); 
        if($unitid != 'null'){
        $courseid = $DB->get_record('local_chapters', ['id' => $chapterid,'unitid' => $unitid], '*', MUST_EXIST);
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
        $mform->addElement('hidden', 'dname', $displayunitname);
        $displaychaptername = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',luu.name,' / ',lc.name,' (',lc.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_chapters} lc ON lc.courseid = sub.courseid
                JOIN {local_units} luu ON luu.id = lc.unitid
                WHERE 1 = 1 AND lc.id = $chapterid");
        $mform->addElement('static', 'displaychaptername', get_string('chapter', 'local_units'), $displaychaptername);
        $mform->addElement('hidden', 'dchaptername', $displaychaptername);
        $displaytopicname = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',luu.name,' / ',lc.name,' / ',lt.name,' (',lt.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_topics} lt ON lt.courseid = sub.courseid
                JOIN {local_units} luu ON luu.id = lt.unitid
                JOIN {local_chapters} lc ON lc.id = lt.chapterid
                WHERE 1 = 1 AND lt.id = $topicid");
        $mform->addElement('static', 'displaytopicname', get_string('topic', 'local_units'), $displaytopicname);
        $mform->addElement('hidden', 'dtopicname', $displaytopicname);
        }else{
        $unitandchapterids = $DB->get_record('local_concept',['id' => $id]);
          $displayunitname = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',lu.name,' (',lu.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_units} lu ON lu.courseid = sub.courseid
                WHERE 1 = 1 AND lu.id = $unitandchapterids->unitid");
         $displaychaptername = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',luu.name,' / ',lc.name,' (',lc.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_chapters} lc ON lc.courseid = sub.courseid
                JOIN {local_units} luu ON luu.id = lc.unitid
                WHERE 1 = 1 AND lc.id = $unitandchapterids->chapterid");
        $displaytopicname = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,' / ',luu.name,' / ',lc.name,' / ',lt.name,' (',lt.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh ON lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 ON lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 ON lh1.parent = lh2.id AND lh2.depth = 1
                JOIN {local_topics} lt ON lt.courseid = sub.courseid
                JOIN {local_units} luu ON luu.id = lt.unitid
                JOIN {local_chapters} lc ON lc.id = lt.chapterid
                WHERE 1 = 1 AND lt.id = $unitandchapterids->topicid");
        $mform->addElement('static', 'displayunitname', get_string('unit', 'local_units'), $displayunitname);
        $mform->addElement('hidden', 'dname', $displayunitname);
        $mform->addElement('static', 'displaychaptername', get_string('chapter', 'local_units'), $displaychaptername);
        $mform->addElement('hidden', 'dchaptername', $displaychaptername);
        $mform->addElement('static', 'displaytopicname', get_string('topic', 'local_units'), $displaytopicname);
        $mform->addElement('hidden', 'dtopicname', $displaytopicname);
        }

        $mform->addElement('text', 'name', get_string('name', 'local_units')); // Add elements to your form.
        $mform->addRule('name', get_string('name_topicerr', 'local_units'), 'required', null);
        $mform->setType('text', PARAM_ALPHANUM);

        if ($id > 0) {
        $conceptcode = $DB->get_field_sql("SELECT code FROM {local_concept} WHERE id = $id");
        $mform->addElement('text', 'code', get_string('code', 'local_units'), $conceptcode);
        $mform->addElement('hidden', 'code', $conceptcode);
        } else {
        $mform->addElement('text', 'code', get_string('code', 'local_units')); // Add elements to your form.
        $mform->addRule('code', get_string('code_concepterr', 'local_units'), 'required', null);
        $mform->addRule('code', get_string('onlynumberandletters', 'local_units'), 'alphanumeric', null);
        $mform->setType('text', PARAM_ALPHANUM);
        }
     
        $mform->addElement('hidden', 'status');
        $mform->setType('int', PARAM_INT);

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
        $chapterid = $data['chapterid'];
        $name = $data['name'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('codespaceerr', 'local_units');
        }
         if (trim($name) == "") {
            $errors['name'] = get_string('name_concepterr', 'local_units');
        }
        $conceptcode = $DB->get_record_sql("SELECT id, code FROM {local_concept} WHERE code = '{$code}' ");
        if ($conceptcode && (empty($data['id']) || $conceptcode->id != $data['id'])) {
            $errors['code'] = get_string('concept_codeerr', 'local_units');
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
     * Form dynamic submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $context = context_system::instance();
        if ($data) {
            if ($data->id > 0) {
                $conceptdata = (new unit)->update_concept($data);
            } else {
                $conceptdata = (new unit)->create_concept($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_concept', ['id' => $id], '*', MUST_EXIST);
             $coursename = $DB->get_record('local_subjects', ['courseid' => $data->courseid], '*', MUST_EXIST);
            $context = context_system::instance();
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
        return new moodle_url('/local/units/index.php',
            ['action' => 'editclass', 'id' => $id]);
    }
}
