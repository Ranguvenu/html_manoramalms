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

class plugin_chapterwisecolumns extends pluginbase{
  public function init(){
    $this->fullname = get_string('chapterwisecolumns','block_learnerscript');
    $this->type = 'undefined';
    $this->form = true;
    $this->reporttypes = array('chapterwisereport');
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
    global $DB, $USER;
    $chapterconcat = '';
    $liveclassconcat = '';
    if (isset($this->reportfilterparams['filter_startdate']) && !empty($this->reportfilterparams['filter_startdate']) && ($this->reportfilterparams['filter_startdate'] != 0)) {
        $startdate = $this->reportfilterparams['filter_startdate'];
        $chapterconcat .= " AND cmc.timemodified >= $startdate";
        $liveclassconcat .= " AND z.start_time >= $startdate";
    }
    if (isset($this->reportfilterparams['filter_duedate']) && !empty($this->reportfilterparams['filter_duedate']) && ($this->reportfilterparams['filter_duedate'] != 0)) {
        $duedate = $this->reportfilterparams['filter_duedate'];
        $chapterconcat .= " AND cmc.timemodified <= $duedate";
        $liveclassconcat .= " AND z.start_time <= $duedate";
    }

        $topicslist = (new ls)->get_subsections($row->courseid, $row->sectionid, $sectionids = array());
        $topicslist = array_unique($topicslist);
        $sectionslist = implode(',', $topicslist);

        switch ($data->column) {
            case 'chapter':
            if (!isset($row->chapter) && isset($data->subquery)) {
                    $day = $DB->get_field_sql($data->subquery);
                } else {
                    $chapterpercent = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN (COUNT(cmc.id) / COUNT(cm.id)) ELSE 0 END
                                    FROM {course_modules} cm
                                    JOIN {modules} m ON cm.module = m.id
                                    LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                    AND cmc.completionstate = 1 AND cmc.userid = :userid $chapterconcat
                                    WHERE m.name IN ('page', 'quiz', 'assign', 'zoom') AND cm.section IN ($sectionslist)", ['userid' => $row->userid]);
                    $chaptername = $row->chapter ? $row->chapter : get_section_name($row->courseid, $row->section);
                    $progress = ($chapterpercent) ? round(($chapterpercent), 2)*100 : 0;
                    // $row->{$data->column} =  $chaptername."<br><div class='spark-report' id='spark-report$row->formatid' data-sparkline='$progress; progressbar' data-labels = 'progress' >" . $progress . "</div>";
                    $row->{$data->column} =  $chaptername."<br>
                    <div class='progress'>
                        <div class='progress-bar' style='width:".$progress."%'></div>
                      </div>
                    ";
                   
                }
                break;
            case 'chaptername':
            if (!isset($row->chapter) && isset($data->subquery)) {
                    $day = $DB->get_field_sql($data->subquery);
                } else {
                    $chaptername = $row->chapter ? $row->chapter : get_section_name($row->courseid, $row->section);
                    $row->{$data->column} =  $chaptername;
                   
                }
                break;
            case 'progress':
                if (!isset($row->progress) && isset($data->subquery)) {
                        $day = $DB->get_field_sql($data->subquery);
                    } else {
                        $chapterpercent = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN (COUNT(cmc.id) / COUNT(cm.id)) ELSE 0 END
                                        FROM {course_modules} cm
                                        JOIN {modules} m ON cm.module = m.id
                                        LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                        AND cmc.completionstate = 1 AND cmc.userid = :userid $chapterconcat
                                        WHERE m.name IN ('page', 'quiz', 'assign', 'zoom') AND cm.section IN ($sectionslist)", ['userid' => $row->userid]);
                        $progress = ($chapterpercent) ? round(($chapterpercent)*100, 0) : 0;  
                        $row->{$data->column} =  $progress;                     
                }
                break;
            case 'liveclass':
                if(isset($row->liveclass) && !empty($row->liveclass)){
                    $userattendance = $row->{$data->column};
                }else{
                    $coursetotallivesessions = $DB->get_field_sql("SELECT count(cm.id)
                        FROM {course_modules} cm
                        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                        JOIN {zoom} z ON z.id = cm.instance
                        WHERE 1 = 1 AND cm.visible = 1
                        AND cm.deletioninprogress = 0 AND cm.section IN($sectionslist) AND cm.course = :courseid AND z.start_time <= UNIX_TIMESTAMP() AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1) $liveclassconcat", ['modulename' => 'zoom', 'courseid' => $row->courseid]);

                    $userattendedsessions = $DB->get_field_sql("SELECT count(DISTINCT zmd.id) 
                        FROM {zoom_meeting_participants} zmp 
                        JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id 
                        JOIN {zoom} z ON z.id = zmd.zoomid
                        JOIN {course_modules} cm ON cm.instance = z.id
                        JOIN {modules} m ON m.id = cm.module
                        WHERE m.name = 'zoom' AND zmp.userid = :userid AND cm.section IN($sectionslist) AND cm.course = :course AND z.start_time <= UNIX_TIMESTAMP() AND z.id NOT IN (SELECT zoomid FROM {local_zoom_status} WHERE 1=1)  $liveclassconcat ", ['userid' => $row->userid, 'course' => $row->courseid]);
                    if($userattendedsessions > $coursetotallivesessions ) {
                        $userattendedsessions = $coursetotallivesessions;
                    }
                    $userattendance = !empty($coursetotallivesessions) ? ($userattendedsessions.'/'.$coursetotallivesessions) : 0;
                    
                }
                 $row->{$data->column} = $userattendance;
                break;
            case 'reading':
                $readingcount = $DB->get_field_sql("SELECT CASE WHEN COUNT(cm.id) > 0 THEN (COUNT(cmc.id) / COUNT(cm.id)) ELSE 0 END
                                    FROM {course_modules} cm
                                    JOIN {modules} m ON cm.module = m.id
                                    JOIN {page} p ON p.id = cm.instance
                                    LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                    AND cmc.completionstate = 1 AND cmc.userid = $row->userid $chapterconcat
                                    WHERE p.pagetype = 0 AND m.name IN ('page')
                                    AND cm.section IN ($sectionslist)
                                    ");
                $row->{$data->column} = !empty($readingcount) ? round(($readingcount * 100), 2) . '%' : '0%';
                break;
            case 'practicetest':
                $practisequestionscount = $DB->get_field_sql("SELECT (SUM(gg.finalgrade/q.grade)/count(gi.id)) as grade
                FROM {grade_grades} AS gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =1
                JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND c.id = :courseid AND gg.userid = :userid AND m.name IN('quiz') AND gg.finalgrade IS NOT NULL GROUP BY u.id", ['courseid' => $row->courseid, 'userid' => $row->userid]);

                $row->{$data->column} = !empty($practisequestionscount) ? round(($practisequestionscount *100), 2) . '%' : '0%';
                break;
            case 'testscore':
                $testscorecount = $DB->get_field_sql("SELECT (SUM(a.grade)/SUM(a.testcount))*100
                FROM (SELECT u.id as userid, SUM(gg.finalgrade/q.grade) as grade ,count(gi.id) as testcount
                FROM {grade_grades} AS gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {course} c ON c.id = cm.course
                JOIN {quiz} q ON q.id = cm.instance AND q.testtype =0
                JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND c.id = :qcourseid AND gg.userid = :quserid AND m.name IN('quiz') AND gg.finalgrade IS NOT NULL GROUP BY u.id
                UNION SELECT u.id as userid, SUM(gg.finalgrade/a.grade) as grade ,count(gi.id) as testcount
                FROM {grade_grades} AS gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {course} c ON c.id = cm.course
                JOIN {assign} a ON a.id = cm.instance
                JOIN {user} u ON u.id = gg.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                WHERE cs.id IN ($sectionslist) AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND c.id = :acourseid AND gg.userid = :auserid AND m.name IN('assign') AND gg.finalgrade IS NOT NULL GROUP BY u.id) as a GROUP BY a.userid", ['qcourseid' => $row->courseid, 'quserid' => $row->userid, 'acourseid' => $row->courseid, 'auserid' => $row->userid]);
                if(!empty($testscorecount) && $testscorecount > 100) {
                    $testscorecount = 100;
                }

                $row->{$data->column} = !empty($testscorecount) ? round($testscorecount, 2) . '%' : '0%';
                break;
            case 'video':
                $row->{$data->column} = '0%';
                break;
            }
    return (isset($row->{$data->column}))? $row->{$data->column} : '';
  }
}
