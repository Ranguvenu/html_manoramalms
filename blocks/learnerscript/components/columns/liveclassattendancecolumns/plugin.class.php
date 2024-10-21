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

class plugin_liveclassattendancecolumns extends pluginbase{
	public function init(){
		$this->fullname = get_string('liveclassattendancecolumns','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('liveclassattendance');
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
		$courseconcat = '';
		$zoomcourseid = '';
		if (isset($this->reportfilterparams['filter_courses'])) {
			$courseid = $this->reportfilterparams['filter_courses'];
			$courseconcat .= "  AND c.id = $courseid ";
			$zoomcourseid .= " AND zz.course = $courseid";
			$zoomcourse .= " AND z.course = $courseid";
		}
		$meetingid = $DB->get_fieldset_sql("SELECT z.id
		FROM {zoom} z
		JOIN {course_modules} cm ON cm.instance = z.id AND z.course = cm.course
		JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
		JOIN {course} c ON c.id = cm.course
		WHERE 1=1 AND c.visible =1 AND z.start_time <= UNIX_TIMESTAMP() $courseconcat GROUP BY z.id");

		$numericpercent = filter_var($row->attendancepercentages, FILTER_SANITIZE_NUMBER_INT);
		switch ($data->column) {
            case 'studentsattended':
				if ($numericpercent == 0) {
					$studentcount = 0;
					$userenrolments = $DB->get_field_sql("SELECT count(DISTINCT ue.userid)
						FROM {user_enrolments} ue
						JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
						JOIN {course} c ON e.courseid = c.id AND e.status = 0
						JOIN {role_assignments}  ra ON ra.userid = ue.userid
						JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
						JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
						JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
						WHERE c.visible = 1 $courseconcat");
					$attended = $DB->get_field_sql("SELECT count(DISTINCT zmp.userid) 
						FROM {zoom_meeting_participants} zmp
						JOIN {zoom_meeting_details} zmd ON zmd.id = zmp.detailsid
						JOIN {zoom} z ON z.id = zmd.zoomid
						JOIN {course_modules} cm ON cm.instance = z.id
						JOIN {modules} m ON m.id = cm.module
						WHERE m.name = 'zoom' $zoomcourse AND z.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
                            FROM {course} c
                            JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                            JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
                            JOIN {role_assignments}  ra ON ra.userid = ue.userid
                            JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                            JOIN {context} ctx ON ctx.instanceid = c.id
                            JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
                            AND ra.contextid = ctx.id AND ctx.contextlevel = 50 
                            WHERE 1=1 AND c.visible = 1 $courseconcat)");
				
						/*$attended = $DB->get_field_sql("SELECT (COUNT(DISTINCT ue.userid)) - ((SELECT COUNT(DISTINCT(CONCAT(zmp.userid, z.id))) AS sas
						FROM {zoom_meeting_participants} zmp
						JOIN {zoom_meeting_details} zmd ON zmd.id = zmp.detailsid
						JOIN {zoom} z ON z.id = zmd.zoomid
						JOIN {course_modules} cm ON cm.instance = z.id
						JOIN {modules} m ON m.id = cm.module
						WHERE m.name = 'zoom' $zoomcourse AND cm.course = c.id AND z.start_time <= UNIX_TIMESTAMP())) AS remain
											FROM {user_enrolments} ue
											JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
											JOIN {course} c ON e.courseid = c.id AND e.status = 0
											JOIN {role_assignments}  ra ON ra.userid = ue.userid
											JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
											JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
											JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
											WHERE c.visible = 1 $courseconcat");*/
				$row->{$data->column} = $userenrolments ? ($userenrolments-$attended) : 0;

				}else if($numericpercent == 25) {
					$studentcount = 0;
						if(!empty($meetingid)) {
							foreach($meetingid AS $zid) {

								$totalsessiontime = $DB->get_field_sql("SELECT SUM(zmd.end_time-zmd.start_time)
					                FROM {zoom_meeting_details} zmd
					                JOIN {zoom} z ON z.id = zmd.zoomid
					                WHERE z.id = :zoomid AND z.start_time <= UNIX_TIMESTAMP()", ['zoomid' => $zid]);
								if($totalsessiontime != '' && $totalsessiontime !=0 && $totalsessiontime != NULL) {
									$attended = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/$totalsessiontime)*100, 2) as attend
										FROM {zoom_meeting_participants} zmp
										JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
										JOIN {zoom} zz ON zz.id = zmd.zoomid
										WHERE zz.id = $zid $zoomcourseid AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
									FROM {user_enrolments} ue
									JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
									JOIN {course} c ON e.courseid = c.id AND e.status = 0
									JOIN {role_assignments}  ra ON ra.userid = ue.userid
									JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
									JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
									JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
									WHERE c.visible = 1 $courseconcat)
										GROUP BY zz.id, zmp.userid) as s WHERE s.attend BETWEEN 1 AND 25 ");
								} else {
									$attended = 0;
								}
								$studentcount += $attended;
							}
					}
				$row->{$data->column} = $studentcount;

				} elseif($numericpercent == 50) {
					$studentcount = 0;
					if(!empty($meetingid)) {
						foreach($meetingid AS $zid) {
							if($totalsessiontime != '' && $totalsessiontime !=0 && $totalsessiontime != NULL) {
								$attended = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/$totalsessiontime)*100, 2) as attend
									FROM {zoom_meeting_participants} zmp
									JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
									JOIN {zoom} zz ON zz.id = zmd.zoomid
									WHERE zz.id = $zid $zoomcourseid AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
								FROM {user_enrolments} ue
								JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
								JOIN {course} c ON e.courseid = c.id AND e.status = 0
								JOIN {role_assignments}  ra ON ra.userid = ue.userid
								JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
								JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
								JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
								WHERE c.visible = 1 $courseconcat)
									GROUP BY zz.id, zmp.userid) as s WHERE s.attend BETWEEN 25 AND 50 ");
							} else {
								$attended = 0;
							}
							$studentcount += $attended;
						}
					}

				$row->{$data->column} = $studentcount;

				} else if($numericpercent == 75){
					$studentcount = 0;
					if(!empty($meetingid)) {
						foreach($meetingid AS $zid) {
							if($totalsessiontime != '' && $totalsessiontime !=0 && $totalsessiontime != NULL) {
								$attended = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/$totalsessiontime)*100, 2) as attend
									FROM {zoom_meeting_participants} zmp
									JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
									JOIN {zoom} zz ON zz.id = zmd.zoomid
									WHERE zz.id = $zid $zoomcourseid AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
								FROM {user_enrolments} ue
								JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
								JOIN {course} c ON e.courseid = c.id AND e.status = 0
								JOIN {role_assignments}  ra ON ra.userid = ue.userid
								JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
								JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
								JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
								WHERE c.visible = 1 $courseconcat)
									GROUP BY zz.id, zmp.userid) as s WHERE s.attend BETWEEN 50 AND 75 ");
							} else {
								$attended = 0;
							}
							$studentcount += $attended;
						}
					}

				$row->{$data->column} = $studentcount;

				}else if($numericpercent == 100){
					$studentcount = 0;
					if(!empty($meetingid)) {
						foreach($meetingid AS $zid) {
							if($totalsessiontime != '' && $totalsessiontime !=0 && $totalsessiontime != NULL) {
								$attended = $DB->get_field_sql("SELECT count(s.attend) FROM (SELECT ROUND((SUM(zmp.leave_time - zmp.join_time)/$totalsessiontime)*100, 2) as attend
									FROM {zoom_meeting_participants} zmp
									JOIN {zoom_meeting_details} zmd ON zmp.detailsid = zmd.id
									JOIN {zoom} zz ON zz.id = zmd.zoomid
									WHERE zz.id = $zid $zoomcourseid AND zz.course = AND zz.start_time <= UNIX_TIMESTAMP() AND zmp.userid IN(SELECT DISTINCT ue.userid
								FROM {user_enrolments} ue
								JOIN {enrol} e ON ue.enrolid = e.id AND ue.status = 0
								JOIN {course} c ON e.courseid = c.id AND e.status = 0
								JOIN {role_assignments}  ra ON ra.userid = ue.userid
								JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
								JOIN {context} ctx ON ctx.instanceid = c.id AND ra.contextid = ctx.id AND ctx.contextlevel = 50
								JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0
								WHERE c.visible = 1 $courseconcat)
									GROUP BY zz.id, zmp.userid) as s WHERE s.attend > 75 ");
							} else {
								$attended = 0;
							}
							$studentcount += $attended;
						}
					}
				$row->{$data->column} = $studentcount;
				}
                break;
            }
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
}
