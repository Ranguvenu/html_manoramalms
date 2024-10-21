<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package local_onlineexams
 * @subpackage local_onlineexams
 */

namespace local_onlineexams\form;
use local_users\functions\userlibfunctions as userlib;
use core;
use moodleform;
use context_system;
use context_course;
use context_coursecat;
use core_component;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/quiz/mod_form.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
//require_once($CFG->dirroot . '/local/costcenter/lib.php');

class custom_onlineexams_form extends moodleform {
    protected $onlineexam;
    protected $context;
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {

        global $USER;

        $this->formstatus = array(
            'manage_onlineexam' => get_string('manage_onlineexam', 'local_onlineexams'),
            // 'other_details' => get_string('onlineexamother_details', 'local_onlineexams'),
        );
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
    }
    /**
     * Form definition.
     */
    function definition() {
        global $DB,$OUTPUT,$CFG, $PAGE, $USER;

        $mform    = $this->_form;
        $onlineexam        = $this->_customdata['course']; // this contains the data of this form
        $onlineexam_id     = $this->_customdata['courseid']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        // $formstatus = $this->_customdata['form_status'];
        $get_onlineexamdetails = $this->_customdata['get_coursedetails'];
        $editoroptions = $this->_customdata['editoroptions'];
        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];
        if(empty($category)){
            $sql = "SELECT *
                      FROM {course_categories}
                     WHERE idnumber LIKE :idnumber";
            $yearbookv2category = $DB->get_record_sql($sql, ['idnumber' => 'yearbookv2']);
            $category = $yearbookv2category->id;
        }

        $categorycontext = context_coursecat::instance($category);
        if (!empty($onlineexam->id)) {
            $onlineexamcontext = context_course::instance($onlineexam->id);
            $context = $onlineexamcontext;
        } else {
            $onlineexamcontext = null;
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->onlineexam  = $onlineexam;
        $this->context = $context;
        // Form definition with new onlineexam defaults.

        $mform->addElement('hidden', 'form_status', $formstatus);
        $mform->setType('form_status', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'enablecompletion');
        $mform->setType('enablecompletion', PARAM_INT);
        $mform->setConstant('enablecompletion', 1);

        if(empty($onlineexam->id)){
            $onlineexamid = 0;
        }else{
            $onlineexamid = $id = $onlineexam->id;
        }

        //For Announcements activity
        $mform->addElement('hidden', 'newsitems',$courseconfig->newsitems);

        $mform->addElement('hidden', 'id', $onlineexamid, array('id' => 'onlineexamid'));
        $mform->setType('id', PARAM_INT);
		
        $core_component = new core_component();
        // if($formstatus == 0){

            $mform->addElement('hidden','category', null);
            $mform->setConstant('category', $category); 

            $mform->addElement('text','fullname', get_string('onlineexam_name','local_onlineexams'),'maxlength="254" size="50"');
            $mform->addHelpButton('fullname', 'onlineexam_name','local_onlineexams');


            if (!empty($onlineexam->id) and !has_capability('moodle/course:changefullname', $categorycontext)) {
                $mform->hardFreeze('fullname');
                $mform->setConstant('fullname', $onlineexam->fullname);
            } else if (has_capability('moodle/course:changefullname', $categorycontext)) {
                $mform->addRule('fullname', get_string('missingfullname','local_onlineexams'), 'required', null, 'client');
                $mform->setType('fullname', PARAM_TEXT);
            }

            if (!empty($onlineexam->id)) {
                $mform->addElement('static', 'shortname_static', get_string('shortname', 'local_onlineexams'), 'maxlength="100" size="20"');               
                $mform->addElement('hidden', 'shortname');
                $mform->setType('shortname', PARAM_TEXT);
                $mform->hardFreeze('shortname');
                $mform->setConstant('shortname', $onlineexam->shortname);
            } else {
                $shortnamestatic = 'oex';
                $shortname = array();
                $shortname[] = $mform->createElement('hidden',  'concatshortname', $shortnamestatic);
                $shortname[] = $mform->createElement('static',  'shortnamestatic', '', '<span class="shortnamestatic">' . $shortnamestatic . '</span>_');
                $shortname[] = $mform->createElement('text', 'shortname', '', 'maxlength="100" size="20"');
                $mform->addGroup($shortname,  'groupshortname',  get_string('shortname', 'local_onlineexams'),  array(''),  false);
                $mform->addRule('groupshortname', get_string('missingshortname', 'local_onlineexams'), 'required', null, 'client');
            }

  			$mform->addElement('hidden', 'open_coursetype');
  			$mform->setType('open_coursetype', PARAM_INT);
  			$mform->setDefault('open_coursetype', 1);

            $mform->addElement('editor','summary_editor', get_string('onlineexamsummary','local_onlineexams'), null, $editoroptions);
            $mform->addHelpButton('summary_editor', 'onlineexamsummary','local_onlineexams');
            $mform->setType('summary_editor', PARAM_RAW);
            $summaryfields = 'summary_editor';

            if ($overviewfilesoptions = course_overviewfiles_options($onlineexam)) {
              $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('onlineexamoverviewfiles','local_onlineexams'), null, $overviewfilesoptions);
              $mform->addHelpButton('overviewfiles_filemanager', 'onlineexamoverviewfiles','local_onlineexams');
              $summaryfields .= ',overviewfiles_filemanager';
            }
            // $onlineexamformats = get_sorted_course_formats(true);
            // $formonlineexamformats = array();
            // foreach ($onlineexamformats as $onlineexamformat) {
            //   $formonlineexamformats[$onlineexamformat] = get_string('pluginname', "format_$onlineexamformat");
            // }

