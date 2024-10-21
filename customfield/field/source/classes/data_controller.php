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


namespace customfield_source;

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
        global $DB;
        $field = $this->get_field();
        $config = $field->get('configdata');
        $formattedoptions = array();
        $formattedoptions[0] = get_string("select_source",'customfield_source');
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'sourcelist',
            'id' => 'id_customfield_source',
            'class' => 'source',
            'multiple' => false,
            'placeholder' => get_string('select_source', 'customfield_source'),
        );
     
        $context = $this->get_field()->get_handler()->get_configuration_context();
           $sourceid =     $this->get_value();
          if($sourceid != 0 && $sourceid != '' && $sourceid != 'data-source'){
            $sourcename = $DB->get_records_sql_menu("select lqs.id,lqs.name as fullname from {local_question_sources} lqs where lqs.id = $sourceid ");
            $formattedoptions = $sourcename;
        }
        $elementname = $this->get_form_element_name();
        $data = data_submitted();
        $getsourceid = $data->customfield_source;
        if($getsourceid) {
          $getsourcedata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_question_sources} WHERE  id = '$getsourceid' ");
        }
        $formattedoptions=!empty($getsourcedata) ? $getsourcedata : $formattedoptions ;
        $source = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(),$formattedoptions, $options, 
                    ['placeholder' => get_string('select_source', 'customfield_source')]);
        $source->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('sourceerror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('sourceerror','local_questions'), 'nonzero', null, 'client');
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
        $options = $this->get_field()->get_options_source();
        if (array_key_exists($value, $options)) {
            if ($value) {
              //  return "Source".$value;
            return format_string($options[$value], true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
        }
    }

        return "-";
    }
}
