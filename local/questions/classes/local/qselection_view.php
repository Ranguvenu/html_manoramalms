<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    local_questions
 * @copyright  2023 Moodle India Private Limited
 * @author     Vinod Kumar  <vinod.pandella@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_questions\local;

// use local_questions\local\view as qsview;
use mod_quiz\question\bank\custom_view as qsview;
use question_bank;
use qbank_previewquestion\question_preview_options;
use qbank_editquestion\editquestion_helper;
use question_engine;
use context_user;
use context_system;
use moodle_url;
use core_question\bank\search\category_condition;

class qselection_view extends qsview {
   const DEFAULT_PAGE_SIZE = 10;
    public function __construct($contexts, $pageurl, $course, $cm = null,$filterparams){
         parent::__construct($contexts, $pageurl, $course, $cm, $filterparams);
        $this->contexts = $contexts;
        $this->baseurl = $pageurl;
        $this->course = $course;
        $this->cm = $cm;
        $this->filterparams = $filterparams;

        // Create the url of the new question page to forward to.
        $this->returnurl = $pageurl->out_as_local_url(false);
        //$this->editquestionurl = new moodle_url('/local/questions/editquestion.php', ['returnurl' => $this->returnurl]);
         $this->editquestionurl = new moodle_url('/question/bank/editquestion/question.php', ['returnurl' => $this->returnurl]);
        if ($this->cm !== null) {
            $this->editquestionurl->param('cmid', $this->cm->id);
        } else {
            $this->editquestionurl->param('courseid', $this->course->id);
        }

        $this->lastchangedid = optional_param('lastchanged', 0, PARAM_INT);

        // Possibly the heading part can be removed.
        $this->init_columns($this->wanted_columns(), $this->heading_column());
        $this->init_sort();
        $this->init_search_conditions();
        $this->init_bulk_actions();
        //parent::__construct($contexts, $pageurl, $course, $cm);
    }
    public function wanted_filters($cat, $tagids, $showhidden, $recurse, $editcontexts, $showquestiontext): void {
        global $CFG, $DB;
        list(, $contextid) = explode(',', $cat);
        $catcontext = \context::instance_by_id($contextid);
        $thiscontext = $this->get_most_specific_context();
        // Category selection form.
        $this->display_question_bank_header();

        // Display tag filter if usetags setting is enabled/enablefilters is true.
        if ($this->enablefilters) {
            if (is_array($this->customfilterobjects)) {
                foreach ($this->customfilterobjects as $filterobjects) {
                    $this->searchconditions[] = $filterobjects;
                }
            } else {
                if ($CFG->usetags) {
                    array_unshift($this->searchconditions,
                        new \core_question\bank\search\tag_condition([$catcontext, $thiscontext], $tagids));
                }

                array_unshift($this->searchconditions, new \core_question\bank\search\hidden_condition(!$showhidden));
                // array_unshift($this->searchconditions, new \mod_quiz\question\bank\filter\custom_category_condition(
                //     $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
                array_unshift($this->searchconditions, new \local_questions\bank\custom_category_condition(
                    $cat, $recurse, $editcontexts, $this->baseurl, $this->course, $this->filterparams));
            }
        }
        $this->display_options_form($showquestiontext);
    }

