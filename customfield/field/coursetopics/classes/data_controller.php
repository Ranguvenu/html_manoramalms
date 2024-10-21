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


namespace customfield_coursetopics;

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
            'data-type' => 'topicslist',
            'id' => 'id_customfield_coursetopics',
            'class' => 'topics',
            'multiple' => false,
            'placeholder' => get_string('select_unit', 'customfield_unit'),
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedtopics();}) }) (event)",
        );

        $coursetopicarray=array();
        $coursetopicarray[] = get_string("select_unit",'customfield_unit');

        $topicids =  $this->get_value();

        if($topicids != 0 && $topicids != '' && $topicids != 'data-topics'){
           // $topics = $DB->get_records_sql_menu("select id,(CASE WHEN name IS NULL THEN CONCAT('Unit',section) ELSE name END) as fullname from {course_sections} where id in($topicids)");
            $topics = $DB->get_records_sql_menu("SELECT lu.id AS id, lu.name AS fullname 
                           FROM {local_units} AS lu WHERE  id = $topicids ");
            $coursetopicarray = $topics;
        }

          $data = data_submitted();
          $getunitid = $data->customfield_unit;
        if($getunitid) {
          $getunitdata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_units} WHERE  id = '$getunitid' ");
        }
        $coursetopicarray=!empty($getunitdata) ? $getunitdata : $coursetopicarray ;

        $course = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(),$coursetopicarray, $options);
        $course->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('uniterror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('uniterror','local_questions'), 'nonzero', null, 'client');
        }
    }
    

    public function instance_form_save(stdClass $datanew) {
        global $USER, $DB;
        $elementname = $this->get_form_element_name();
        if (!property_exists($datanew, $elementname)) {
            return;
        }
            //$options = array_filter($datanew->$elementname);
            //$value = implode(',', $options);
            $value =$datanew->$elementname;
            $courses = new stdClass;
            $courses->questionbankid = $datanew->category;
            $courses->goalid = $datanew->customfield_goal;
            $courses->boardid = $datanew->customfield_board;
            $courses->classid = $datanew->customfield_class;
            $courses->courseid = $datanew->customfield_courses;
            $courses->topicid = $datanew->customfield_unit;
            $courses->chapterid = $datanew->customfield_chapter;
            $courses->unitid = $datanew->customfield_topics;
            $courses->conceptid = $datanew->customfield_concept;
            $courses->questionid = $datanew->id;
            $courses->timecreated = time();
            $courses->difficulty_level = $datanew->customfield_difficultylevel;
            $courses->cognitive_level = $datanew->customfield_cognitivelevel;
            $courses->source = $datanew->customfield_source;
            
            //$courses->learning_objective =$datanew->customfield_learningobjective;
            $courses->usercreated = $USER->id;
            $newid = $DB->insert_record('local_questions_courses', $courses);
            if($newid){
                 $qreview = new stdClass;
                 $qreview->questionid = $datanew->id;
                 $qreview->courseid = $datanew->customfield_courses;
                 $qreview->questionbankid = $datanew->category;
                 $qreview->reviewdby = $USER->id;
                 $qstatuses = ['draft'=>'draft','readytoreview'=>'readytoreview','underreview'=>'underreview','reject'=>'reject','publish'=>'publish'];
                 if(isset($qstatuses[$datanew->customfield_qstatus])){
                  $qreview->qstatus = $datanew->customfield_qstatus;
                 }else{
                   $qreview->qstatus = 'draft';
                 }
                 $qreview->reviewdon = time();
                 $DB->insert_record('local_qb_questionreview', $qreview);
            }
            $this->data->set($this->datafield(), $value);
            $this->data->set('value', $value);
            $this->save();


    }
    public function delete() {
      global $DB;
      $qid = $this->get('instanceid');
      $getqentriessql = " SELECT questionbankentryid FROM {question_versions} WHERE questionid = :qid ";
      $getqentries = $DB->get_field_sql($getqentriessql,['qid' => $qid]);
      $getquestionidsql = " SELECT GROUP_CONCAT(questionid) 
                            FROM {question_versions} 
                            WHERE questionbankentryid = :getqentries ";
      $getquestionids =  $DB->get_field_sql($getquestionidsql,['getqentries' => $getqentries]);
      $deletesql = " DELETE FROM {local_questions_courses} WHERE questionid IN ($getquestionids)  ";
      return $DB->execute($deletesql);
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
        $values=explode(",",$value);
        if ($this->is_empty($value)) {
            return null;
        }
        $options = $this->get_field()->get_options();
        if($options){   
        $arr=array();
        foreach($values as $v){
            $value = $v;
            if (array_key_exists($value, $options)) {
                $displayvalues = format_string($options[$value], true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
                array_push($arr,$displayvalues);
            }
        }
        return implode(',',$arr);
        }
        return "-";
    }
}
