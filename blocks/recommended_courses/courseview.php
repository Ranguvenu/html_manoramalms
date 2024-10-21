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
 * TODO describe file courseview
 *
 * @package    block_recommended_courses
 * @copyright  2023 Moodle India Information Solutions.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
global $DB, $PAGE, $OUTPUT, $CFG;

require_login();
$url = new moodle_url('/blocks/recommended_courses/courseview.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$show = '';
echo $OUTPUT->header();
require_once($CFG->dirroot . '/blocks/recommended_courses/filters_form.php');

$renderer = $PAGE->get_renderer('block_recommended_courses');

// To get the list of recommended courses for filtering.
$filterparams = $renderer->list_of_recommended_courses(true);

$thisfilters = array('recommended_courses');

// Filters form.
$mform = new filters_form(null, array('filterlist' => $thisfilters, 'filterparams' => $filterparams));
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/blocks/recommended_courses/courseview.php');
}
echo '<a class="btn-link btn-sm d-flex align-items-center filter_btn" href="javascript:void(0);" data-toggle="collapse"
            data-target="#block_recommended_courses-filter_collapse" aria-expanded="false"
            aria-controls="block_recommended_courses-filter_collapse">
                    <span class="filter mr-2">Filters</span>
                <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
          </a>';
$filterparams['submitid'] = 'form#filteringform';
echo  '<div class="mt-2 mb-2 collapse '.$show.'" id="block_recommended_courses-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
                $mform->display();
echo       '</div>
       </div>';
echo $OUTPUT->render_from_template('block_recommended_courses/global_filter', $filterparams);

// To get the list of recommended courses.
echo $renderer->list_of_recommended_courses();
echo $OUTPUT->footer();