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
 * Class session_form
 *
 * @package    local_packages
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_packages\form;
use core_form\dynamic_form ;
use moodle_url;
use context;
use context_system;
use Exception;
use moodle_exception;
use stdClass;
use \local_packages\local\packages as packages;

class session_form extends dynamic_form {
	public function definition () {
	  global $USER, $CFG,$DB;
	  $corecomponent = new \core_component();
	  $mform = $this->_form;
	  $id = $this->optional_param('id', 0, PARAM_INT);
    $packageid = $this->optional_param('packageid', 0, PARAM_INT);
    $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        
    $mform->addElement('hidden', 'id', $id);
    $mform->setType('id', PARAM_INT);

    $mform->addElement('hidden', 'packageid', $packageid);
    $mform->setType('packageid', PARAM_INT);

    $mform->addElement('hidden', 'courseid', $courseid);
    $mform->setType('courseid', PARAM_INT);

    $batches = $this->_ajaxformdata['batch'];
    $allbatches = array();
    if(!empty($batches)) {
      $batches = is_array($batches) ? $batches : array($batches);
      $allbatches = packages::get_batches($batches,0,$packageid,$courseid);
    }elseif ($id > 0) {
      $allbatches = packages::get_batches(array(),$id,$packageid,$courseid);
    }
    $attributes = array(
      'ajax' => 'local_packages/dynamic_dropdown_ajaxdata',
      'data-type' => 'batches',
      'id' => 'package-batches',
      'data-packageid' =>$packageid,
      'data-courseid' =>$courseid,
      'multiple'=>false
    );
    $mform->addElement('autocomplete', 'batch',get_string('batchname','local_packages'),$allbatches, $attributes);

    $mform->addElement('text', 'schedulecode', get_string('schedulecode', 'local_packages'), array());
    $mform->setType('schedulecode', PARAM_TEXT);


    $mform->addElement('date_selector', 'startdate', get_string('startdate','local_packages'));
    $mform->setType('startdate', PARAM_TEXT);
    
    $mform->addElement('date_selector', 'enddate', get_string('enddate','local_packages'));
    $mform->setType('enddate', PARAM_TEXT);
    

    $starttimeselector = packages::get_timeselector();
    $starttimedurselect[] =& $mform->createElement('select', 'hours', '', $starttimeselector['hours'],array('class'=> 'time_selector'));
    $starttimedurselect[] =& $mform->createElement('select', 'minutes', '', $starttimeselector['minutes'], array('class'=> 'time_selector'), true);
    $mform->addGroup($starttimedurselect, 'starttime', get_string('starttime','local_packages'), array(''), true);

    $endtimeselector = packages::get_timeselector();
    $endtimedurselect[] =& $mform->createElement('select', 'hours', '', $endtimeselector['hours'],array('class'=> 'time_selector'));
    $endtimedurselect[] =& $mform->createElement('select', 'minutes', '', $endtimeselector['minutes'], array('class'=> 'time_selector'), true);
    $mform->addGroup($endtimedurselect, 'endtime', get_string('endtime','local_packages'), array(''), true);

    $teachers = $this->_ajaxformdata['teacher'];
    $allteachers = array();
    if(!empty($teachers)) {
      $teachers = is_array($teachers) ? $teachers : array($teachers);
      $allteachers = packages::get_teachers($teachers,0);
    }elseif ($id > 0) {
      $allteachers = packages::get_teachers(array(),$id);
    }
    $tattributes = array(
      'ajax' => 'local_packages/dynamic_dropdown_ajaxdata',
      'data-type' => 'teachers',
      'id' => 'package-teachers',
      'data-packageid' =>$packageid,
      'data-courseid' =>$courseid,
      'multiple'=>false
    );
    $mform->addElement('autocomplete', 'teacher',get_string('teacher','local_packages'),$allteachers, $tattributes);

	}
  public function validation($data, $files) {
    global $DB;
    $errors = parent::validation($data, $files);
    $schedulecode = $data['schedulecode'];
    if(empty($data['batch'])) {
      $errors['batch'] = get_string('batchrequired','local_packages');
    } 
    if(empty($data['schedulecode'])) {
      $errors['schedulecode'] = get_string('schedulecoderequired','local_packages');
    } 
    if(!empty($data['schedulecode'])) {
      if (empty($data['id'])) {
        if($DB->record_exists_sql(" SELECT * FROM {local_package_sessions}  WHERE  schedulecode = '$schedulecode'")) {
          $errors['schedulecode'] = get_string('schedulecodeexists','local_packages',$schedulecode);
        }
      } else {
        $schedulecodeexists= $DB->get_records_sql('SELECT * FROM {local_package_sessions} WHERE schedulecode = :schedulecode AND id = :id', ['schedulecode' => $schedulecode, 'id' => $data['id']]);
        if (count($schedulecodeexists) <= 0) {
          if($DB->record_exists_sql(" SELECT * FROM {local_package_sessions}  WHERE  schedulecode = '$schedulecode'")) {
            $errors['schedulecode'] = get_string('schedulecodeexists','local_packages',$schedulecode);
          }
        }
      }
    }
    $startdate = date('Y-m-d', $data['startdate']);
    $currdate = date('Y-m-d');
    if(date("Y-m-d",$data['startdate']) > date("Y-m-d",$data['enddate'])){
      $errors['enddate'] = get_string('todatelower', 'local_packages');
    }
    if(date("Y-m-d",$data['startdate']) < $currdate){
      $errors['startdate'] = get_string('previousdate', 'local_packages');
    }
    $selectedstarttime = ($data['starttime']['hours'] * 3600) + ($data['starttime']['minutes'] * 60);
    $selectedendtime = ($data['endtime']['hours'] * 3600) + ($data['endtime']['minutes'] * 60);
    $currenttime = (date("H") * 3600) + (date("i") * 60);
    if($startdate == $currdate) {
      if ($selectedstarttime <= $currenttime) {
        $errors['starttime'] = get_string('starttimecannotbelessthannow','local_packages');
      }  
      if ($selectedendtime < $currenttime) {
        $errors['endtime'] = get_string('endtimecannotbelessthannow','local_packages');
      }
    }
    if($selectedendtime <= $selectedstarttime) {
      $errors['endtime'] = get_string('endtimeshuldhigher','local_packages');
    }
    if(!empty($data['batch'])) {
      $batch_id =  is_array($data['batch'])?implode(',',$data['batch']):$data['batch'];
      $groupid = (int) $batch_id;
      $grouprecord =$DB->get_record('local_coursegroup_section',['groupid'=>$groupid]);
      $grouprecord->batchstartdate = userdate($grouprecord->enrol_start_date,get_string('strftimedatemonthabbr', 'core_langconfig'));
      $grouprecord->batchenddate = userdate($grouprecord->enrol_end_date,get_string('strftimedatemonthabbr', 'core_langconfig'));
      if(date("Y-m-d",$data['startdate']) < date("Y-m-d",$grouprecord->enrol_start_date)){
        $errors['startdate'] = get_string('cantbelowerthanbatchstartdate', 'local_packages',$grouprecord);
      }
      if(date("Y-m-d",$data['enddate']) > date("Y-m-d",$grouprecord->enrol_end_date)){
        $errors['enddate'] = get_string('cantbehigherthanbatchenddate', 'local_packages',$grouprecord);
      }
    }
    
    if(empty($data['teacher'])) {
      $errors['teacher'] = get_string('teacherrequired','local_packages');
    }
    return $errors;
  }
  protected function get_context_for_dynamic_submission(): context {
    return context_system::instance();
  }
  protected function check_access_for_dynamic_submission(): void {
      
  }
  public function process_dynamic_submission() {
    global $CFG, $DB,$USER;
    $data = $this->get_data();
    if($data) {
      (new packages)->add_update_session($data);
    }
  }
  public function set_data_for_dynamic_submission(): void {
    global $DB;
    if ($id = $this->optional_param('id', 0, PARAM_INT)) {
      $ajaxdata = $this->_ajaxformdata;
      $sessiondata = (new packages)->set_session_data($id, $ajaxdata);
      $this->set_data($sessiondata);
    }
  }
  protected function get_page_url_for_dynamic_submission(): moodle_url {
    $id = $this->optional_param('id', 0, PARAM_INT);
    $packageid = $this->optional_param('packageid', 0, PARAM_INT);
    return new moodle_url('/local/packages/packagedetails.php?packageid='.$packageid);
  }
}

