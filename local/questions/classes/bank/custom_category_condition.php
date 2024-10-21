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

namespace local_questions\bank;
use qbank_managecategories\helper;
// namespace mod_quiz\question\bank\filter;

/**
 * A custom filter condition for quiz to select question categories.
 *
 * This is required as quiz will only use ready questions and the count should show according to that.
 *
 * @package    mod_quiz
 * @category   question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_category_condition extends \core_question\bank\search\category_condition {
   public $filterparams;
   public function __construct($cat, $recurse, $contexts, $baseurl, $course, $filterparams) {
      $this->filterparams = $filterparams;
      $this->contexts = $contexts;
     parent::__construct($cat, $recurse, $contexts, $baseurl, $course, $maxinfolength = null);
   }
    public function display_options() {
         global $PAGE,$DB;
         $displaydata = [];
        $catmenu = helper::question_category_options($this->contexts, true, 0,
                true, -1, false);
        $displaydata['categoryselect'] = \html_writer::select($catmenu, 'category', $this->cat, [],
                array('class' => 'searchoptions custom-select', 'id' => 'id_selectacategory'));
        // $displaydata['categorydesc'] = '';
        // if ($this->category) {
        //     $displaydata['categorydesc'] = $this->print_category_info($this->category);
        // }
         $goalsql = "SELECT id,name FROM {local_hierarchy} WHERE depth = :depth AND parent = :parent AND is_active =  :isactive";
         $goalmenu= $DB->get_records_sql_menu($goalsql,['depth'=>1,'parent'=>0,'isactive'=>1]);
         $defaultgoalvalue[null] =get_string("choose_goal",'local_questions');
         $goalmenu =$defaultgoalvalue + $goalmenu;
         $displaydata['goalselect'] = \html_writer::select($goalmenu, 'goal', $this->filterparams['goal'], [],
            ['class' => 'searchoptions custom-select', 'id' => 'id_selectagoal']);

         $gid = (int) $this->filterparams['goal'];
         $boardsql = "SELECT id,name FROM {local_hierarchy} WHERE parent = :gid AND  depth=:depth AND is_active = :isactive";
         $boardmenu = $DB->get_records_sql_menu($boardsql, ['gid'=>$gid,'depth'=>2,'isactive'=>1]);
         $defaultboardvalue[null] = get_string("choose_board",'local_questions');
         $boardmenu =$defaultboardvalue + $boardmenu;
         $displaydata['boardselect'] = \html_writer::select($boardmenu, 'board',  $this->filterparams['board'], [],
            ['class' => 'searchoptions custom-select', 'id' => 'id_selectaboard' ]);
         if($gid && isset($boardmenu[$this->filterparams['board']])){
            $bid = (int) $this->filterparams['board'];
            $classsql = "SELECT id,name FROM {local_hierarchy} WHERE parent = :bid AND  depth=
            :depth AND is_active = :isactive";
            $classmenu = $DB->get_records_sql_menu($classsql,['bid'=>$bid,'depth'=>3,'isactive'=>1]);
         }else{
            $classmenu = [];
            $bid = 0;
         }
         $defaultclassvalue[null] = get_string("choose_class",'local_questions');
         $classmenu =$defaultclassvalue + $classmenu;
         $displaydata['classselect'] = \html_writer::select($classmenu, 'class',  $this->filterparams['class'], [],
            ['class' => 'searchoptions custom-select', 'id' => 'id_selectaclass' ]);
         
        if($gid && $bid && isset($classmenu[$this->filterparams['class']])){
         $cid = (int) $this->filterparams['class'];
         $subjectsql = "SELECT sub.courseid as id,sub.name as fullname from {local_subjects} AS sub WHERE classessid = :cid AND is_active =:isactive ";
         $subjectmenu= $DB->get_records_sql_menu($subjectsql, ['cid'=>$cid,'isactive'=>1]);
         }
         else{
            $subjectmenu = [];
            $cid = 0;
         }
         $defaultsubjectvalue[null] = get_string("choose_subject",'local_questions');
         $subjectmenu =$defaultsubjectvalue + $subjectmenu;
         $displaydata['courseselect'] = \html_writer::select($subjectmenu, 'course',  $this->filterparams['course'], [],
            ['class' => 'searchoptions custom-select', 'id' => 'id_selectacourse' ]);

         if($gid && $bid && $cid && isset($subjectmenu[$this->filterparams['course']])){
         $couid = (int) $this->filterparams['course'];
         $topicssql = "SELECT lu.id AS id, lu.name AS fullname 
                       FROM {local_units} AS lu WHERE  courseid = :couid";
         $coursetopicmenu= $DB->get_records_sql_menu($topicssql,['couid'=>$couid]);
         }
         else{
         $coursetopicmenu = [];
         $couid = 0; 
         }
         $defaulttopicvalue[null] = get_string("choose_unit",'local_questions');
         $coursetopicmenu =$defaulttopicvalue + $coursetopicmenu;
         $displaydata['coursetopicselect'] = \html_writer::select($coursetopicmenu, 'coursetopic', $this->filterparams['coursetopic'], [],['class' => 'searchoptions custom-select', 'id' => 'id_selectacoursetopic' ]);
         
         $coursetopic = (int) $this->filterparams['coursetopic'];
         $defaultchaptervalue = [null => get_string("choose_chapter",'local_questions')];
         if($coursetopic){
            $chaptersql = "SELECT lc.id AS id, lc.name AS fullname 
                        FROM {local_chapters} AS lc 
                        WHERE  courseid = :courseid AND unitid = :unitid";
          $chaptermenu= $DB->get_records_sql_menu($chaptersql,['unitid'=>$coursetopic,'courseid'=>$couid]);
           $chaptermenu =$defaultchaptervalue + $chaptermenu;
         }else{
            $chaptermenu =$defaultchaptervalue;
         }

         $displaydata['chapterselect'] = \html_writer::select($chaptermenu, 'chapter', $this->filterparams['chapter'], [],['class' => 'searchoptions custom-select', 'id' => 'id_selectachapter' ]);

         $chapterid = (int) $this->filterparams['chapter'];
         $defaultunitvalue = [null => get_string("choose_topic",'local_questions')];
         if($chapterid){
            $unitsql = "SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_topics} AS lt WHERE  courseid = :courseid AND chapterid = :chapterid  AND unitid = :unitid ";
           $unitmenu= $DB->get_records_sql_menu($unitsql, ['unitid'=>$coursetopic,'courseid'=>$couid,'chapterid'=>$chapterid]);
           $unitmenu =$defaultunitvalue + $unitmenu;
         }else{
            $unitmenu =$defaultunitvalue;
         }
         $unitmenu =$defaultunitvalue + $unitmenu;
         $displaydata['unitselectselect'] = \html_writer::select($unitmenu, 'unit', $this->filterparams['unit'], [],['class' => 'searchoptions custom-select', 'id' => 'id_selectaunit' ]);

         $unitid = (int) $this->filterparams['unit'];
         $defaultconceptvalue = [null => get_string("choose_concept",'local_questions')];
         if($unitid){
            $conceptsql = "SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_concept} AS lt WHERE  courseid = :courseid AND chapterid = :chapterid  AND unitid = :unitid AND topicid = :topicid";
           $conceptmenu = $DB->get_records_sql_menu($conceptsql, ['unitid'=>$coursetopic,'courseid'=>$couid,'chapterid'=>$chapterid,'topicid'=>$unitid]);
           $conceptmenu  =$defaultconceptvalue + $conceptmenu ;
         }else{
            $conceptmenu  =$defaultconceptvalue;
         }
         $conceptmenu  =$defaultconceptvalue + $conceptmenu ;
         $displaydata['conceptselectselect'] = \html_writer::select($conceptmenu , 'concept', $this->filterparams['concept'], [],['class' => 'searchoptions custom-select', 'id' => 'id_selectaconcept' ]);

         $displaydata['questionids'] =  \html_writer::empty_tag('input', array('type'=>'text', 'name'=>'questionid', 'value'=>$this->filterparams['questionid'],'placeholder'=>get_string('enterqids','local_questions'),'style'=>'width:200px'));
        return $PAGE->get_renderer('local_questions')->render_custom_condition($displaydata);
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
                                        $this->contexts->having_cap('moodle/question:add'));
        echo \html_writer::end_div();

    }
}