            // if (isset($onlineexam->format)) {
            //   $onlineexam->format = course_get_format($onlineexam)->get_format(); // replace with default if not found
            //   if (!in_array($onlineexam->format, $onlineexamformats)) {
            //       // this format is disabled. Still display it in the dropdown
            //       $formonlineexamformats[$onlineexam->format] = get_string('withdisablednote', 'moodle',
            //               get_string('pluginname', 'format_'.$onlineexam->format));
            //   }
            // }
            $radioarray = [];
            $radioarray[] = $mform->createElement('radio', 'examtype', '', get_string('online_exams', 'local_onlineexams'), 0/* , $attributes */);
            $radioarray[] = $mform->createElement('radio', 'examtype', '', get_string('yearbook', 'local_onlineexams'), 1);
            $radioarray[] = $mform->createElement('radio', 'examtype', '', get_string('yearbookmocktest', 'local_onlineexams'), 2);
            $mform->addGroup($radioarray, 'radioar', get_string('test_type', 'local_onlineexams'), array(' '), false);

            $mform->addElement('advcheckbox', 'isfeaturedexam', ''/*get_string('featured_exam', 'local_onlineexams')*/, get_string('is_featured', 'local_onlineexams'), array('group' => 1), array(0, 1));
            $mform->setType('isfeaturedexam', PARAM_INT);

            $radioarr = [];
            $radioarr[] = $mform->createElement('radio', 'format', '', get_string('singleactivity', 'local_onlineexams'), 'singleactivity');
            $radioarr[] = $mform->createElement('radio', 'format', '', get_string('multipleactivities', 'local_onlineexams'), 'flexsections');
            $mform->setDefault('format', 'singleactivity');
            $mform->addGroup($radioarr, 'radioar', get_string('noofactivities', 'local_onlineexams'), array(' '), false);
            
            $attemptnumbers = range(0,10);
            $attemptnumbers[0] = get_string('unlimited'); 
            $mform->addElement('select',  'attempts', get_string('attempts', 'mod_quiz'), $attemptnumbers);
            $mform->setType('attempts', PARAM_INT);
            $mform->hideIf('attempts', 'format', 'eq', 'flexsections');
            if (!empty($onlineexam->id)) {
                $mform->setDefault('attempts', $onlineexam->attempts);
            }

            if (empty($onlineexam->maxgrade)) {
                $max_grade = 10;
            } else {
                $max_grade = $onlineexam->maxgrade;
            }
            $mform->addElement('hidden', 'maxgrade');
            $mform->setType('maxgrade', PARAM_INT);
            $mform->setDefault('maxgrade', $max_grade);
            
            $mform->addElement('text', 'gradepass', get_string('gradepass', 'local_onlineexams'));
            $mform->setType('gradepass', PARAM_FLOAT);
            $mform->hideIf('gradepass', 'format', 'eq', 'flexsections');
            $mform->setDefault('gradepass', 0);

            $mform->addElement('select', 'grademethod', get_string('grademethod', 'quiz'),
            quiz_get_grading_options());
            $mform->addHelpButton('grademethod', 'grademethod', 'quiz');
            $mform->hideIf('grademethod', 'format', 'eq', 'flexsections');

        // } elseif($formstatus == 1){
          // core quiz fields 
            $datefieldoptions = array('optional' => true);
           
            // Open and close dates.
            $mform->addElement('date_time_selector', 'timeopen', get_string('quizopen', 'quiz'),
                    $datefieldoptions);
            $mform->addHelpButton('timeopen', 'quizopenclose', 'quiz');
            $mform->hideIf('timeopen', 'format', 'eq', 'flexsections');

            $mform->addElement('date_time_selector', 'timeclose', get_string('quizclose', 'quiz'),
                    $datefieldoptions);
            $mform->hideIf('timeclose', 'format', 'eq', 'flexsections');
            // Time limit.
            $mform->addElement('duration', 'timelimit', get_string('timelimit', 'quiz'),
                    array('optional' => true));
            $mform->addHelpButton('timelimit', 'timelimit', 'quiz');
            $mform->hideIf('timelimit', 'format', 'eq', 'flexsections');

