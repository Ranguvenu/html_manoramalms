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
 * renderer  for 'block_notification'.
 *
 * @package   block_notification
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/notification/lib.php');
/**
 * block_notification_renderer
 */
class block_notification_renderer extends plugin_renderer_base
{
    /**
     * [render_notification description]
     */
    public function render_notification()
    {
        global $DB, $USER, $OUTPUT, $CFG, $PAGE;
        $notifications = $this->get_notifications();
        $count = 0;
        $params = array();
        $day = '';
        $todaycount = 0;
        $yesterdaycount = 0;
        $totalnotifications = count($notifications);

        foreach ($notifications as $notify) {
            if ( $count == 5 ) {
                break;
            }
            if (date('Y-m-d') == date('Y-m-d', $notify->timecreated) && $todaycount == 0) {
                $day = 'Today';
                $todaycount++;
            } else if (date('Y-m-d', strtotime("yesterday")) == date('Y-m-d', $notify->timecreated) && $yesterdaycount == 0) {
                $day = 'Yesterday';
                $yesterdaycount++;
            } else {
                $date1 = strtotime(date('Y-m-d'));
                $date2 = $notify->timecreated;
                $diff = abs($date2 - $date1);
                $day = round($diff / (60 * 60 * 24)) . 'd';
            }
            $params[$count]['type'] = $notify->eventtype;
            $params[$count]['notificationname'] = $notify->subject;
            $params[$count]['message'] = $notify->fullmessage;
            $params[$count]['date'] = date('M jS', $notify->timecreated);
            $params[$count]['weekday'] = date('l', $notify->timecreated);
            $params[$count]['day'] = $day;
            $count++;
        }
        if($totalnotifications > 5){
            $viewmore = TRUE;
        }
        $data = array(
            "viewmore" => $viewmore,
            "notificationdetails" => $params,
        );
        return  $this->render_from_template('block_notification/view',  $data);
    }
    public function get_notifications()
    {
        global $DB, $USER;

        $sql = " SELECT n.* FROM {notifications} AS n 
                 JOIN {message_popup_notifications} AS mpn ON mpn.notificationid  =  n.id
        WHERE 1 ";
      //  if (!is_siteadmin()) {
            $sql .= " AND n.useridto =:useridto ";
            $params = ['useridto' => $USER->id];
        // } else {
        //     $params = [];
        // }
        $sql .= " ORDER BY n.id  DESC ";
        $notifications = $DB->get_records_sql($sql, $params);
        return $notifications;
    }
    public function calendarbutton($courseid)
    {
        $data = [
            'contextid' => (\context_course::instance($courseid))->id,
        ];
        // if ($context = $this->get_default_add_context()) {
        $data['defaulteventcontext'] = 2;
        // }
        return  $this->render_from_template('core_calendar/add_event_button',  $data);
    }
    public function calendar_view($data)
    {
        $data['duecount'] = get_count_dueactivity();

        return  $this->render_from_template('block_notification/calendarview',  $data);
    }
    public function filters()
    {
        $data['year'] = calendar_year_options();
        $data['month'] = calendar_month_options();
        $data['day'] = date('d');
        return  $this->render_from_template('block_notification/filters',  $data);
    }
}