    protected function build_query(): void {
        global $USER,$DB;
        // Get the required tables and fields.
        $systemcontext = context_system::instance();
        $joins = [];
        $fields = ['qv.status', 'qc.id as categoryid', 'qv.version', 'qv.id as versionid', 'qbe.id as questionbankentryid'];
        if (!empty($this->requiredcolumns)) {
            foreach ($this->requiredcolumns as $column) {
                $extrajoins = $column->get_extra_joins();
                foreach ($extrajoins as $prefix => $join) {
                    if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                        throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                    }
                    $joins[$prefix] = $join;
                }

                $fields = array_merge($fields, $column->get_required_fields());
            }
        }

        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $tests = ['q.parent = 0', $latestversion];
        $this->sqlparams = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }

        $joins['um'] ="JOIN {user} um ON um.id = q.modifiedby";
     
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        if(!is_siteadmin() && has_capability('local/questions:qcreater',$systemcontext)){
        
            $sql.= " AND q.createdby = $USER->id";
            
         }
            $sql.= " LEFT JOIN {local_qb_questionreview} lqqr ON lqqr.questionid = q.id ";
            $sql.= " LEFT JOIN {question_versions} quv ON quv.questionid = q.id ";
            
        if (!empty($this->filterparams)) {
           $sql.= " JOIN {local_questions_courses} lqc ON lqc.questionid = q.id  WHERE 1=1 ";
        }

       $sql .= " AND lqqr.qstatus = 'publish'";

        if (!empty($this->filterparams['questionid'])) {

            $questionids = explode(',', $this->filterparams['questionid']);
        
            if(!empty($questionids)){
                $qidquery = array();
                foreach ($questionids as $qids) {
                    $qids= (int)$qids;
                    $qidquery[] = " CONCAT(',',quv.questionbankentryid,',') LIKE CONCAT('%,',$qids,',%') ";
                }
                $qidparamas =implode('OR',$qidquery);
                $sql .= '  AND ('.$qidparamas.') ';
            }
         }
         if (!empty($this->filterparams['goal'])) {
            $goalid = (int) $this->filterparams['goal'];
            $sql.= " AND  lqc.goalid  = $goalid ";
        }
        if (!empty($this->filterparams['board'])) {
            $boardid = (int) $this->filterparams['board'];
            $sql.= " AND  lqc.boardid  = $boardid ";
        }
        if (!empty($this->filterparams['class'])) {
            $classid = (int) $this->filterparams['class'];
            $sql.= " AND  lqc.classid  = $classid ";
        }
        if (!empty($this->filterparams['course'])) {
            $courseid = (int) $this->filterparams['course'];
            $sql.= " AND  lqc.courseid  = $courseid ";
        }

         if (!empty($this->filterparams['unit'])) {
            $unit = (int) $this->filterparams['unit'];
            $sql.= " AND  lqc.unitid  = $unit ";
        }
         if (!empty($this->filterparams['chapter'])) {
            $chapterid = (int) $this->filterparams['chapter'];
            $sql.= " AND  lqc.chapterid  = $chapterid ";
        }
        if (!empty($this->filterparams['coursetopic'])) {
            $coursetopic = (int) $this->filterparams['coursetopic'];
            $sql.= " AND  lqc.topicid  = $coursetopic ";
        }
        if (!empty($this->filterparams['concept'])) {
            $conceptid = (int) $this->filterparams['concept'];
            $sql.= " AND  lqc.conceptid  = $conceptid ";
        }

        if (!empty($this->filterparams['source'])) {
            $sourceid = (int) $this->filterparams['source'];
            $sql.= " AND  lqc.source  = $sourceid ";
        }
        if (!empty($this->filterparams['difficulty'])) {
            $difficultyid = (int) $this->filterparams['difficulty'];
            $sql.= " AND  lqc.difficulty_level  = $difficultyid ";
        }
        if (!empty($this->filterparams['cognitive'])) {
            $cognitiveid = (int) $this->filterparams['cognitive'];
            $sql.= " AND  lqc.cognitive_level  = $cognitiveid ";
        }
        if(!empty($this->filterparams['uploadfrom'])){
            $sql .= " AND lqc.timecreated >= ".$this->filterparams['uploadfrom']." ";
        }
        if(!empty($this->filterparams['uploadto'] )){
            $sql .=" AND lqc.timecreated <= ".$this->filterparams['uploadto']." ";
        }
        $condition = (!empty($this->filterparams)) ? ' AND ' : ' WHERE ';
        $sql .= $condition . implode(' AND ', $tests);
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
       
    }
    /**
     * Renders the html question bank (same as display, but returns the result).
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @param array $pagevars
     * @param string $tabname
     * @return string HTML code for the form
     */
      public function render($pagevars, $tabname): string {
        ob_start();
        
         $this->display($pagevars, $tabname);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    public function display($pagevars, $tabname): void {
        $page = $pagevars['qpage'];
        $perpage = $pagevars['qperpage'];
        $cat = $pagevars['cat'];
        $recurse = $pagevars['recurse'];
        $showhidden = $pagevars['showhidden'];
        $showquestiontext = $pagevars['qbshowtext'];

        $tagids = [];
        if (!empty($pagevars['qtagids'])) {
            $tagids = $pagevars['qtagids'];
        }

        echo \html_writer::start_div('questionbankwindow boxwidthwide boxaligncenter');

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);

        // Show the filters and search options.
        $this->wanted_filters($cat, $tagids, $showhidden, $recurse, $editcontexts, $showquestiontext);
   

        // Continues with list of questions.
        $this->display_question_list($this->baseurl, $cat, null, $page, $perpage,
                                        $this->contexts->having_cap('moodle/question:add'),$pagevars);
        echo \html_writer::end_div();

    }

    /**
     * Prints the table of questions in a category with interactions
     *
     * @param \moodle_url $pageurl     The URL to reload this page.
     * @param string     $categoryandcontext 'categoryID,contextID'.
     * @param int        $recurse     Whether to include subcategories.
     * @param int        $page        The number of the page to be displayed
     * @param int|null   $perpage     Number of questions to show per page
     * @param array      $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_question_list($pageurl, $categoryandcontext, $recurse = 1, $page = 0,
                $perpage = null, $addcontexts = [],$pagevars = []): void {

        global $OUTPUT;
        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);

        $category = $this->get_current_category($categoryandcontext);
        $perpage = $perpage ?? $this->pagesize;

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }
        $questionsrs = $this->load_page_questions($page, $perpage);
        $questions = [];
        foreach ($questionsrs as $question) {
            if (!empty($question->id)) {
                $questions[$question->id] = $question;
            }
        }
        $questionsrs->close();

        // Bulk load any required statistics.
        $this->load_required_statistics($questions);

        // Bulk load any extra data that any column requires.
        foreach ($this->requiredcolumns as $name => $column) {
            $column->load_additional_data($questions);
        }
        unset($pagevars['qtagids']);
        $pageurlparams = array_merge($pageurl->params(),$pagevars);
     
        //$pageingurl = new \moodle_url($pageurl, $pageurl->params());
        $pageingurl = new \moodle_url($pageurl, $pageurlparams);
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';

        $this->display_top_pagnation($OUTPUT->render($pagingbar));

        // This html will be refactored in the bulk actions implementation.
        echo \html_writer::start_tag('form', ['action' => $pageurl, 'method' => 'post', 'id' => 'questionsubmit']);
        echo \html_writer::start_tag('fieldset', ['class' => 'invisiblefieldset', 'style' => "display: block;"]);
        echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo \html_writer::input_hidden_params($this->baseurl);

        $this->display_questions($questions);

        $this->display_bottom_pagination($OUTPUT->render($pagingbar), $totalnumber, $perpage, $pageurl);

        $this->display_bottom_controls($catcontext);

        echo \html_writer::end_tag('fieldset');
        echo \html_writer::end_tag('form');
    }
}
