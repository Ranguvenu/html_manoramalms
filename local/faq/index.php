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
 * @package F-academy
 * @subpackage local_faq
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG;
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

// $output = $PAGE->get_renderer('local_faq');

$PAGE->set_pagelayout('standard');

$PAGE->set_url('/local/faq/index.php');

require_capability('local/faq:view', $systemcontext);
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('helpandsupport', 'local_faq'));
$PAGE->set_heading(get_string('helpandsupport', 'local_faq'));
$PAGE->navbar->add(get_string('helpandsupport', 'local_faq'));
$PAGE->requires->js_call_amd('local_faq/faqform', 'init');

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_faq');

$cancreatecat = false;
if (is_siteadmin() || has_capability('local/faq:category_create', $systemcontext)) {
    $cancreatecat = true;
}

$cancreatequery = false;
if (is_siteadmin() || has_capability('local/faq:query_create', $systemcontext)) {
    $cancreatequery = true;
}

echo $OUTPUT->render_from_template('local_faq/form', ['cancreatecat' => $cancreatecat, 'cancreatequery' => $cancreatequery]);
echo $renderer->get_faq_view();


echo $OUTPUT->footer();
