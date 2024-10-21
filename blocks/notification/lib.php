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
 * Callback implementations for Notification
 *
 * @package    block_notification
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG;
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/calendar/lib.php');

function block_notification_calendar_events($year, $month, $day, $courseid, $categoryid)
{
    global $DB, $USER, $CFG;
    // $date = new \DateTime('now', core_date::get_user_timezone_object(99));
    // $type = \core_calendar\type_factory::get_calendar_instance();
    // $tstart = $type->convert_to_timestamp($year, $month, $day);
    // $date->setTimestamp($tstart);
    // $date->modify('+1 day');
    // $date->modify('-1 second');
    // $tend = $date->getTimestamp();
    // $sql = " SELECT ev.*,ev.timestart+ev.timeduration as timeend,case when ev.modulename = 'quiz' then 'MCQ Test'  WHEN ev.modulename = 'zoom' then 'Live Class'  when ev.modulename ='assign' then 'Assignment' WHEN ev.modulename = 'forum' then 'Disscussions'  else 'Defualt' end as typeofevent  FROM {event} AS ev
    //     JOIN {course} AS c ON c.id =  ev.courseid
    //      JOIN {enrol} e ON e.courseid = c.id 
    //     JOIN {user_enrolments} ue ON ue.enrolid = e.id       
    //     WHERE 1 ";
    // if (!is_siteadmin()) {
    //     $sql .= " AND ue.userid =:userid ";
    //     $params = ['userid' => $USER->id];
    // } else {
    //     $params = [];
    // }
    // if ($tstart > 0 && $tend > 0) {
    //     $sql .= " AND ev.timestart BETWEEN :tstart AND :tend ";
    //     $params['tstart'] = $tstart;
    //     $params['tend'] = $tend;
    // }
    // if ($courseid > 1) {
    //     $sql .= " AND ev.courseid =:courseid";
    //     $params['courseid'] = $courseid;
    // }
    // if ($categoryid > 0) {
    //     $sql .= " AND ev.categoryid =:categoryid";
    //     $params['categoryid'] = $categoryid;
    // }
    // $sql .= " ORDER BY ev.id  DESC ";

    // $eventdetails = $DB->get_records_sql($sql, $params);

    $context = \context_user::instance($USER->id);

    $type = \core_calendar\type_factory::get_calendar_instance();

    $time = $type->convert_to_timestamp($year, $month, $day);
    $calendar = \calendar_information::create($time, $courseid, $categoryid);
    list($eventdetails, $template) = calendar_get_view($calendar, 'day');
    $events = [];
    $count = 0;
    foreach ($eventdetails->events as $event) {
        if ($event->eventtype == 'close') {
            continue;
        } else {
                
            $isforum = 0;
            $isassign = 0;
            $iszoom = 0;
            $isquiz = 0;
            $isfeedback = 0;
            $isscorm = 0;
            $islesson = 0;
            $isworkshop = 0;
            $event->timeend = 0;
            switch ($event->modulename) {
                case "forum":
                    $type = 'Announcement';
                    $isforum = 1;
                    break;
                case "assign":
                    $isassign = 1;
                    $type = 'Subjective Test';
                    break;
                case "zoom":
                    $iszoom = 1;
                    $type = 'Live Class';
                    break;
                case "quiz":
                    $isquiz = 1;
                    $type = 'MCQ Test';
                    break;
                case "feedback":
                    $isfeedback = 1;
                    $type = 'Feedback';
                    break; 
                case "lesson":
                    $islesson = 1;
                    $type = 'Lesson';
                    break; 
                case "scorm":
                    $isscorm = 1;
                    $type = 'Scorm';
                    break;
                case "workshop":
                    $isworkshop = 1;
                    $type = 'Workshop';
                    break;     
                default:
                    $isforum = 0;
                    $isassign = 0;
                    $iszoom = 0;
                    $isquiz = 0;
                    $isfeedback = 0;
                    $isscorm = 0;
                    $islesson = 0;
                    $isworkshop = 0;
                    $type =  "Default";
            }
            $msql = "SELECT cm.id FROM {event} e 
            JOIN {modules} m ON m.name = e.modulename
            JOIN {course_modules} cm ON cm.module = m.id AND e.instance = cm.instance 
            JOIN {course} c ON c.id = cm.course WHERE e.id = :eventid ";
            $moduleid = $DB->get_field_sql($msql,['eventid'=>$event->id]);
            $module_instance = $DB->get_field('course_modules', 'instance', ['id' => $moduleid]);
            if ($isquiz) {
                $module = $DB->get_record('quiz',['id' => $module_instance]);

                $qsql = 'SELECT count(q.id) FROM {quiz} q
                JOIN {quiz_slots} qs ON qs.quizid = q.id
                WHERE q.id = :quizid ';
                $qcount = $DB->count_records_sql($qsql, ['quizid' => $module_instance]);
                $qmarks = $DB->get_field('quiz', 'grade', ['id' => $module_instance], IGNORE_MISSING);
                $duration = 0;
                if ($module->timelimit != 0) {
                    $duration = $module->timelimit;
                    $event->timestart = $module->timeopen;
                    $event->timeend = $module->timeopen + $module->timelimit;
                } else {
                    // $duration = $module->timeclose - $module->timeopen;
                    $event->timestart = $module->timeopen;
                    $event->timeend = $module->timeclose;
                }
                if ($duration > 0) {
                    // $events[$count]['duration'] = gmdate("H:i:s", $duration);
                    $events[$count]['duration'] = format_time($duration);
                } else {
                    $events[$count]['duration'] = 'Open';
                }
            } else {
                $qcount = 0;
                $qmarks = 0;
            }
            if ($iszoom) {
                $zoomid = $DB->get_field('course_modules', 'instance', ['id' => $event->instance], IGNORE_MISSING);
                $zoominstance = $DB->get_record('zoom', ['id' => $zoomid]);
                $lessonplanurl = zoom_file_lessonplan_path($zoominstance->lessonplan);
                $classnotesurl = zoom_file_classnotes_path($zoominstance->classnotes);
                $daystarttime = strtotime('midnight', $zoominstance->start_time);
                $event->timeend = $event->timestart + $event->timeduration;
                if ($event->timestart + $event->timeduration < time()) {
                    $recordingsurl = $CFG->wwwroot.'/mod/zoom/recordings.php?id='.$event->instance;
                } else if ($event->timestart + $event->timeduration > time() && $daystarttime < $zoominstance->start_time && $event->timestart-600 <= time()) {
                    $linkurl = $CFG->wwwroot.'/mod/zoom/loadmeeting.php?id='.$event->instance;
                } else {
                    $viewpageurl = $CFG->wwwroot.'/mod/zoom/view.php?id='.$event->instance;
                }
            } else {
                $lessonplanurl = '';
                $classnotesurl = '';
                $linkurl = '';
                $viewpageurl = '';
                $recordingsurl = '';
            }
            if($islesson){
                $duration = 0;
                $module = $DB->get_record('lesson',['id' => $module_instance]);
                if ($module->timelimit != 0) {
                    $duration = $module->timelimit;
                }
                // else {
                //     $duration = $module->duedate - $module->allowsubmissionsfromdate;
                // }
                if ($duration > 0) {
                    // $events[$count]['duration'] = gmdate("H:i:s", $duration);
                    $events[$count]['duration'] = format_time($duration);
                } else {
                    $events[$count]['duration'] = 'Open';
                }
            }
            if ($isassign) {
                $module = $DB->get_record('assign', ['id' => $module_instance]);
                // if ($module->allowsubmissionsfromdate > 0) {
                    $event->timestart = $module->allowsubmissionsfromdate;
                // }
                // if ($module->duedate > 0) {
                    $event->timeend = $module->duedate;
                // }
                $qmarks = $module->grade;
                $duration = $module->duedate - $module->allowsubmissionsfromdate;
                if ($duration > 0) {
                    // $events[$count]['duration'] = gmdate("H:i:s", $duration);
                    $events[$count]['duration'] = format_time($duration);
                } else {
                    $events[$count]['duration'] = 'Open';
                }
            }
            $url = new \moodle_url(sprintf('/mod/%s/view.php', $event->modulename), ['id' => $moduleid]);

            $events[$count]['id'] = $event->id;
            $events[$count]['typeofevent'] = $type;
            $eventname = $event->name;
            if (str_contains($event->name, 'opens')) {
                $eventname = rtrim($event->name, 'opens');
            }
            $events[$count]['name'] = $eventname;
            $events[$count]['description'] = $event->description;
            $events[$count]['descriptionformat'] = $event->format;
            $events[$count]['location'] = $event->location;
            $events[$count]['categoryid'] = $event->categoryid;
            $events[$count]['groupid'] = $event->groupid;
            $events[$count]['userid'] = $event->userid;
            $events[$count]['repeatid'] = $event->repeatid;
            $events[$count]['eventcount'] = $event->eventcount;
            $events[$count]['component'] = $event->component;
            $events[$count]['modulename'] = $event->modulename;
            // $events[$count]['activityname'] = $event->activityname;
            // $events[$count]['activitystr'] = $event->activitystr;
            $events[$count]['instance'] = $event->instance;
            $events[$count]['eventtype'] = $event->eventtype;
            $events[$count]['timestart'] = $event->timestart;
            //$events[$count]['timeend'] = $event->timestart+$event->timeduration;
            $events[$count]['timeend'] = $event->timeend;
            $events[$count]['timeduration'] = $event->timeduration;
            $events[$count]['timesort'] = $event->timesort;
            // $events[$count]['timeusermidnight'] = $event->timeusermidnight;
            $events[$count]['visible'] = $event->visible;
            $events[$count]['timemodified'] = $event->timemodified;
            $events[$count]['isforum'] = $isforum;
            $events[$count]['isassign'] = $isassign;
            $events[$count]['iszoom'] = $iszoom;
            $events[$count]['isquiz'] = $isquiz;
            $events[$count]['lessonplanurl'] = $lessonplanurl;
            $events[$count]['classnotesurl'] = $classnotesurl;
            $events[$count]['linkurl'] = $linkurl;
            $events[$count]['recordingsurl'] = $recordingsurl;
            $events[$count]['viewpageurl'] = $viewpageurl;
            $events[$count]['qcount'] = $qcount;
            $events[$count]['qmarks'] = (int) $qmarks;
           // $events[$count]['duration'] =  $duration;
            $events[$count]['url'] = $url->out(false);
            // $events[$count]['overdue'] = $event->overdue;
            $count++;
        }
    }
    $data = array(
        "events" => $events,
    );
    return $data;
}
function block_notification_duration_format($duration){

    if(!empty($duration) && $duration > 0){
        $duration = $duration/60;
        if($duration >= 60 ){
            $hours = floor($duration / 60);
            $minutes = ($duration % 60);
            $hformat = $hours > 1 ? $hformat = '%01shrs': $hformat = '%01shr';
            if($minutes == NULL){
                $mformat = '';
            }else{
                $mformat = $minutes > 1 ? $mformat = '%01smins': $mformat = '%01smin';
            }
            $format = $hformat . ' ' . $mformat;
            $coursecompletiondays = sprintf($format, $hours, $minutes);
        }else{
            $minutes = $duration;
            $coursecompletiondays = $duration > 1 ? $duration.'mins' : $duration.'min';
        }
    }else{
        $coursecompletiondays = 'N/A';
    }

    return $coursecompletiondays;
}
function calendar_year_options(){
    global $DB;
    $yearsarr = [];
    $year = date('Y');
    for($i=2023;$i<=2075;$i++){
        $yearsarr['name'] = $i;
        $yearsarr['value'] = $i;
        $yearsarr['selected'] = ($year == $i) ? true : false;
        $years[]=$yearsarr;
    }
    $data['yearoptions'] = $years;
    return $data;
}
function calendar_month_options(){
    global $DB;
    $months = [1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December"];
    $monthsarr= [];    
    $month = date('m');
    foreach($months as $key => $value){
        $monthsarr['name'] = $value;
        $monthsarr['value'] = $key;
        $monthsarr['selected'] = ($month == $key) ? true : false;
        $calmonths[]=$monthsarr;
    }
    $data['monthoptions'] = $calmonths;
    return $data;
}
    
function block_notification_output_fragment_due_activities_list($args){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $value = array();
    for ($i = 7; $i >= 1; $i--) {
 
        $ctime = strtotime(date('d-m-Y', time())) - $i * DAYSECS;

        $calendar = \calendar_information::create($ctime, 0, null);
        list($data, $template) = calendar_get_view($calendar, 'day');
    
        $value = array_merge($data->events, $value);
    }

    $edata = [];
    $newArray = [];
    $count = 0;
    foreach ($value as $event) {

        if($event->eventtype == 'open'){
            continue;
        }
        
        if ($event->modulename == 'quiz') {
            $quiz = get_attempted_quiz($event->instance,$event->course->id);
            if($quiz == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);
            }
        }
        else if ($event->modulename == 'assign') {
            $assign = get_submitted_assignment($event->instance, $event->course->id);  
            if($assign == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);  
            }
        }
        else if ($event->modulename == 'zoom') {
            $zoom = get_attended_zoom($event->instance,$event->course->id);  
            if($zoom == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);
            }

        }
        $count ++;
    }
    foreach ($edata as $val) {
        foreach ($val as $aa) {
            $newArray[] = $aa;
        }
    }
    $dueactivity = array(
        "dueactivity" => $newArray,
    );
    
    $output = $OUTPUT->render_from_template(
        'block_notification/dueactivityview', $dueactivity
    );

    return  $output; 
}


