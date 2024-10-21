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
 * TODO describe file plugin.class
 *
 * @package    block_learnerscript
 * @copyright  2023 Jahnavi <jahnavi.nanduri@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use stdClass;

class plugin_goals extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->placeholder = true;
        $this->singleselection = true;
        $this->fullname = get_string('filtergoals', 'block_learnerscript');
        $this->reporttypes = array('users', 'goalsusers');
        $this->filtertype = 'custom';
        if (!empty($this->reportclass->basicparams)) {
            foreach ($this->reportclass->basicparams as $basicparam) {
                if ($basicparam['name'] == 'goals') {
                    $this->filtertype = 'basic';
                }
            }
        }
    }

    public function summary($data) {
        return get_string('goals', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {

        $filtergoals = isset($filters['filter_goals']) ? $filters['filter_goals'] : 0;
        if (!$filtergoals) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtergoals);
        } else {
            if (preg_match("/%%FILTER_CHAPTERS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filtergoals;
                return str_replace('%%FILTER_CHAPTERS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true){
        global $DB, $CFG;
        $properties = new stdClass();
        $properties->courseid = SITEID;

        $reportclassname = 'block_learnerscript\lsreports\report_' . $this->report->type;
        $reportclass = new $reportclassname($this->report, $properties);

        if ($this->report->type != 'sql') {
            $components = (new \block_learnerscript\local\ls)->cr_unserialize($this->report->components);
        } else {
            $goalrecords = $DB->get_records_sql("SELECT id, name
            FROM {local_hierarchy}
            WHERE parent = :parentid AND depth = :depth",
            ['parentid' => 0, 'depth' => 1]);
            $goalslist = array_keys($goalrecords);
        }

        $goalsoptions = array();
        if($selectoption){
            $goalsoptions[0] = $this->singleselection ?
                get_string('filter_goals', 'block_learnerscript') : get_string('select') .' '. get_string('goals');
        }

        if (empty($goalslist)) {
            $goalss = $DB->get_records_sql("SELECT id, name
                            FROM {local_hierarchy}
                            WHERE parent = :parentid AND depth = :depth",
                            ['parentid' => 0, 'depth' => 1]);

            foreach ($goalss as $c) {
                $goalsoptions[$c->id] = format_string($c->name);
            }
        }
        return $goalsoptions;
    }
    public function selected_filter($selected) {
        $filterdata = $this->filter_data();
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        $goalsoptions = $this->filter_data();
        if ((!$this->placeholder || $this->filtertype == 'basic') && COUNT($goalsoptions) > 1) {
            unset($goalsoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_goals', get_string('goals', 'block_learnerscript'), $goalsoptions,array('data-select2'=>1));
        $select->setHiddenLabel(true);
        $mform->setType('filter_goals', PARAM_INT);
    }

}
