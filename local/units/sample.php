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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Moodle India Information Solutions
 * @package local_units
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$format = optional_param('format', 'csv', PARAM_ALPHA);
$systemcontext = context_system::instance();

if ($format) {
    $fields = array(
        'goal' => 'goal',
        'board' => 'board',
        'class' => 'class',
        'subject' => 'subject',
        'unit' => 'unit',
        'chapter' => 'chapter',
        'topic' => 'topic',
    );
    switch ($format) {
        case 'csv' : sample_download_csv($fields);
    }
    die;
}

function sample_download_csv($fields) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('upload'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
	$filedata = [];
	$csvexport->add_data($filedata);
    $csvexport->download_file();
    die;
}