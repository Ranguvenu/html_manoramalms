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

function questionid_filter($mform){
    global $DB;
     $cmid = optional_param('cmid', '', PARAM_INT);
    $qidarray=array();
    $qidarray[] = get_string("enterqids",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'questionidlist',
        'id' => 'id_questionid',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedcourses();}) }) (event)",
    );
    //$mform->addElement('text', 'questionid', get_string("selectquestionid",'local_questions'),$qidarray, $options);
    $mform->addElement('text', 'questionid', '', ['placeholder' => get_string('enterqids', 'local_questions')]);
    $mform->addElement('hidden', 'cmid', '');
    $mform->setDefault('cmid', $cmid);

}
function qidentifier_filter($mform){
    global $DB;
    $mform->addElement('text', 'qidentifier', '', ['placeholder' => get_string('enteridentifiers', 'local_questions')]);
}

function goal_filter($mform){
    global $DB,$USER,$PAGE;
       $selectedgoalrecords =$DB->get_records_sql_menu("SELECT id,name FROM {local_hierarchy} WHERE depth=1");
       $defaultvalue[null] =get_string("choose_goal",'local_questions');
       $getgoalrecords =$defaultvalue + $selectedgoalrecords;
         $formattedoptions = array();
        $formattedoptions[null] = get_string("select_goal",'customfield_goal');
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'goallist',
            'id' => 'id_customfield_goal',
            'class' => 'goal',
            'multiple' => false,
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removegoals();}) }) (event)",
            'placeholder' => get_string('choose_goal', 'local_questions'),
        );
    $mform->addElement('autocomplete','goal', '', $getgoalrecords,$options);
}

function board_filter($mform){
    global $DB;
    $bidopt = optional_param('boardid', '', PARAM_INT);
    $boardarray=array();
    $boardarray[null] = get_string("choose_board",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'boardlist',
        'id' => 'id_customfield_board',
        'class' => 'board',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removeboards();}) }) (event)",
        'placeholder' => get_string('choose_board', 'local_questions')
    );
    if(data_submitted()){
       $data = data_submitted();
       $bid= $data->board;
       if($bid) {
        $getboardname =  $DB->get_records_sql_menu('SELECT id,name as fullname FROM {local_hierarchy} WHERE id ='.$bid.' AND depth=2 ');
       }
    }
   if($bidopt) {
   $getboardname =  $DB->get_records_sql_menu('SELECT id,name as fullname FROM {local_hierarchy} WHERE id ='.$bidopt.' AND depth=2 ');
   }
    $boards=!empty($getboardname) ? $getboardname : $boardarray ;
    $mform->addElement('autocomplete', 'board', '', $boards, $options);
}
function classes_filter($mform){ 
    global $DB,$USER,$PAGE;
    $cidopt = optional_param('classid', '', PARAM_INT);
    $classarray=array();
    $classarray[null] = get_string("choose_class",'local_questions');
       $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'classlist',
        'id' => 'id_customfield_class',
        'class' => 'class',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removeclasses();}) }) (event)",
        'placeholder' => get_string('choose_class', 'local_questions')
    );
    if(data_submitted()){
        $data = data_submitted();
        $cid= $data->class;
        if($cid) {
        $getclassname = $DB->get_records_sql_menu('SELECT id,name as fullname FROM {local_hierarchy} WHERE id ='.$cid.' AND depth=3 ');
        }
    }
    if($cidopt) {
    $getclassname = $DB->get_records_sql_menu('SELECT id,name as fullname FROM {local_hierarchy} WHERE id ='.$cidopt.' AND depth=3 ');
    }
   $classes=!empty($getclassname) ? $getclassname : $classarray ;
   $mform->addElement('autocomplete', 'class', '', $classes, $options);
}