function get_attempted_quiz($e_moduleid, $courseid){
     
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    
    $sql = 'SELECT q.* FROM {quiz} q 
            WHERE q.course = :courseid 
            AND q.id IN (SELECT quiz FROM {quiz_attempts} qa WHERE qa.userid = :userid)';

    $quiz = $DB->get_records_sql($sql, ['quizid' => $e_moduleid, 'userid' => $USER->id,'courseid' => $courseid]);

    if ($quiz) { 
        return true;
    }else{
        return false;
    }
   
}

function get_submitted_assignment($e_moduleid,$courseid){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $sql = 'SELECT a.* FROM {assign} a 
            WHERE a.course = :courseid 
            AND a.id IN (SELECT assignment FROM {assign_submission} asb WHERE asb.userid = :userid)';

    $assign = $DB->get_records_sql($sql, ['assignment_id' => $e_moduleid, 'userid' => $USER->id,'courseid' => $courseid]);
   
    if ($assign) { 
        return true;
    }else{
        return false;
    }

}

function get_attended_zoom($e_moduleid,$courseid){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;
    $sql = 'SELECT z.* FROM {zoom} z
            JOIN {zoom_meeting_details} zd ON z.id = zd.zoomid
            WHERE z.course = :courseid 
            AND zd.id IN (SELECT detailsid FROM {zoom_meeting_participants} zp WHERE zp.userid = :userid)';

    $zoom = $DB->get_records_sql($sql, ['zoom_id' => $e_moduleid, 'userid' => $USER->id,'courseid' => $courseid]);
   
    if ($zoom) { 
        return true;
    }else{
        return false;
    }
}

