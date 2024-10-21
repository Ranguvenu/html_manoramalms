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
 * @copyright  2023 Sudharani <sudharani.sadula@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\ls;

class plugin_readingdetailscolumns extends pluginbase {

    public function init() {
        $this->fullname = get_string('readingdetails', 'block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('readingdetails');
    }

    public function summary($data) {
        return format_string($data->columname);
    }

    public function colformat($data) {
        $align = (isset($data->align)) ? $data->align : '';
        $size = (isset($data->size)) ? $data->size : '';
        $wrap = (isset($data->wrap)) ? $data->wrap : '';
        return array($align, $size, $wrap);
    }

    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $DB;
        $readconcat = '';
        if (isset($this->reportfilterparams['filter_startdate']) && !empty($this->reportfilterparams['filter_startdate']) && ($this->reportfilterparams['filter_startdate'] != 0)) {
            $startdate = $this->reportfilterparams['filter_startdate'];
            $readconcat .= " AND p.timemodified >= $startdate";
            $cmadded .= " AND cm.added >= $startdate";
        }
        if (isset($this->reportfilterparams['filter_duedate']) && !empty($this->reportfilterparams['filter_duedate']) && ($this->reportfilterparams['filter_duedate'] != 0)) {
            $duedate = $this->reportfilterparams['filter_duedate'];
            $readconcat .= " AND p.timemodified <= $duedate";
            $cmadded .= " AND cm.added <= $duedate";
        }
        $pages = $DB->get_records_sql("SELECT DISTINCT p.id
                                    FROM {page} p 
                                    JOIN {course_modules} cm ON p.id = cm.instance
                                    JOIN {modules} m ON cm.module = m.id AND m.name =:modulename
                                    WHERE p.pagetype = 0 AND cm.section = :sectionid $readconcat ", ['modulename' => 'page', 'sectionid' => $row->sectionid]);
        $coursemodules = $DB->get_records_sql("SELECT DISTINCT cm.id
                                    FROM {course_modules} cm
                                    JOIN {modules} m ON cm.module = m.id AND m.name =:modulename
                                    JOIN {page} p ON p.id = cm.instance
                                    WHERE p.pagetype = 0 AND cm.section = :sectionid $cmadded", ['modulename' => 'page', 'sectionid' => $row->sectionid]);
        $topic = $DB->get_record_sql("SELECT cs.name, cf.value
            FROM {course_sections} cs 
            JOIN {course_format_options} cf ON cf.sectionid = cs.id
            WHERE cs.id = :sectionid AND cf.value <> 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);

        switch ($data->column) {
            case 'chapter':
                if(isset($row->sectionid) && !empty($row->sectionid)) {
                    if($topic) {
                        $topicname = ($topic->name == '') ? get_section_name($row->courseid, $row->section) : $topic->name;
                        $parent_chapterid= (new ls)->get_parent_chapter($row->courseid, $row->sectionid);

                        // $chapter = $DB->get_field('course_sections', 'name', array('section' => $topic->value, 'course' => $row->courseid));
                        $chapter = $DB->get_field('course_sections', 'name', array('id' => $parent_chapterid, 'course' => $row->courseid));
                        $chaptername = $chapter ? $chapter : get_section_name($row->courseid, $parent_chapterid);

                        $row->{$data->column} = $chaptername;
                    } else {
                        $chapter = $DB->get_record_sql("SELECT cs.name 
                        FROM {course_sections} cs 
                        JOIN {course_format_options} cf ON cf.sectionid = cs.id
                        WHERE cs.id = :sectionid AND cf.value = 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);
                        $chaptername = ($chapter->name) ? ($chapter->name) : get_section_name($row->courseid, $row->section);
                        $row->{$data->column} = $chaptername;
                    }
                } else {
                    $row->{$data->column} = 'NA';
                }
                break;
            case 'topic':
                if($topic) {
                    $topicname = ($topic->name == '') ? get_section_name($row->courseid, $row->section) : $topic->name;
                    $row->{$data->column} = $topicname;
                } else {
                    $row->{$data->column} = '';
                }
                break;
            case 'timespend':
                if ($pages) {
                    $pageslist = array_keys($pages);
                    list($pagetimesql, $pagetimeparams) = $DB->get_in_or_equal($pageslist, SQL_PARAMS_NAMED);
                    $pagetimeparams['modulename'] = 'page';
                    $pagetimeparams['userid'] = $row->userid;
                    $pageactvytotaltimespent = $DB->get_field_sql("SELECT SUM(mt.timespent)
                            FROM {block_ls_modtimestats} mt
                            JOIN {course_modules} cm ON cm.id = mt.activityid
                            JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                            WHERE m.name = 'page'
                            AND mt.userid = :userid
                            AND cm.instance $pagetimesql", $pagetimeparams);
                } else {
                    $pageactvytotaltimespent = '';
                }
                
                $row->{$data->column} = (!empty($pageactvytotaltimespent) && $pageactvytotaltimespent != 0) ? (new ls)->strTime($pageactvytotaltimespent) : 0;
                break;
            case 'status':
                if ($pages) {
                    $pageslist = array_keys($pages);
                    list($pagesql, $pageparams) = $DB->get_in_or_equal($pageslist, SQL_PARAMS_NAMED);
                    $pageparams['modulename'] = 'page';
                    $pageparams['userid'] = $row->userid;
                    $pageparams['courseid'] = $row->courseid;
                    $pageparams['sectionid'] = $row->section;
                    $readingcount = $DB->get_field_sql("SELECT COUNT(DISTINCT cmc.id)
                                        FROM {course_modules_completion} cmc
                                        JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                                        JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                                        JOIN {page} p ON p.id = cm.instance
                                        WHERE p.pagetype = 0 AND cmc.completionstate = 1
                                        AND cmc.userid = :userid AND cm.course = :courseid AND p.id $pagesql", $pageparams);

                    if($readingcount == count($pages)) {
                        $status = get_string('completed', 'block_learnerscript');
                    } else {
                        $coursemoduleslist = array_keys($coursemodules);
                        list($pageviewsql, $pageviewparams) = $DB->get_in_or_equal($coursemoduleslist, SQL_PARAMS_NAMED);
                        $pageviewparams['userid'] = $row->userid;
                        $pageviewed = $DB->get_field_sql("SELECT count(pc.id)
                                        FROM {block_ls_pageviewed} pc
                                        WHERE pc.userid = :userid
                                        AND pc.page $pageviewsql ", $pageviewparams);
                        if(!empty($pageviewed) && $pageviewed > 0) {
                            $status = get_string('learning', 'block_learnerscript');
                        } else {
                            $status = get_string('notstarted', 'block_learnerscript');
                        }

                    }
                } else {
                    $status = 'NA';
                }
                
                $row->{$data->column} = !empty($status) ? $status : '';
                break;
        }
        return (isset($row->{$data->column}))? $row->{$data->column} : ' -- ';
    }
}
