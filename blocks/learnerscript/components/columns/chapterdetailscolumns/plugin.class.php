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

/** LearnerScript Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @subpackage learnerscript
  * @author: jahnavi<jahnavi@eabyas.com>
  * @date: 2022
  */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\ls;

class plugin_chapterdetailscolumns extends pluginbase{
  public function init(){
    $this->fullname = get_string('chapterdetailscolumns','block_learnerscript');
    $this->type = 'undefined';
    $this->form = true;
    $this->reporttypes = array('chapterdetailsereport');
  }
  public function summary($data){
    return format_string($data->columname);
  }
  public function colformat($data){
    $align = (isset($data->align))? $data->align : '';
    $size = (isset($data->size))? $data->size : '';
    $wrap = (isset($data->wrap))? $data->wrap : '';
    return array($align,$size,$wrap);
  }
  public function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
    global $DB;

    $topicslist = (new ls)->get_subsections($row->course, $row->id, $sectionids = array());
    $topicslist = array_unique($topicslist);
    $sectionslist = implode(',', $topicslist);

    $totalstudents = $DB->get_field_sql("SELECT COUNT(DISTINCT ue.userid)
                            FROM {course} c
                            JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                            JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
                            JOIN {role_assignments}  ra ON ra.userid = ue.userid
                            JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                            JOIN {context} ctx ON ctx.instanceid = c.id
                            JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                            AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                            WHERE c.id = :courseid", ['courseid' => $row->course]);
    switch ($data->column) {
            case 'chapter':
            if (!isset($row->chapter) && isset($data->subquery)) {
                    $day = $DB->get_field_sql($data->subquery);
                } else {
                    $chaptername = $row->chapter ? $row->chapter : get_section_name($row->course, $row->section);
                    $row->{$data->column} =  $chaptername;
                   
                }
                break;
            case 'video':
                $row->{$data->column} = '-';

                break;
            case 'liveclass':
                 $liveclasspresent = $DB->get_field_sql("SELECT COUNT(DISTINCT zmp.userid)
                                    FROM {zoom_meeting_details} zmd
                                    JOIN {zoom_meeting_participants} zmp ON zmd.id = zmp.detailsid
                                    JOIN {zoom} z ON z.id = zmd.zoomid
                                    JOIN {course_modules} cm ON cm.instance = z.id
                                    JOIN {course_sections} cs ON cs.id = cm.section
                                    JOIN {modules} m ON m.id = cm.module  AND m.name ='zoom'
                                    JOIN {user} u ON u.id = zmp.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 
                                    WHERE cs.id IN ($sectionslist) AND cm.course = $row->course AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
                            FROM {course} c
                            JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                            JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
                            JOIN {role_assignments}  ra ON ra.userid = ue.userid
                            JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                            JOIN {context} ctx ON ctx.instanceid = c.id
                            JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                            AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1
                            WHERE c.id = :courseid)",['courseid' => $row->course]);
                $row->{$data->column} = !empty($totalstudents) ? round(($liveclasspresent / $totalstudents) * 100, 2) . '%' : '0%';
                break;
            case 'reading':
                $totalpagescount = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN COUNT(cm.id) ELSE 0 END
                                FROM {course_modules} cm
                                JOIN {modules} m ON cm.module = m.id
                                JOIN {page} p ON p.id = cm.instance
                                WHERE m.name IN ('page')
                                AND p.pagetype = 0 AND cm.section IN ($sectionslist) AND cm.course =  $row->course");
                $pagescount = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN COUNT(cmc.id) ELSE 0 END
                                FROM {course_modules} cm
                                JOIN {modules} m ON cm.module = m.id
                                JOIN {page} p ON p.id = cm.instance
                                JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                JOIN {user} u ON u.id = cmc.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                                AND cmc.completionstate <> 0
                                WHERE p.pagetype = 0 AND m.name IN ('page')
                                AND cm.section IN ($sectionslist) AND cm.course =  $row->course");
                $readingcount = !empty($totalpagescount) ? ($pagescount / $totalpagescount) : 0;
                $row->{$data->column} = !empty($totalstudents) ? round((($readingcount / $totalstudents) * 100), 2) . '%' : '0%';
                break;
            case 'practicequestion':
                $practicetestpercent = $DB->get_field_sql("SELECT (SUM(gg.finalgrade/q.grade)/count(gi.id)) as grade
                                      FROM {grade_grades} AS gg
                                      JOIN {grade_items} gi ON gi.id = gg.itemid
                                      JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                                      JOIN {modules} m ON m.id = cm.module
                                      JOIN {course_sections} cs ON cs.id = cm.section
                                      JOIN {course} c ON c.id = cm.course
                                      JOIN {quiz} q ON q.id = cm.instance AND q.testtype = 1
                                      JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                                      WHERE cs.id IN ($sectionslist) AND c.id = :courseid AND m.name IN ('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' GROUP BY c.id",
                                      ['courseid' => $row->course]);
                $row->{$data->column} = !empty($totalstudents) ? round($practicetestpercent * 100, 2) . '%' : '0%';
                break;
            case 'testscore':
                $testscorespercent = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100 
                                FROM (SELECT c.id as courseid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                                FROM {grade_grades} AS gg
                                JOIN {grade_items} gi ON gi.id = gg.itemid
                                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                                JOIN {modules} m ON m.id = cm.module
                                JOIN {course} c ON c.id = cm.course
                                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                                JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                                WHERE cm.section IN ($sectionslist) AND m.name IN('quiz') AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL GROUP BY c.id
                                UNION SELECT c.id as courseid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                                FROM {grade_grades} AS gg
                                JOIN {grade_items} gi ON gi.id = gg.itemid
                                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                                JOIN {modules} m ON m.id = cm.module
                                JOIN {course} c ON c.id = cm.course
                                JOIN {assign} a ON a.id = cm.instance
                                JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                                WHERE cm.section IN ($sectionslist) AND m.name IN('assign') AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gg.finalgrade IS NOT NULL GROUP BY c.id) as a GROUP BY a.courseid");
                $row->{$data->column} = !empty($totalstudents) ? round($testscorespercent, 2) . '%' : '0%';

                break;
            }
    return (isset($row->{$data->column}))? $row->{$data->column} : '';
  }
}
