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
 * TODO describe file view
 *
 * @package    local_studymaterial
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$id      = required_param('id',PARAM_INT); // Study Material ID
$url = new moodle_url('/local/studymaterial/view.php', ['id'=>$id]);
$studymaterial = $DB->get_record('local_studymaterial', array('id'=>$id), '*', MUST_EXIST);
$PAGE->set_url($url);
$context =\context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading($studymaterial->name);
echo $OUTPUT->header();
$content = file_rewrite_pluginfile_urls($studymaterial->content, 'pluginfile.php', $context->id, 'local_studymaterial', 'content', 0);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $studymaterial->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");
echo $OUTPUT->footer();
