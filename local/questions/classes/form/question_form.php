<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_questions\form;
use core_form\dynamic_form ;
use dml_transaction_exception;
use moodle_url;
use context;
use context_system;
use Exception;
use moodle_exception;
use stdClass;
use \local_questions\local\questionbank as question;

class question_form extends dynamic_form {
	public function definition () {
	    global $USER, $CFG,$DB;
	    $corecomponent = new \core_component();
	    $mform = $this->_form;
	    $id = $this->optional_param('id', 0, PARAM_INT);
	    $mform->addElement('hidden', 'id', $id);
	    $mform->setType('id', PARAM_INT);

	}
	    
    public function validation($data, $files) {
      global $DB;
      return $errors;
   }
   protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
   }
   protected function check_access_for_dynamic_submission(): void {
       
      
   }
   public function process_dynamic_submission() {
      global $CFG, $DB,$USER;
      require_once($CFG->dirroot.'/user/profile/definelib.php');
      $data = $this->get_data();
    }
    public function set_data_for_dynamic_submission(): void {
      global $DB;
      if ($id = $this->optional_param('id', 0, PARAM_INT)) {
        $this->set_data($data);
      }
    }
    protected function get_page_url_for_dynamic_submission(): moodle_url {
      $id = $this->optional_param('id', 0, PARAM_INT);
      return new moodle_url('/local/questions/index.php');
    }
}
