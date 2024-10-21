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
  * @author: Sudharani
  * @date: 2023
  */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\ls;

class plugin_liveclassdetailscolumns extends pluginbase{
  public function init(){
    $this->fullname = get_string('liveclassdetailscolumns','block_learnerscript');
    $this->type = 'undefined';
    $this->form = true;
    $this->reporttypes = array('liveclassdetails');
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
    $totalsessiontime = $DB->get_field_sql("SELECT SUM(zmd.end_time-zmd.start_time)
                    FROM {zoom_meeting_details} zmd
                    JOIN {zoom} z ON z.id = zmd.zoomid
                    WHERE z.id = :zoomid", ['zoomid' => $row->id]);

    $attendedsession = $DB->get_field_sql("SELECT SUM(zmp.leave_time - zmp.join_time)
    FROM {zoom_meeting_participants} zmp
    JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
    JOIN {zoom} z ON z.id = zmd.zoomid
    WHERE zmp.userid = :userid AND z.id = :zoomid
    GROUP BY z.id ", ['userid' => $row->userid, 'zoomid' => $row->id]);
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
            case 'scheduleddate':
                if($row->scheduleddate) {
                    $startdate = !empty($row->scheduleddate) ? userdate($row->scheduleddate, "%d %b %Y") : '';
                    $row->{$data->column} = $startdate;

                } else {
                    $row->{$data->column} = '';
                }
            break;
            case 'scheduledtime':
                if($row->scheduleddatetime) {

                    $starttime = !empty($row->scheduleddatetime) ? userdate($row->scheduleddatetime, "%H:%M %p") : '';
                    $endtime = !empty($row->duration) ? userdate($row->scheduleddatetime+$row->duration, "%H:%M %p") : '';

                    $row->{$data->column} = $starttime.' - '.$endtime;

                } else {
                    $row->{$data->column} = '';
                }
            break;
            case 'activity':
                if($row->activityname) {
                    $row->{$data->column} = $row->activityname;

                } else {
                    $row->{$data->column} = '';
                }
            break;
            case 'percentage':
                if (isset($row->percentage) && !empty($row->percentage)) {
                    $userattendance = $row->{$data->column};
                } else {
                    $fullattended = [];
                    $partiallyattended = [];
                    $missed = 0;
                    if($attendedsession > $totalsessiontime) {
                        $attendedsession = $totalsessiontime;
                    }
                    $sessionpercentage = $totalsessiontime ? round(($attendedsession / $totalsessiontime) * 100, 2) : 0;
                    $row->sessionpercentage = $sessionpercentage; 
                }
                $row->{$data->column} = !empty($sessionpercentage) ? $sessionpercentage.'%' : 0;
                break;
            case 'status':
                if(isset($row->status) && !empty($row->status)){
                     $row->{$data->column} = $row->{$data->column};
                }else{
                   /*if(isset($row->sessionpercentage) && !empty($row->sessionpercentage)) {
                        if($row->sessionpercentage > 80) {
                            $status = get_string('attended', 'block_learnerscript');
                            $status = $status;
                        } else if(($row->sessionpercentage > 1) && ($row->sessionpercentage <= 80)) {
                            $status = get_string('partialpresent', 'block_learnerscript');
                            $status = $status;
                        }
                    } else {
                        $status = get_string('missed', 'block_learnerscript');
                        $status = $status;
                    }*/
                    if ($totalsessiontime != 0 && $attendedsession != '') {
                        $sessionpercentage = $totalsessiontime ? round(($attendedsession / $totalsessiontime) * 100, 2) : 0;
                        if ($sessionpercentage > 80) {
                            $status = get_string('attended', 'block_learnerscript');
                            $status = $status;
                        } else if($sessionpercentage > 1 && $sessionpercentage <= 80){
                             $status = get_string('partialpresent', 'block_learnerscript');
                            $status = $status;
                        }
                    } else {
                        $status = get_string('missed', 'block_learnerscript');
                        $status = $status;
                    }
                }
                $row->{$data->column} = $status;
                break;
            }
    return (isset($row->{$data->column}))? $row->{$data->column} : '';
  }
}
