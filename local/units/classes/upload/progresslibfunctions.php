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

namespace local_units\upload;
use csv_import_reader;
use moodle_url;
use core_text;

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard fields
 * @return array list of fields
 */
class progresslibfunctions {
	/**
     * [uu_validate_user_upload_columns description]
     * @param  csv_import_reader $cir           [description]
     * @param  array             $stdfields     [standarad fields]
     * @param  array             $fields        [fields]
     * @param  moodle_url        $returnurl     [moodle return page url]
     * @return array                            [validated fields in csv uploaded]
     */
	public function local_units_validate_hierarchy_columns(csv_import_reader $cir, $stdfields, $fields, moodle_url $returnurl) {

		$columns = $cir->get_columns();
		// print_object($columns);exit;
		if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error', $returnurl);
        }
        if (count($columns) < 2) {
            $cir->close();
            $cir->cleanup();
            print_error('csvfewcolumns', 'error', $returnurl);
        }

        // Test columns.
        $processed = [];

        foreach ($columns as $key => $unused) {
        	$field = $columns[$key];
        	$lcfield = core_text::strtolower($field);
        	if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
                // Standard fields are only lowercase.
                $newfield = $lcfield;
            } else if (in_array($field, $profilefields)) {
                // Exact field name match - these are case sensitive.
                $newfield = $field;
            } else if (in_array($lcfield, $profilefields)) {
                // Hack: somebody wrote uppercase in csv file, but the system knows only lowercase  field.
                $newfield = $lcfield;
            } else if (preg_match('/^(cohort|user|group|type|role|enrolperiod)\d+$/', $lcfield)) {
                // Special fields for enrolments.
                $newfield = $lcfield;
            } else {
                $newfield = $lcfield;
            }
            $processed[$key] = $newfield;
        }
        return $processed;
	}
}