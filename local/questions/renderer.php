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
use \local_questions\local\questionbank as question;
use html_writer;
use moodle_url;
use context_system;

class local_questions_renderer extends plugin_renderer_base {
	public function get_questions($filter = false) {
	    $systemcontext = context_system::instance();
	    $options = array('targetID' => 'viewquestiondata','perPage' => 25, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
	     $options['methodName']='local_questions_viewquestions';
	    $options['templateName']='local_questions/listofquestions';
	    $options = json_encode($options);
	    $filterdata = json_encode(array());
	    $dataoptions = json_encode(array('contextid' => $systemcontext->id));
	    $context = [
	            'targetID' => 'viewquestiondata',
	            'options' => $options,
	            'dataoptions' => $dataoptions,
	            'filterdata' => $filterdata,
	    ];
	    if($filter){
	        return  $context;
	    }else{
	        return  $this->render_from_template('theme_horizon/cardPaginate', $context);
	    }
	}
	public function global_filter($filterparams) {
	    global $DB, $PAGE, $OUTPUT;
	    
	    return $this->render_from_template('theme_horizon/global_filter', $filterparams);
	}
	public function questionview($filterparams) {
	    global $DB, $PAGE, $OUTPUT;
	    $systemcontext = context_system::instance();
	    echo $this->render_from_template('local_questions/questionview', $filterparams);
	}
	public function render_custom_condition($displaydata){
		return $this->render_from_template('local_questions/custom_fields_condition', $displaydata);
	}
}	

