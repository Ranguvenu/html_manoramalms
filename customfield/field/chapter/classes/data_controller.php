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


namespace customfield_chapter;

defined('MOODLE_INTERNAL') || die;
use stdClass;
use question_bank;

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
        $categoryid = optional_param('category', 0, PARAM_INT);
        $questionid = optional_param('id', 0 , PARAM_INT);
        if(!$questionid){
            $id = optional_param('qcategory', 0 , PARAM_INT);
            if(!$id){
            $id = optional_param('category', 0 , PARAM_INT);
            }
        }else{
            $questioninfo = question_bank::load_question($questionid);
           $id = $questioninfo->category;
        }
        $field = $this->get_field();
        $config = $field->get('configdata');
        $options = $field->get_options();
        $context = $this->get_field()->get_handler()->get_configuration_context();
        foreach ($options as $key => $option) {
            // Multilang formatting with filters.
            $formattedoptions[$key] = format_string($option, true, ['context' => $context]);
        }

        $elementname = $this->get_form_element_name();
        
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'chapterlist',
            'id' => 'id_customfield_chapter',
            'class' => 'chapter',
            'multiple' => false,
            'placeholder' => get_string('select_chapter', 'customfield_chapter'),
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedchapter();}) }) (event)",
        );

        $chapterarray=array();
        $chapterarray[] = get_string("select_chapter",'customfield_chapter');

        $chapterid =  $this->get_value();
       

        if($chapterid != 0 && $chapterid != '' && $chapterid != 'data-chapter'){
             $chapters = $DB->get_records_sql_menu("SELECT lc.id AS id, lc.name AS fullname 
                            FROM {local_chapters} AS lc WHERE id = $chapterid ");
            $chapterarray = $chapters;
        }
          $data = data_submitted();
          $getchapterid = $data->customfield_chapter;
        if($getchapterid) {
          $getchapterdata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_chapters} WHERE  id = '$getchapterid' ");
        }
        $chapterarray=!empty($getchapterdata) ? $getchapterdata : $chapterarray ;
        $course = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(),$chapterarray, $options);
        $course->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('chaptererror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('chaptererror','local_questions'), 'nonzero', null, 'client');
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
        if (array_key_exists($value, $options)) {
            return format_string($options[$value], true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
        }

        return "-";
    }
}