function event_data($event, $count){
     
    $events = [];
    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $msql = "SELECT cm.id FROM {event} e 
                        JOIN {modules} m ON m.name = e.modulename
                        JOIN {course_modules} cm ON cm.module = m.id AND e.instance = cm.instance 
                        JOIN {course} c ON c.id = cm.course WHERE e.id = :eventid ";
    $moduleid = $DB->get_field_sql($msql,['eventid'=>$event->id]);
    $url = new \moodle_url(sprintf('/mod/%s/view.php', $event->modulename), ['id' => $moduleid]);

    switch ($event->modulename){
        case 'quiz':
            $type = get_string('mcqtest', 'block_notification');
            break;
        case 'assign':
            $type = get_string('subjectivetest', 'block_notification');
            break;
        case 'zoom':
            $type = get_string('liveclass', 'block_notification');
            break;
        default:
            $type = '';
    }
    $events[$count]['id'] = $event->id;
    $events[$count]['typeofevent'] = $type;
    $events[$count]['name'] = $event->name;
    $events[$count]['description'] = $event->description;
    $events[$count]['descriptionformat'] = $event->format;
    $events[$count]['location'] = $event->location;
    $events[$count]['categoryid'] = $event->categoryid;
    $events[$count]['groupid'] = $event->groupid;
    $events[$count]['userid'] = $event->userid;
    $events[$count]['repeatid'] = $event->repeatid;
    $events[$count]['eventcount'] = $event->eventcount;
    $events[$count]['component'] = $event->component;
    $events[$count]['modulename'] = $event->modulename;
    $events[$count]['instance'] = $event->instance;
    $events[$count]['eventtype'] = $event->eventtype;
    $events[$count]['timestart'] = $event->timestart;
    $events[$count]['timeend'] = $event->timestart+$event->timeduration;
    $events[$count]['timeduration'] = $event->timeduration;
    $events[$count]['timesort'] = $event->timesort;
    $events[$count]['visible'] = $event->visible;
    $events[$count]['timemodified'] = $event->timemodified;
    $events[$count]['url'] = $url->out(false);
    $count++;

    return $events;
}