function courses_filter($mform){
    global $DB;
    $subjectid = optional_param('subjectid', '', PARAM_INT);
    $coursearray=array();
    $coursearray[null] = get_string("choose_subject",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'courselist',
        'id' => 'id_customfield_course',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removecourses();}) }) (event)",
        'placeholder' => get_string('choose_subject', 'local_questions')
    );
   if(data_submitted()){
       $data = data_submitted();
       $sid= $data->subject;
       if($sid) {
       $getcoursename = $DB->get_records_sql_menu('SELECT courseid as id,name as fullname FROM {local_subjects} WHERE courseid ='.$sid);
       }
    }
    if($subjectid) {
   $getcoursename = $DB->get_records_sql_menu('SELECT courseid as id,name as fullname FROM {local_subjects} WHERE courseid ='.$subjectid);
   }
    $courses=!empty($getcoursename) ? $getcoursename : $coursearray ;
    $mform->addElement('autocomplete', 'subject', '', $courses, $options);
}
function topics_filter($mform){
    global $DB;
    $topicidopt = optional_param('topicid', '', PARAM_INT);
    $coursetopicarray=array();
    $coursetopicarray[null] = get_string("choose_unit",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'topicslist',
        'id' => 'id_customfield_coursetopics',
        'class' => 'topics',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removeunit();}) }) (event)",
        'placeholder' => get_string('choose_unit', 'local_questions')
    );
    if($topicidopt) {
    $gettopicname = $DB->get_records_sql_menu("SELECT lu.id AS id, lu.name AS fullname 
                           FROM {local_units} AS lu WHERE  id = $topicidopt");   
    }
     $topicname=!empty($gettopicname) ? $gettopicname : $coursetopicarray ;
    $mform->addElement('autocomplete', 'topic', '', $topicname, $options);

}
function chapter_filter($mform){
    global $DB;
    $chapteridopt = optional_param('chapterid', '', PARAM_INT);
    $chapterarray=array();
    $chapterarray[null] = get_string("choose_chapter",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'chapterlist',
        'id' => 'id_customfield_chapter',
        'class' => 'chapter',
        'multiple' => false,
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removechapter();}) }) (event)",
        'placeholder' => get_string('choose_chapter', 'local_questions')
    );
    if($chapteridopt) {
    $getchaptername = $DB->get_records_sql_menu("SELECT lc.id AS id, lc.name AS fullname 
                            FROM {local_chapters} AS lc WHERE  id = $chapteridopt");
    }
     $chapter=!empty($getchaptername) ? $getchaptername : $chapterarray ;
    $mform->addElement('autocomplete', 'chapter', '', $chapter, $options);

}
function unit_filter($mform){
    global $DB;
    $unitidopt = optional_param('unitid', '', PARAM_INT);
    $unitarray=array();
    $unitarray[null] = get_string("choose_topic",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'unitlist',
        'id' => 'id_customfield_unit',
        'class' => 'unit',
        'multiple' => false,
        'placeholder' => get_string('choose_topic', 'local_questions'),
        'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.removeconcept();}) }) (event)"
    );
    if($unitidopt) {
    $getunitname = $DB->get_records_sql_menu("SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_topics} AS lt WHERE  id = $unitidopt ");
    
    }
     $unitname=!empty($getunitname) ? $getunitname : $unitarray ;
    $mform->addElement('autocomplete', 'unit', '', $unitname, $options);

}
function concept_filter($mform){
    global $DB;
    $conceptidopt = optional_param('conceptid', '', PARAM_INT);
    $conceptarray=array();
    $conceptarray[null] = get_string("choose_concept",'local_questions');
    $options = array(
        'ajax' => 'local_questions/coursetopics',
        'data-type' => 'conceptlist',
        'id' => 'id_customfield_concept',
        'class' => 'concept',
        'multiple' => false,
        'placeholder' => get_string('choose_concept', 'local_questions'),
    );
    if($conceptidopt) {
    $getconceptname = $DB->get_records_sql_menu("SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_concept} AS lt WHERE  id = $conceptidopt ");
    }
     $conceptname=!empty($getconceptname) ? $getconceptname : $conceptarray ;
    $mform->addElement('autocomplete', 'concept', '', $conceptname, $options);

}
function source_filter($mform){
    global $DB;
     $default[null] = get_string("choose_source",'local_questions');
     $sources= $DB->get_records_sql_menu("SELECT id,name FROM {local_question_sources} WHERE 1=1 ORDER by id DESC");
     $defaultoptions = $default + $sources;

        $formattedoptions = array();
        $formattedoptions[0] = get_string("choose_source",'local_questions');
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'sourcelist',
            'id' => 'id_customfield_source',
            'class' => 'source',
            'multiple' => false,
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedgoals();}) }) (event)",
            'placeholder' => get_string('choose_source', 'local_questions'),
        );
    $mform->addElement('autocomplete','source', '',$defaultoptions, $options, array('placeholder' => get_string("choose_source",'local_questions')));
}
function difficulty_filter($mform){
     global $DB;
   $opt = [];
    $opt['1'] =  get_string('high','customfield_difficultylevel');
    $opt['2'] =  get_string('medium','customfield_difficultylevel');
    $opt['3'] = get_string('low','customfield_difficultylevel');
       $defaultvalue[null] =get_string("choose_difficultylevel",'customfield_difficultylevel');
       $getdifficultyrecords =$defaultvalue + $opt;
      $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'difficultylist',
            'id' => 'id_customfield_difficulty',
            'class' => 'difficulty',
            'multiple' => false,
            'placeholder' => get_string('choose_difficultylevel', 'customfield_difficultylevel'),
        );
       $formattedoptions = array();
    $mform->addElement('autocomplete','difficulty', '',$getdifficultyrecords, $options);
}
function cognitive_filter($mform){
    $options = [];
    $options['1'] =  get_string('na','customfield_cognitivelevel');
    $options['2'] =  get_string('creating','customfield_cognitivelevel');
    $options['3'] =  get_string('evaluating','customfield_cognitivelevel');
    $options['4'] =  get_string('analysing','customfield_cognitivelevel');
    $options['5'] =  get_string('applying','customfield_cognitivelevel') ; 
    $options['6'] =  get_string('understanding','customfield_cognitivelevel') ; 
    $options['7'] =  get_string('remembering','customfield_cognitivelevel') ; 
    $defaultvalue[null] =get_string("choose_cognitivelevel",'customfield_cognitivelevel');
    $getcognitiverecords = $defaultvalue+$options;
    $selectoptions = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'cognitivelist',
            'id' => 'id_customfield_cognitive',
            'class' => 'cognitive',
            'multiple' => false,
            'placeholder' => get_string('choose_cognitivelevel', 'local_questions'),
        );
    $mform->addElement('autocomplete','cognitive', '',$getcognitiverecords, $selectoptions,);
}
function qstatus_filter($mform){
    $qstatus = [
            'draft' => get_string('draft', 'local_questions'),
            'underreview' => get_string('underreview', 'local_questions'),
            'readytoreview' => get_string('readytoreview', 'local_questions'),
            'reject' => get_string('reject', 'local_questions'),
            'publish' => get_string('publish', 'local_questions')
        ];
         $defaultvalue[null] =get_string("choose_qstatus",'local_questions');
        $getqstatusrecords = $defaultvalue+$qstatus;

         $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'qstatuslist',
            'id' => 'id_customfield_qstatus',
            'class' => 'qstatus',
            'multiple' => false,
            'placeholder' => get_string('choose_qstatus', 'local_questions'),
        );
    $mform->addElement('autocomplete','qstatus', '',$getqstatusrecords, $options);
}
function betweendate_filter($mform){
    $systemcontext = context_system::instance();
    $mform->addElement('date_selector', 'uploadfrom', get_string('uploadfrom', 'local_questions'),array('optional'=>true));
    $mform->setType('uploadfrom', PARAM_RAW);

    $mform->addElement('date_selector', 'uploadto', get_string('uploadto', 'local_questions'),array('optional'=>true));
    $mform->setType('uploadto', PARAM_RAW);

}
/**
 * Add a random question to the quiz at a given point.
 * @param stdClass $quiz the quiz settings.
 * @param int $addonpage the page on which to add the question.
 * @param int $categoryid the question category to add the question from.
 * @param int $number the number of random questions to add.
 * @param bool $includesubcategories whether to include questoins from subcategories.
 * @param int[] $tagids Array of tagids. The question that will be picked randomly should be tagged with all these tags.
 */