            // What to do with overdue attempts.
            $mform->addElement('select', 'overduehandling', get_string('overduehandling', 'quiz'),
                    quiz_get_overdue_handling_options());
            $mform->addHelpButton('overduehandling', 'overduehandling', 'quiz');
            $mform->hideIf('overduehandling', 'format', 'eq', 'flexsections');
            // Grace period time.
            $mform->addElement('duration', 'graceperiod', get_string('graceperiod', 'quiz'),
                    array('optional' => true));
            $mform->addHelpButton('graceperiod', 'graceperiod', 'quiz');
            $mform->hideIf('graceperiod', 'overduehandling', 'neq', 'graceperiod');
            $mform->hideIf('graceperiod', 'format', 'eq', 'flexsections');
            //---------------------------------------------------------------------------
            // Browser security choices.
            $mform->addElement('select', 'browsersecurity', get_string('browsersecurity', 'quiz'),
                    \quiz_access_manager::get_browser_security_choices());
            $mform->addHelpButton('browsersecurity', 'browsersecurity', 'quiz');
            $mform->hideIf('browsersecurity', 'format', 'eq', 'flexsections');
            
        // }
        // $mform->closeHeaderBefore('buttonar');
        $this->set_data($onlineexam);
	$mform->disable_form_change_checker();
        // Finally set the current form data
        // if(empty($onlineexam)&&$onlineexam_id>0){
        //      $onlineexam = get_course($onlineexam_id);
        // }
        // if(!empty($this->_ajaxformdata['open_certificateid'])){
        //     $onlineexam->open_certificateid = $this->_ajaxformdata['open_certificateid'];
        // }
        // if(!empty($onlineexam->open_certificateid)){
        //     $onlineexam->map_certificate = 1;
        // }

        // if(!empty($this->_ajaxformdata['open_categoryid'])){
        //     $onlineexam->open_categoryid = $this->_ajaxformdata['open_categoryid'];
        // }else{
        //     $onlineexam->open_categoryid = $yearbookv2category->id;
        // }

		// $mform->disable_form_change_checker();
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
		$form_data = data_submitted();
        // Add field validation check for duplicate shortname.
        
        $shortname = !empty(trim($data['concatshortname'])) ? trim($data['concatshortname']) . '_' . trim($data['shortname']) : trim($data['shortname']);
        if ($onlineexam = $DB->get_record('course', array('shortname' => $shortname), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $onlineexam->id != $data['id']) {
                $errors['groupshortname'] = get_string('shortnametaken', 'local_onlineexams', $onlineexam->fullname);
            }
        }
        if (empty(trim($data['shortname'])) && $data['id'] == 0) {
            $errors['groupshortname'] = get_string('shortnamecannotbeempty', 'local_onlineexams');
        }
        if (empty(trim($data['fullname']))&& $data['form_status'] == 0) {
            $errors['fullname'] = get_string('missingfullname','local_onlineexams');
        }
        if (
            isset($data['timeopen']) && $data['timeopen']
            && isset($data['timeclose']) && $data['timeclose']
        ) {
            if ($data['timeclose'] <= $data['timeopen']) {
                $errors['timeclose'] = get_string('nosameenddate', 'local_onlineexams');
            }
        }
        if(isset($data['open_onlineexamcompletiondays']) && $data['open_onlineexamcompletiondays']){
            $value = $data['open_onlineexamcompletiondays'];
            $intvalue = (int)$value;
  
            if(!("$intvalue" === "$value") || $intvalue < 0){
              $errors['open_onlineexamcompletiondays'] = get_string('numeric', 'local_classroom'); 
            }
            
          }
          if (isset($data['gradepass']) && $data['form_status'] == 0){
            
            if (array_key_exists('maxgrade', $data) AND array_key_exists('gradepass', $data)) {
                if ($data['gradepass'] > $data['maxgrade']) {
                    $errors['gradepass'] = get_string('shouldbeless','local_onlineexams',$data['maxgrade']);
                }
            }
            $value = $data['gradepass'];
            $intvalue = (int)$value;
  
            if(!("$intvalue" === "$value") || $intvalue < 0){
              $errors['gradepass'] = get_string('numeric', 'local_onlineexams');
            }
            
        }
        if(isset($data['timelimit'])){
            $value = $data['timelimit'];
            $intvalue = (int)$value;  
            if(!("$intvalue" === "$value") || $intvalue < 0){
              $errors['timelimit'] = get_string('numeric', 'local_onlineexams');
            }
        }
        
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));
        return $errors;
    }
}
