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

namespace local_studymaterial\form;

use core_form\dynamic_form;
use moodle_url;
use context;
use context_system;
use html_writer;
use local_studymaterial\local\studymaterial as studymaterial;

/**
 * Custom studymaterial class.
 */
class customstudymaterial extends dynamic_form
{

    /**
     * Form defination
     */
    public function definition()
    {
        global $CFG, $DB, $USER;

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->optional_param('id', 0, PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if ($courseid > 0) {
            $params = ['id' => $courseid];
            $coursename = $DB->get_field('course', 'fullname', $params);
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
            $mform->setDefault('courseid', $courseid);
            $mform->addElement('static', 'static_courseid', get_string('course', 'local_studymaterial'), $coursename);
        } else {
            $sql = "SELECT ls.courseid, ls.name FROM {local_subjects} AS ls JOIN {course} AS c ON c.id = ls.courseid WHERE 1 ";
            $courses = $DB->get_records_sql_menu($sql, []);
            $mform->addElement('autocomplete', 'courseid', get_string('course', 'local_studymaterial'), [get_string('selectcourse', 'local_studymaterial')] + $courses);
        }
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        //   $this->standard_intro_elements();
        $mform->addElement('textarea', 'description', get_string('description', 'local_studymaterial'));
        $mform->addRule('description', get_string('required'), 'required', null, 'server');
        $mform->setType('description', PARAM_RAW);

        //-------------------------------------------------------
        $systemcontext = context_system::instance();
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'page'));
        $mform->addElement('editor', 'content', get_string('content', 'local_studymaterial'), null, array('subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $systemcontext, 'noclean' => 1, 'trusttext' => 0));
        $mform->addRule('content', get_string('required'), 'required', null, 'client');

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
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
            if ($data->id > 0) {
                $studymaterialdata = (new studymaterial)->update_studymaterial($data);
            } else {
                $studymaterialdata = (new studymaterial)->create_studymaterial($data);
            }
        }
    }

    /**
     * Set form data for dynamic submission.
     */
    public function set_data_for_dynamic_submission(): void
    {
        global $DB, $CFG;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $data = $DB->get_record('local_studymaterial', ['id' => $id], '*', MUST_EXIST);
            $systemcontext = \context_system::instance();

            $draftid_editor = file_get_submitted_draft_itemid('content');
            $currentintro = file_prepare_draft_area($draftid_editor, $systemcontext->id, 'local_studymaterial', 'content', 0, array('subdirs' => true), $data->content);
            $datacontent = array('text' => $currentintro, 'format' => $data->contentformat, 'itemid' => $draftid_editor);
            $this->set_data(['id' => $data->id, 'course' => $data->course, 'name' => $data->name, 'description' => $data->intro, 'content' => $datacontent]);
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
            '/local/studymaterial/index.php',
            ['action' => 'editstudymaterial', 'id' => $id]
        );
    }
}


// <p><img class="img-fluid align-top" src="http://localhost/manoramalms/draftfile.php/5/user/draft/774906978/IMG-20230822-WA0004.jpg" alt="fsdgseg" width="720" height="1280"></p>

// <p><img class="img-fluid align-top" src="@@PLUGINFILE@@/Screenshot%20from%202023-10-18%2011-47-26.png" alt="gfdh" width="1519" height="759"></p>
