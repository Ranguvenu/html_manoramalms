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
 * program Capabilities
 *
 * program - A Moodle plugin for managing ILT's
 * @package
 * @author     Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
class filters_form extends moodleform {

    function definition() {
        global $CFG;

        $mform    = $this->_form;
        $filterlist        = $this->_customdata['filterlist'];// this contains the data of this form
        $filterparams      = $this->_customdata['filterparams'] ?? null;
        $options = $filterparams['options'];
        $dataoptions = $filterparams['dataoptions'];
        $submitidcust = $this->_customdata['submitid'] ?? null;
        $submitid = $submitidcust ? $submitidcust : 'filteringform';
        $this->_form->_attributes['id'] = $submitid;
        $action = "";
        
        $mform->addElement('hidden', 'options', $options);
        $mform->setType('options', PARAM_RAW);

        $mform->addElement('hidden', 'dataoptions', $dataoptions);
        $mform->setType('dataoptions', PARAM_RAW);

        foreach ($filterlist as $key => $value) {
            if ($value === 'recommended_courses') {
                $filter = 'recommended_courses';
            } else {
                $filter = $value;
            }
            $corecomponent = new core_component();
            $pluginexist = $corecomponent::get_plugin_directory('block', $filter);

            if ($pluginexist) {
                require_once($CFG->dirroot . '/blocks/' . $filter . '/lib.php');
                $functionname = $value.'_filter';
                $functionname($mform);
            }
        }
        if ($action === 'user_enrolment') {
            $buttonarray = array();
            // $applyclassarray = array('class' => 'form-submit');
            // $buttonarray[] = &$mform->createElement('submit', 'filter_apply', 
            //                 get_string('apply','block_recommended_courses'), $applyclassarray);
            // $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','block_recommended_courses'), $applyclassarray);
        } else {
            $buttonarray = array();
            $applyclassarray = array('class' => 'form-submit',
                                'onclick' => 
                                '(function(e){ 
                                require("block_recommended_courses/cardPaginate").filteringData(e,"'.$submitid.'") })(event)');
            $cancelclassarray = array('class' => 'form-submit',
                                'onclick' => 
                                '(function(e){ require("block_recommended_courses/cardPaginate").resetingData(e,"'.$submitid.'") })(event)');
            $buttonarray[] = &$mform->createElement('button', 'filter_apply', 
                             get_string('apply','block_recommended_courses'), $applyclassarray);
            $buttonarray[] = &$mform->createElement('button', 'cancel', get_string('reset','block_recommended_courses'), $cancelclassarray);

        }
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
