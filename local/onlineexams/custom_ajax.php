<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Process ajax requests
 *
 * @copyright Sreenivas
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local_onlineexams
 */

if (!defined('AJAX_SCRIPT')) {
	define('AJAX_SCRIPT', true);
}

require(__DIR__ . '/../../config.php');
require_once('lib.php');
global $CFG, $DB, $USER, $PAGE;
$id = required_param('id', PARAM_INT);
$page = required_param('page', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);


$PAGE->set_context(context_system::instance());
require_login();
$output = $PAGE->get_renderer('local_onlineexams');
$params = array('courseid' => $id, 'employeerole' => 'employee');
switch ($page) {
	case 1:

		// $sql = "SELECT ou.*,u.id as userid,u.firstname,u.lastname,u.email, o.id as onlinetestid, o.quizid
        //                from {local_onlinetest_users} ou
        //                JOIN {course} o ON ou.onlinetestid = o.id
        //                JOIN {user} u ON ou.userid=u.id AND u.deleted = 0 AND u.suspended = 0
        //                where ou.onlinetestid=?  ";

		// $assigned_users = $DB->get_records_sql($sql, array($id));
		
		$enrolledusersssql = " SELECT u.id as userid,c.*,u.firstname,u.lastname,u.email,q.id as quizid
		FROM {course} c
		JOIN {quiz} q ON q.course = c.id
		JOIN {context} AS cot ON cot.instanceid = c.id AND cot.contextlevel = 50
		JOIN {role_assignments} as ra ON ra.contextid = cot.id
		JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1
						AND u.deleted = 0 AND u.suspended = 0
		WHERE c.id = :courseid  AND c.open_module= 'online_exams' AND c.open_coursetype = 1 ";

		$assigned_users = $DB->get_records_sql($enrolledusersssql, $params);
		$out = '';
		$data = array();
		if (!empty($assigned_users)) {
			foreach ($assigned_users as $assigned_user) {
				$row = array();
				$user = $DB->get_record_sql("SELECT * FROM {user} WHERE id=$assigned_user->userid");

				$gradeitem = $DB->get_record('grade_items', array('iteminstance' => $assigned_user->quizid, 'itemmodule' => 'quiz', 'courseid' => $id));
				$gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass, 2) : '-';
				if ($gradeitem->id)
					$usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = $gradeitem->id AND userid = $assigned_user->userid");
				if ($usergrade) {
					$mygrade = round($usergrade->finalgrade, 2);
					if ($usergrade->finalgrade >= $gradepass) {
						$status = get_string('completed', 'local_onlineexams');
					} else {
						$status = get_string('incompleted', 'local_onlineexams');
					}
				} else {
					$mygrade = '-';
					$status = get_string('pending', 'local_onlineexams');
				}
				if ($user) {
					$row[] = $user->firstname . ' ' . $user->lastname;
					$row[] = $user->email;
					$row[] = ($user->idnumber) ? $user->idnumber : '-';
					$row[] = date('d-m-Y', $assigned_user->timecreated);
					$row[] = $mygrade;
					$row[] = $status;
				}
				$data[] = $row;
			}
		}
		$table = new html_table();
		$head = array('<b>' . get_string('username', 'local_onlineexams') . '</b>', '<b>' . get_string('email') . '</b>', '<b>' . get_string('employeeid', 'local_users') . '</b>', '<b>' . get_string('enrolledon', 'local_onlineexams') . '</b>', '<b>' . get_string('grade', 'local_onlineexams') . '</b>', '<b>' . get_string('status', 'local_onlineexams') . '</b>');
		$table->head = $head;
		$table->width = '100%';
		$table->align = array('left', 'left', 'left', 'left', 'center', 'left');
		$table->id = 'onlinetest_assigned_users' . $id . '';
		$table->attr['class'] = 'onlinetest_assigned_users';
		if ($data)
			$table->data = $data;
		else
			$table->data = 'No users';
		$out .= html_writer::table($table);
		$out .= html_writer::script('$(document).ready(function() {
			   $("#onlinetest_assigned_users' . $id . '").dataTable({
			   	language: {
                              emptyTable: "No Records Found",
                               paginate: {
                                            previous: "<",
                                            "next": ">"
                                        }
                         },
			   });
		  });');
		echo $out;
		break;
	case 2:

		$completedusersssql = " SELECT c.*,u.id as userid,u.firstname,u.lastname,u.email,q.id as quizid
		FROM {course} c
		JOIN {quiz} q ON q.course = c.id
		JOIN {context} AS cot ON cot.instanceid = c.id AND cot.contextlevel = 50
		JOIN {role_assignments} as ra ON ra.contextid = cot.id
		JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1
		AND u.deleted = 0 AND u.suspended = 0
		JOIN {course_modules} as cm ON cm.course = c.id 
		JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND u.id = cmc.userid
		WHERE c.id = :courseid  AND cmc.completionstate > 0 $costcenterpathconcatsql AND c.open_module= 'online_exams' AND c.open_coursetype = 1 ";

		$completed_users = $DB->get_records_sql($completedusersssql, $params);
		$out = '';
		$data = array();
		if (!empty($completed_users)) {
			foreach ($completed_users as $assigned_user) {
				$row = array();
				$user = $DB->get_record_sql("SELECT * FROM {user} WHERE id=$assigned_user->userid");
				$gradeitem = $DB->get_record('grade_items', array('iteminstance' => $assigned_user->quizid, 'itemmodule' => 'quiz', 'courseid' => $id));
				if ($gradeitem->id)
					$usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = $gradeitem->id AND userid = $assigned_user->userid");
				if ($usergrade) {
					$mygrade = round($usergrade->finalgrade, 2);
				} else {
					$mygrade = '-';
				}
				if ($user) {
					$row[] = $user->firstname . ' ' . $user->lastname;
					$row[] = $user->email;
					$row[] = $user->idnumber;
					$row[] = $mygrade;
					$row[] = date('d-m-Y', $assigned_user->timecreated);
					$row[] = date('d-m-Y', $assigned_user->timemodified);
				}
				$data[] = $row;
			}
		}
		$table = new html_table();
		$head = array('<b>' . get_string('name', 'local_onlineexams') . '</b>', '<b>' . get_string('email') . '</b>', '<b>' . get_string('employeeid', 'local_users') . '</b>', '<b>' . get_string('grade', 'local_onlineexams') . '</b>', '<b>' . get_string('enrolledon', 'local_onlineexams') . '</b>', '<b>' . get_string('completedon', 'local_onlineexams') . '</b>');
		$table->head = $head;
		$table->align = array('left', 'left', 'left', 'center', 'left', 'left');
		if ($data)
			$table->data = $data;
		else
			$table->data = 'No users';
		$table->width = '100%';
		$table->id = 'completed_users_view' . $id . '';
		$table->attr['class'] = 'completed_users_view';
		$out .= html_writer::table($table);
		$out .= html_writer::script('$(document).ready(function() {
			 $("#completed_users_view' . $id . '").dataTable({
			 	bInfo : false,
                lengthMenu: [5, 10, 25, 50, -1],
                    language: {
                              emptyTable: "No Records Found",
                                paginate: {
                                            previous: "<",
                                            next: ">"
                                        }
                         },
			 });
		});');
		echo $out;
		break;
}
