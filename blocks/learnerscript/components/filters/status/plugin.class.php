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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Sudharani
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use stdClass;

class plugin_status extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true; 
        $this->placeholder = true;
        $this->singleselection = true;
        $this->fullstatus = get_string('status', 'block_learnerscript');
        $this->reporttypes = array('');
        $this->filtertype = 'custom';
        if (!empty($this->reportclass->basicparams)) {
            foreach ($this->reportclass->basicparams as $basicparam) {
                if ($basicparam['status'] == 'status') {
                    $this->filtertype = 'basic';
                }
            }
        }
    }

    public function summary($data) {
        return get_string('filterstatus_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {

        $filterstatus = isset($filters['filter_status']) ? $filters['filter_status'] : 0;
        if (!$filterstatus) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterstatus);
        } else {
            if (preg_match("/%%FILTER_STATUS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filterstatus;
                return str_replace('%%FILTER_STATUS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true){
        global $DB, $CFG;
        $properties = new stdClass();
        $properties->courseid = SITEID;

        $reportclassstatus = 'block_learnerscript\lsreports\report_' . $this->report->type;
        $reportclass = new $reportclassstatus($this->report, $properties);

        if ($this->report->type != 'sql') {
            $components = (new \block_learnerscript\local\ls)->cr_unserialize($this->report->components);
        } else {
            $statuslist = array();
        }
        $statusoptions = array();
        if($selectoption){
            $statusoptions[-1] = $this->singleselection ?
                get_string('filter_status', 'block_learnerscript') : get_string('select') .' '. get_string('status', 'block_learnerscript');
        }
        if($this->report->type == 'readingreport' || $this->report->type == 'readingdetails') {
            if(empty($statuslist)){
                $statusoptions = [0 => get_string('all', 'block_learnerscript'), 1 => get_string('completed', 'block_learnerscript'), 2 => get_string('learning', 'block_learnerscript'), 3 => get_string('notstarted', 'block_learnerscript')];
            }
        } else if($this->report->type == 'liveclassreport' || $this->report->type == 'liveclassdetails') {
            if(empty($statuslist)){
                $statusoptions = [0 => get_string('all', 'block_learnerscript'), 1 => get_string('missed', 'block_learnerscript'), 2 => get_string('partialpresent', 'block_learnerscript'), 3 => get_string('attended', 'block_learnerscript')];
            }
        } else if($this->report->type == 'testscorereport' || $this->report->type == 'testscoredetails') {
            if(empty($statuslist)){
                $statusoptions = [0 => get_string('all', 'block_learnerscript'), 1 => get_string('submitted', 'block_learnerscript'), 2 => get_string('missed', 'block_learnerscript'), 3 => get_string('notstarted', 'block_learnerscript'), 4 => get_string('inprogress', 'block_learnerscript')];
            }
        }
        
        return $statusoptions;
    }
    public function selected_filter($selected) {
        $filterdata = $this->filter_data();
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        $statusoptions = $this->filter_data(); 
        if ((!$this->placeholder || $this->filtertype == 'basic') && COUNT($statusoptions) > 1) {
            unset($statusoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_status', get_string('status', 'block_learnerscript'), $statusoptions, array('data-select2' => 1));
        $select->setHiddenLabel(true);
        $mform->setType('filter_status', PARAM_INT);
    }

}