function local_questions_quiz_add_random_questions($quiz, $addonpage, $categoryid, $number,
        $includesubcategories, $tagids = [], $goalid, $boardid, $classid, $courseid, $coursetopicid,$chapterid,$unitid,$conceptid) {
    global $DB;

    $category = $DB->get_record('question_categories', ['id' => $categoryid]);
    if (!$category) {
        new moodle_exception('invalidcategoryid');
    }

    $catcontext = context::instance_by_id($category->contextid);
    require_capability('moodle/question:useall', $catcontext);

    // Tags for filter condition.
    $tags = \core_tag_tag::get_bulk($tagids, 'id, name');
    $tagstrings = [];
    foreach ($tags as $tag) {
        $tagstrings[] = "{$tag->id},{$tag->name}";
    }
    // Create the selected number of random questions.
    for ($i = 0; $i < $number; $i++) {
        // Set the filter conditions.
        $filtercondition = new stdClass();
        $filtercondition->questioncategoryid = $categoryid;
        $filtercondition->includingsubcategories = $includesubcategories ? 1 : 0;
        $goalsql="SELECT id,name  
                    FROM {local_hierarchy}  
                    WHERE id = $goalid 
                    AND parent = 0 
                    AND depth = 1";
        $goalname = $DB->get_records_sql_menu($goalsql);
        $filtercondition->goalid = $goalname;
         $boardsql="SELECT id,name  
                    FROM {local_hierarchy}  
                    WHERE id = $boardid  AND depth = 2";
        $boardname = $DB->get_records_sql_menu($boardsql);
        $filtercondition->boardid = $boardname;

        $classsql="SELECT id,name  
                    FROM {local_hierarchy}  
                    WHERE id = $classid  AND depth = 3";
        $classname = $DB->get_records_sql_menu($classsql);
        $filtercondition->classid = $classname;

        $coursesql="SELECT sub.courseid as id,sub.name as fullname
                    FROM {local_subjects} AS sub 
                    WHERE courseid = $courseid";
        $coursename = $DB->get_records_sql_menu($coursesql);
        $filtercondition->courseid = $coursename;
     
        $coursetopicsql = "SELECT id AS id, name as fullname FROM {local_units} WHERE id = $coursetopicid ";
        $coursetopicname = $DB->get_records_sql_menu($coursetopicsql);
        $filtercondition->coursetopicid = $coursetopicname;


         $chaptersql = "SELECT id AS id, name as fullname FROM {local_chapters} WHERE  id = $chapterid";                    
        $chaptername = $DB->get_records_sql_menu($chaptersql);
        $filtercondition->chapterid = $chaptername;
      

        $unitsql = "SELECT id AS id, name as fullname FROM {local_topics} WHERE id = $unitid "; 
        $unitname = $DB->get_records_sql_menu($unitsql);
        $filtercondition->unitid = $unitname;

        $conceptsql = "SELECT id AS id, name as fullname FROM {local_concept} WHERE id = $conceptid "; 
        $conceptname = $DB->get_records_sql_menu($conceptsql);
        $filtercondition->conceptid = $conceptname;

        if (!empty($tagstrings)) {
            $filtercondition->tags = $tagstrings;
        }

        if (!isset($quiz->cmid)) {
            $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
            $quiz->cmid = $cm->id;
        }

        // Slot data.
        $randomslotdata = new stdClass();
        $randomslotdata->quizid = $quiz->id;
        $randomslotdata->usingcontextid = context_module::instance($quiz->cmid)->id;
        $randomslotdata->questionscontextid = $category->contextid;
        $randomslotdata->maxmark = 1;
    
        $randomslot = new \mod_quiz\local\structure\slot_random($randomslotdata);
        $randomslot->set_quiz($quiz);
        $randomslot->set_filter_condition($filtercondition);
        $randomslot->insert($addonpage);
    }
}
/**
 * Function to display the reject reason form
 * returns data of the popup
 */
