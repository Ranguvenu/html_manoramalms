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

namespace local_units\form;
use moodleform;
use csv_import_reader;
use core_text;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/local/units/classes/upload/syncfunctionality.php');

class hrms_async extends moodleform {
	/**
	 * form definition.
	 */
	function definition() {
		$mform = $this->_form;

		$filepickeroptions = array();
        $filepickeroptions['accepted_types'] = 'csv';
        $filepickeroptions['maxfiles'] = 1;
		$mform->addElement('filepicker', 'userfile', get_string('file'), null, $filepickeroptions);
		$mform->addRule('userfile', null, 'required');

		$mform->addElement('hidden', 'delimiter_name');
		$mform->setType('delimiter_name', PARAM_TEXT);
		$mform->setDefault('delimiter_name', 'comma');

		$mform->addElement('hidden', 'encoding');
		$mform->setType('encoding', PARAM_RAW);
		$mform->setDefault('encoding', 'UTF-8');

		$this->add_action_buttons(true, get_string('upload'));
	}
}
