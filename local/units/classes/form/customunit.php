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
 * units unit
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
 * Custom unit class.
 */
class customunit extends dynamic_form {

    /**
     * Form defination
     */
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->optional_param('id', 0, PARAM_INT);
        $courseid = $this->_ajaxformdata['course'];
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
         if ($id == 0 ) {
         $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'allcourseslist',
        'id' => 'id_customfield_subject',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removecourses();}) }) (event)",
        'placeholder' => get_string('choose_subject', 'local_units')
         );
        $coursearray=array();
        if($courseid){
            $subjectname = $DB->get_records_sql_menu("SELECT courseid as id, concat(name,' (',code,')') as fullname FROM {local_subjects} WHERE courseid = $courseid");
            $coursearray= $subjectname;
        }else{
            $coursearray[null] = get_string("select_subject",'local_units');
        }
            $mform->addElement('autocomplete', 'course', get_string("subject","local_units"), $coursearray, $options);
            $mform->addRule('course',get_string("choose_subject","local_units"), 'required', null);
        }
            $mform->addElement('text', 'name', get_string('name', 'local_units')); // Add elements to your form.
            $mform->addRule('name', get_string('name_uniterr', 'local_units'), 'required', null);
            $mform->setType('text', PARAM_ALPHANUM);
            $canunitedit = $DB->record_exists_sql("SELECT id FROM {local_units} WHERE  id = $id ");
 
        if ($id > 0  && $canunitedit) {
            $subjectid = $DB->get_field_sql("SELECT courseid FROM {local_units} WHERE id = $id");


             $subject = $DB->get_field_sql("SELECT CONCAT(lh2.name,' / ',lh1.name,' / ',lh.name, ' / ', sub.name,'(',sub.code,')') as fullname 
                FROM {local_subjects} AS sub
                JOIN {local_hierarchy} lh on lh.id = sub.classessid AND lh.depth = 3 
                JOIN {local_hierarchy} lh1 on lh.parent =lh1.id AND lh1.depth = 2
                JOIN {local_hierarchy} lh2 on lh1.parent = lh2.id AND lh2.depth = 1 WHERE 1=1 AND sub.courseid = $subjectid");
             
            $unitcode = $DB->get_field_sql("SELECT code FROM {local_units} WHERE id = $id");
            $mform->addElement('text', 'code', get_string('code', 'local_units'), $unitcode);
            $mform->addElement('hidden', 'code', $unitcode);

            $mform->addElement('static', 'subject', get_string('subject', 'local_units'), $subject);
            $mform->addElement('hidden', 'subject', $subject);
        }else {
            $mform->addElement('text', 'code', get_string('code', 'local_units')); // Add elements to your form.
            $mform->addRule('code', get_string('name_codeerr', 'local_units'), 'required', null);
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
        $name = $data['name'];
        if (strrpos($code, ' ') !== false) {
            $errors['code'] = get_string('spaceserrors', 'local_units');
        }
         if (ltrim($name) == "") {
            $errors['name'] = get_string('name_uniterr', 'local_units');
        }
        $unitcode = $DB->get_record_sql("SELECT id, code FROM {local_units} where code ='{$code}'");
        if ($unitcode && (empty($data['id']) || $unitcode->id != $data['id'])) {
            $errors['code'] = get_string('unit_codeerr', 'local_units');
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
        global $CFG, $DB, $USER;

        $data = $this->get_data();
        $usermodified = $USER->id;

        if ($data) {
            if ($data->id > 0) {
                $unitdata = (new unit)->update_units($data);
            } else {
                $unitdata = (new unit)->create_units($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_units', ['id' => $id], '*', MUST_EXIST);
            //$coursename = $DB->get_record('local_subjects', ['courseid' => $data->courseid], '*', MUST_EXIST);
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
            ['action' => 'editunits', 'id' => $id]);
    }

}