function local_questions_output_fragment_rejected_reviewdata($args) {
    global $DB, $PAGE, $USER, $CFG, $OUTPUT;
    $params = [];
    $params['qid'] = $args['questionid'];
    $params['qbankeid'] = $args['qbentryid'];
    $rejectedquestionsql = "SELECT *
                              FROM {local_rejected_questions}
                             WHERE questionid = :qid
                               AND questionbankentryid = :qbankeid
                             ORDER BY id DESC";
    $rejectedquestions = $DB->get_records_sql($rejectedquestionsql, $params);
    $count = 0;
    foreach ($rejectedquestions as $qkey => $rejectedquestion) {
        $count ++;
        $sql = "SELECT CONCAT(firstname, ' ', lastname) as fullname
                  FROM {user} WHERE id = ?";
        $fullname = $DB->get_field_sql($sql, [$rejectedquestion->usercreated]);
        $rejectedquestion->userfullname = $fullname;
        $rejectedquestion->daterejectedon = date('d-m-Y', $rejectedquestion->timecreated);
        $rejectedquestion->count = $count;
    }
    $rejecteddata = array_values($rejectedquestions);

    $templatedata = [
        'rejectedquestionsdata' => $rejecteddata,
    ];
    $output = $OUTPUT->render_from_template('local_questions/rejecteddata', $templatedata);

    return $output;
}
