<?php
use core_component;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
require_once($CFG->libdir.'/formslib.php');
class filters_form extends moodleform {
    function definition() {
        global $CFG;
        $mform    = $this->_form;
        $filterlist        = $this->_customdata['filterlist']; // this contains the data of this form
     
        $filterparams      = $this->_customdata['filterparams'];
        $action            = $this->_customdata['action'];
        $options           = $filterparams['options'];
        $dataoptions       = $filterparams['dataoptions'];
        //$submitid = $this->_customdata['submitid'] ? $this->_customdata['submitid'] : 'filteringform';
        $submitid = 'filteringform';

        $this->_form->_attributes['id'] = $submitid;

        $mform->addElement('hidden', 'options', $options);
        $mform->setType('options', PARAM_RAW);

        $mform->addElement('hidden', 'dataoptions', $dataoptions);
        $mform->setType('dataoptions', PARAM_RAW);     
       
        foreach ($filterlist as $key => $value) {
                $filter = 'questions';
            $core_component = new core_component();
            $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
            if ($courses_plugin_exist) {
                require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                $functionname = $value.'_filter';
                $functionname($mform);
            }
        }
     
        $buttonarray = array();
        $buttonarray = array();
        $applyclassarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'filter_apply', get_string('apply','local_questions'), $applyclassarray);
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_questions'), $applyclassarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->disable_form_change_checker();       
    }
     /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
