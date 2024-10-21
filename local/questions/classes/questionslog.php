<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_questions;
defined('MOODLE_INTERNAL') || die;
class questionslog{
	public function save_question_logs($question,$logstatus,$currentquestion){
		global $DB, $USER;
		$questionlog = new \stdClass();
        $questionlog->questionid = $question->id;
        $questionlog->idnumber = $question->idnumber;
        $questionlog->qinfo =  $currentquestion;
        $questionlog->usercreated = $USER->id;
        $questionlog->timecreated = time();
        $questionlog->importstatus = $logstatus;
        $logid = $DB->insert_record('local_questions_import_log', $questionlog);
		}

}
