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
use block_learnerscript\local\ls;

class plugin_studentwisechapters extends pluginbase {

    public function init() {
        $this->fullname = get_string('studentwisechapters', 'block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('studentwisechapters');
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

        $topicslist = (new ls)->get_subsections($row->courseid, $row->sectionid , array());
        $topicslist = array_unique($topicslist);
        $sectionslist = implode(',', $topicslist);        

        switch ($data->column) {
            case 'chapter':
               if(isset($row->sectionid) && !empty($row->sectionid)) {
                    $topic = $DB->get_record_sql("SELECT cs.name, cf.value
                        FROM {course_sections} cs 
                        JOIN {course_format_options} cf ON cf.sectionid = cs.id
                        WHERE cs.id = :sectionid AND cf.value <> 0 AND cf.name like 'parent' AND cf.format LIKE 'flexsections'", ['sectionid' => $row->sectionid]);
                    if($topic) {
                        $topicname = ($topic->name == '') ? get_section_name($row->courseid, $row->section) : $topic->name;
                        $chapter = $DB->get_field('course_sections', 'name', array('section' => $topic->value, 'course' => $row->courseid));
                        $chaptername = $chapter ? $chapter : get_section_name($row->courseid, $topic->value);

                        $row->{$data->column} = $chaptername.'<br>'.$topicname;
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
            case 'liveclass':
                $coursetotallivesessions = $DB->get_field_sql("SELECT count(DISTINCT cm.id)
                        FROM {course_modules} cm
                        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                        JOIN {zoom} z ON z.id = cm.instance
                        JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
                        WHERE cm.section IN($sectionslist) AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP()", ['modulename' => 'zoom', 'courseid' => $row->courseid]);

                    $userattendedsessions = $DB->get_field_sql("SELECT count(DISTINCT zmd.id) 
                        FROM {zoom_meeting_participants} zmp 
                        JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id 
                        JOIN {zoom} z ON z.id = zmd.zoomid
                        JOIN {course_modules} cm ON cm.instance = z.id
                        JOIN {modules} m ON m.id = cm.module
                        WHERE m.name = 'zoom' AND zmp.userid = :userid AND cm.section IN($sectionslist) AND cm.course = :course AND z.start_time <= UNIX_TIMESTAMP() $liveclassconcat ", ['userid' => $row->userid, 'course' => $row->courseid]);
                    if($userattendedsessions > $coursetotallivesessions ) {
                        $userattendedsessions = $coursetotallivesessions;
                    }
                    $row->{$data->column} = !empty($coursetotallivesessions) ? round((($userattendedsessions / $coursetotallivesessions) * 100), 2). '%' : '0%';
                break;
            case 'reading':
                $readingcount = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN (COUNT(cmc.id) / COUNT(cm.id)) ELSE 0 END
                                    FROM {course_modules} cm
                                    JOIN {modules} m ON cm.module = m.id
                                    JOIN {page} p ON p.id = cm.instance
                                    LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                    AND cmc.completionstate <> 0 AND cmc.userid = $row->userid
                                    WHERE m.name IN ('page') AND p.pagetype = 0
                                    AND cm.section IN ($sectionslist)
                                    ");
                $row->{$data->column} = !empty($readingcount) ? round(($readingcount * 100), 2) . '%' : '0%';
                break;
            case 'practisequestions':
                $practisequestionscount = $DB->get_field_sql("SELECT (SUM(gg.finalgrade/q.grade)/count(gi.id)) as grade
                                                FROM {grade_grades} AS gg
                                                JOIN {grade_items} gi ON gi.id = gg.itemid
                                                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                                                JOIN {modules} m ON m.id = cm.module
                                                JOIN {course_sections} cs ON cs.id = cm.section
                                                JOIN {course} c ON c.id = cm.course
                                                JOIN {quiz} q ON q.id = cm.instance AND q.testtype = 1
                                                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND c.id = :courseid AND gg.userid = :userid AND m.name IN ('quiz') AND gg.finalgrade IS NOT NULL GROUP BY gg.userid",
                                                ['courseid' => $row->courseid, 'userid' => $row->userid]);
                $row->{$data->column} = !empty($practisequestionscount) ? round(($practisequestionscount * 100), 2) . '%' : '0%';
                break;
            case 'testscore':
                $testscorecount = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
                FROM (SELECT gg.userid as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                FROM {grade_grades} AS gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND c.id = :qcourseid AND gg.userid = :quserid AND m.name IN('quiz') AND gg.finalgrade IS NOT NULL $chapterconcat GROUP BY gg.userid
                UNION SELECT gg.userid as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                FROM {grade_grades} AS gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND c.id = :acourseid AND gg.userid = :auserid AND m.name IN('assign') AND gg.finalgrade IS NOT NULL $chapterconcat GROUP BY gg.userid) as a", ['qcourseid' => $row->courseid, 'quserid' => $row->userid, 'acourseid' => $row->courseid, 'auserid' => $row->userid]);
                if(!empty($testscorecount) && $testscorecount > 100) {
                    $testscorecount = 100;
                }

                $row->{$data->column} = !empty($testscorecount) ? round($testscorecount, 2) . '%' : '0%';
                break;
        }
        return (isset($row->{$data->column}))? $row->{$data->column} : ' -- ';
    }
}
