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

namespace theme_horizon\output\mod_quiz;
use html_writer;
/**
 * Class edit_quiz_renderer
 *
 * @package    theme_horizon
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_renderer extends \mod_quiz\output\edit_renderer {
	/**
     * Render the edit page
     *
     * @param \mod_quiz\quiz_settings $quizobj object containing all the quiz settings information.
     * @param structure $structure object containing the structure of the quiz.
     * @param \core_question\local\bank\question_edit_contexts $contexts the relevant question bank contexts.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_page(\mod_quiz\quiz_settings $quizobj, \mod_quiz\structure $structure,
        \core_question\local\bank\question_edit_contexts $contexts, \moodle_url $pageurl, array $pagevars) {
        $output = '';

        // Page title.
        $output .= $this->heading(get_string('questions', 'quiz'));

        // Information at the top.
        $output .= $this->quiz_state_warnings($structure);

        $output .= html_writer::start_div('mod_quiz-edit-top-controls');

        $output .= html_writer::start_div('d-flex justify-content-between flex-wrap mb-1');
        $output .= html_writer::start_div('d-flex flex-column justify-content-around');
        $output .= $this->quiz_information($structure);
        $output .= html_writer::end_tag('div');
        $output .= $this->maximum_grade_input($structure, $pageurl);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('d-flex justify-content-between flex-wrap mb-1');
        $output .= html_writer::start_div('mod_quiz-edit-action-buttons btn-group edit-toolbar', ['role' => 'group']);
        $output .= $this->configure_neet_keem_button($structure, $pageurl);
        $output .= $this->repaginate_button($structure, $pageurl);
        $output .= $this->selectmultiple_button($structure);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('d-flex flex-column justify-content-around');
        $output .= $this->total_marks($quizobj->get_quiz());
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= $this->selectmultiple_controls($structure);
        $output .= html_writer::end_tag('div');

        // Show the questions organised into sections and pages.
        $output .= $this->start_section_list($structure);

        foreach ($structure->get_sections() as $section) {
            $output .= $this->start_section($structure, $section);
            $output .= $this->questions_in_section($structure, $section, $contexts, $pagevars, $pageurl);

            if ($structure->is_last_section($section)) {
                $output .= \html_writer::start_div('last-add-menu');
                $output .= html_writer::tag('span', $this->add_menu_actions($structure, 0,
                        $pageurl, $contexts, $pagevars), ['class' => 'add-menu-outer']);
                $output .= \html_writer::end_div();
            }

            $output .= $this->end_section();
        }

        $output .= $this->end_section_list();

        // Initialise the JavaScript.
        $this->initialise_editing_javascript($structure, $contexts, $pagevars, $pageurl);

        // Include the contents of any other popups required.
        if ($structure->can_be_edited()) {
            $thiscontext = $contexts->lowest();
            $this->page->requires->js_call_amd('mod_quiz/quizquestionbank', 'init', [
                $thiscontext->id
            ]);

            $this->page->requires->js_call_amd('mod_quiz/add_random_question', 'init', [
                $thiscontext->id,
                $pagevars['cat'],
                $pageurl->out_as_local_url(true),
                $pageurl->param('cmid'),
                \core\plugininfo\qbank::is_plugin_enabled(\qbank_managecategories\helper::PLUGINNAME),
            ]);

            // Include the question chooser.
            $output .= $this->question_chooser();
        }

        return $output;
    }
    public function configure_neet_keem_button($structure, $pageurl){
        global $DB;
        $this->page->requires->js_call_amd('theme_horizon/regradequestions', 'init');
        $quizinfo = $structure->get_quiz();
        $customfielddata = $DB->get_records_sql("SELECT cff.id, cff.shortname, cfd.value FROM {customfield_field} cff JOIN {customfield_data} cfd ON cfd.fieldid = cff.id WHERE cfd.instanceid = :cmid ", ['cmid' => $quizinfo->cmid]);
        foreach($customfielddata AS $customdata) {
            $quizinfo->{$customdata->shortname} = $customdata->value;
        }
        if ($quizinfo->nsca) {
            $buttonoptions = [
                'type'  => 'button',
                'name'  => 'regradequestions',
                'id'    => 'regradequestions_quiz',
                'value' => get_string('regradequestions', 'theme_horizon'),
                'class' => 'btn btn-secondary mr-1',
                'data-quizid' => $quizinfo->id,
                'data-header' => get_string('confirm'),
                'data-body' => get_string('regradequestions_confirm', 'theme_horizon'),
            ];
            return html_writer::empty_tag('input', $buttonoptions);
        } else {
            return '';
        }
    }
}
