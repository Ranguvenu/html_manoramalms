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

//namespace local_questions;
use mod_quiz\quiz_settings;

/**
 * Class observer
 *
 * @package    local_questions
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_questions_observer{

    public static function course_module_viewed(\core\event\course_module_viewed $event){
        global $DB,$CFG,$USER;
        if (!WS_SERVER && !AJAX_SCRIPT && $event->component == 'mod_quiz' ) { //Verify if the event is triggered in a service or by AJAX.
        $quiz = $event->get_record_snapshot('quiz', $event->objectid);
      
        $quizobj = quiz_settings::create_for_cmid($quiz->cmid, $USER->id);
            require_once($CFG->libdir.'/gradelib.php');
            // Determine whether a start attempt button should be displayed.
            $viewobj = new stdClass();
            $viewobj->quizhasquestions = $quizobj->has_questions();
            $viewobj->preventmessages = []; 
                 if ( $quiz->testtype == 1 && !empty($viewobj->quizhasquestions) ){
                    $url = new moodle_url('/mod/quiz/startattempt.php',[ 'cmid' => $quiz->cmid, 'sesskey' => $USER->sesskey, 'showintro' => false]);
                   redirect($url);
                 }   
        }

    }

}
