<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package local_onlineexams
 * @subpackage local_onlineexams
 */
require_once('../../config.php');
require_login();
global $DB, $PAGE, $CFG, $USER, $OUTPUT;
$tab = required_param('tab', PARAM_TEXT);
$formattype = optional_param('formattype', 'card', PARAM_TEXT);
if ($formattype == 'card') {
    $formattype_url = 'table';
    $display_text = get_string('listtype','local_onlineexams');
    $display_icon = get_string('listicon','local_onlineexams');
} else {
    $formattype_url = 'card';
    $display_text = get_string('cardtype','local_onlineexams');
    $display_icon = get_string('cardicon','local_onlineexams');
}

$categorycontext =  (new \local_onlineexams\lib\accesslib())::get_module_context();
$pageurl = new moodle_url('/local/onlineexams/userdashboard.php',array('tab' => $tab));
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($categorycontext);
$heading = get_string($tab.'_onlineexams', 'local_onlineexams');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->navbar->add($heading);
$PAGE->requires->js_call_amd('block_userdashboard/userdashboard', 'makeActive',array('identifier' => 'onlineexams_'.$tab));
$PAGE->requires->js_call_amd('block_userdashboard/userdashboard', 'load',array('tab' => $tab));
$PAGE->requires->js_call_amd('block_userdashboard/userdashboard', 'init');
$renderer = $PAGE->get_renderer('local_onlineexams');
$filterparams = $renderer->get_userdashboard_onlineexams($tab, true,$formattype);
echo $OUTPUT->header();
  $display_url = new moodle_url('/local/onlineexams/userdashboard.php',array('tab' => $tab ,'formattype' => $formattype_url));
  $displaytype_div = '<div class="col-12 d-inline-block">';
$displaytype_div .= '<a class="pull-right btn btn-outline-secondary cardlist_view" id="card_list_view_detailed" data-displaytype="'.$formattype.'">';//href="' . $display_url->out() . '"
  $displaytype_div .= '<span class="'.$display_icon.'"></span>' . $display_text;
  $displaytype_div .= '</a>';
  $displaytype_div .= '</div>';
  echo $displaytype_div;
echo $OUTPUT->render_from_template('local_onlineexams/userdashboard_inner_tab', array());
$content = $renderer->get_userdashboard_onlineexams($tab,false,$formattype);
echo html_writer::div($content, 'userdashboard_content_detailed', array('data-options' => json_encode($filterparams['options']), 'data-dataoptions' => json_encode($filterparams['dataoptions']), 'data-filterdata' => json_encode($filterparams['filterdata'])));
echo $OUTPUT->footer();
