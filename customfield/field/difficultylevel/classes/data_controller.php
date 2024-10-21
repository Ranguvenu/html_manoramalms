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


namespace customfield_difficultylevel;

defined('MOODLE_INTERNAL') || die;
use stdClass;

class data_controller extends \core_customfield\data_controller {

    /**
     * Return the name of the field where the information is stored
     * @return string
     */
    public function datafield() : string {
        return 'charvalue';
    }

    /**
     * Returns the default value as it would be stored in the database (not in human-readable format).
     *
     * @return mixed
     */
    public function get_default_value() {
        // $defaultvalue = $this->get_field()->get_configdata_property('defaultvalue');
        if ('' . $defaultvalue !== '') {
            $key = array_search($defaultvalue, $this->get_field()->get_options());
            if ($key !== false) {
                return $key;
            }
        }
        return 0;
    }

    /**
     * Add fields for editing a textarea field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function instance_form_definition(\MoodleQuickForm $mform) {
        
        $field = $this->get_field();
        $config = $field->get('configdata');
        $formattedoptions = array();
        $formattedoptions[] = get_string("select_difficultylevel",'customfield_difficultylevel');
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'difficultylist',
            'id' => 'id_customfield_difficulty',
            'class' => 'difficulty',
            'multiple' => false,
            'placeholder' => get_string('select_difficultylevel', 'customfield_difficultylevel')
        );
        $selectoptions = [];
        $selectoptions[0] = get_string("select_difficultylevel",'customfield_difficultylevel');
        $selectoptions['1'] =  get_string('high','customfield_difficultylevel');
        $selectoptions['2'] =  get_string('medium','customfield_difficultylevel');
        $selectoptions['3'] = get_string('low','customfield_difficultylevel');
        $context = $this->get_field()->get_handler()->get_configuration_context();
            $difid =     $this->get_value();
          if($difid != 0 && $difid != '' && $difid != 'data-difficultylevel'){
      
        if(isset($selectoptions[$difid]))
            $formattedoptions[$difid] = $selectoptions[$difid];
        }
        $elementname = $this->get_form_element_name();
            $data = data_submitted();
          $getdiffid = $data->customfield_difficultylevel;
        if(isset($selectoptions[$getdiffid])) {
          $getdiffdata[$getdiffid] = $selectoptions[$getdiffid];
        }
        $formattedoptions = !empty($getdiffdata) ? $getdiffdata : $formattedoptions  ;
        $course = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(),$formattedoptions, $options, 
                    ['placeholder' => get_string('select_difficultylevel', 'customfield_difficultylevel')]);
        $course->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('difficultyerror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('difficultyerror','local_questions'), 'nonzero', null, 'client');
        }
        
    }


    /**
     * Validates data for this field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function instance_form_validation(array $data, array $files) : array {
        $errors = parent::instance_form_validation($data, $files);
        if ($this->get_field()->get_configdata_property('required')) {
            // Standard required rule does not work on select element.
            $elementname = $this->get_form_element_name();
            if (empty($data[$elementname])) {
                $errors[$elementname] = get_string('err_required', 'form');
            }
        }
        return $errors;
    }

    /**
     * Returns value in a human-readable format
     *
     * @return mixed|null value or null if empty
     */
    public function export_value() {
        $value = $this->get_value();
        if ($this->is_empty($value)) {
            return null;
        }

        $options = $this->get_field()->get_options();
        //if (array_key_exists($value, $options)) {
        if($value == 1){
                return "High";
            //return format_string($options[$value], true,
             //   ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
        }else if($value == 2){
            return "Medium";
        }else if($value == 3){
         return "Low";
        }

        return "-";
    }
}
