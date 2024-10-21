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

class plugin_chapters extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->placeholder = true;
        $this->singleselection = true;
        $this->fullname = get_string('filterchapters', 'block_learnerscript');
        $this->reporttypes = array('studentwisechapters');
        $this->filtertype = 'custom';
        if (!empty($this->reportclass->basicparams)) {
            foreach ($this->reportclass->basicparams as $basicparam) {
                if ($basicparam['name'] == 'chapters') {
                    $this->filtertype = 'basic';
                }
            }
        }
    }

    public function summary($data) {
        return get_string('chapters', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {

        $filterchapters = isset($filters['filter_chapters']) ? $filters['filter_chapters'] : 0;
        if (!$filterchapters) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterchapters);
        } else {
            if (preg_match("/%%FILTER_CHAPTERS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filterchapters;
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
            $chapterrecords = $DB->get_records_sql("SELECT DISTINCT cf.id,
            (CASE WHEN cs.name IS NULL THEN CONCAT('Chapter',cs.section) ELSE cs.name END) as chapter
            FROM {course_format_options} as cf
            JOIN {course_sections} as cs ON cs.id = cf.sectionid
            WHERE cf.value = 0 AND cf.name like 'parent'");
            $chapterslist = array_keys($chapterrecords);
        }

        $chaptersoptions = array();
        if($selectoption){
            $chaptersoptions[0] = $this->singleselection ?
                get_string('filter_chapters', 'block_learnerscript') : get_string('select') .' '. get_string('chapters');
        }
        $chaptercourseid = ($_SESSION['courseid']) ? $_SESSION['courseid'] : 0;
        if (empty($chapterslist)) {
            $chapterss = $DB->get_records_sql("SELECT DISTINCT cf.id,
            (CASE WHEN cs.name IS NULL THEN CONCAT('Chapter',cs.section) ELSE cs.name END) as chapter
            FROM {course_format_options} as cf
            JOIN {course_sections} as cs ON cs.id = cf.sectionid
            WHERE cf.value = 0 AND cf.name like 'parent'
            AND cs.course = :chaptercourseid", ['chaptercourseid' => $chaptercourseid]);

            foreach ($chapterss as $c) {
                $chaptersoptions[$c->id] = format_string($c->chapter);
            }
        }
        return $chaptersoptions;
    }
    public function selected_filter($selected) {
        $filterdata = $this->filter_data();
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        $chaptersoptions = $this->filter_data();
        if ((!$this->placeholder || $this->filtertype == 'basic') && COUNT($chaptersoptions) > 1) {
            unset($chaptersoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_chapters', get_string('chapters', 'block_learnerscript'), $chaptersoptions,array('data-select2'=>1));
        $select->setHiddenLabel(true);
        $mform->setType('filter_chapters', PARAM_INT);
    }

}