function get_count_dueactivity(){

    global $DB, $USER, $OUTPUT, $CFG, $PAGE;

    $value = array();
    for ($i = 7; $i >= 1; $i--) {
 
        $ctime = strtotime(date('d-m-Y', time())) - $i * DAYSECS;

        $calendar = \calendar_information::create($ctime, 0, null);
        list($data, $template) = calendar_get_view($calendar, 'day');
    
        $value = array_merge($data->events, $value);
    }

    $edata = [];
    $newArray = [];
    $count = 0;
    foreach ($value as $event) {

        if($event->eventtype == 'open'){
            continue;
        }
        
        if ($event->modulename == 'quiz') {
            $quiz = get_attempted_quiz($event->instance,$event->course->id);
            if($quiz == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);
               
            }      
        }
        else if ($event->modulename == 'assign') {
            $assign = get_submitted_assignment($event->instance, $event->course->id);  
            if($assign == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);  
            }        
        }
        else if ($event->modulename == 'zoom') {
            $zoom = get_attended_zoom($event->instance,$event->course->id);  
            if($zoom == 'true'){
                continue;
            } 
            else {
                $edata[] = event_data($event, $count);
            }
        }
        $count ++;
    }
    foreach ($edata as $val) {
        foreach ($val as $aa) {
            $newArray[] = $aa;
        }
    }
   
    $total = count($newArray);

    return $total;
}
