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

namespace block_notification\form;

/**
 * Class filters
 *
 * @package    block_notification
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->libdir/formslib.php");
class filters_form extends \moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $USER;
        $mform = $this->_form; // Don't forget the underscore!

        //  $squadoptions = $DB->get_records_sql_menu("SELECT  ls.id , ls.name  FROM {local_squads} AS ls WHERE 1
        //   {$dependencysql} ", $params);
        $years = [2023 => '2023', 2024 => '2024'];
        $mform->addElement('select', 'year', get_string('year', 'block_notification'), $years, array('multiple' => false, 'placeholder' =>  get_string('year', 'block_notification')));
        $mform->setType('year', PARAM_RAW);
        $months = [1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December"];
        $mform->addElement('select', 'month', get_string('month', 'block_notification'), $months, array('multiple' => false, 'placeholder' =>  get_string('month', 'block_notification')));
        $mform->setType('month', PARAM_RAW);
        $mform->addElement('hidden', 'day', $this->_customdata['day']);
        $mform->setType('day', PARAM_RAW);
    }


    function validation($data, $files)
    {
    }
}
