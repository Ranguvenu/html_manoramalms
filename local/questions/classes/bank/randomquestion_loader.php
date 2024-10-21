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
 * A class for efficiently finds questions at random from the question bank.
 *
 * @package   core_question
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\bank;

/**
 * This class efficiently finds questions at random from the question bank.
 *
 * You can ask for questions at random one at a time. Each time you ask, you
 * pass a category id, and whether to pick from that category and all subcategories
 * or just that category.
 *
 * The number of teams each question has been used is tracked, and we will always
 * return a question from among those elegible that has been used the fewest times.
 * So, if there are questions that have not been used yet in the category asked for,
 * one of those will be returned. However, within one instantiation of this class,
 * we will never return a given question more than once, and we will never return
 * questions passed into the constructor as $usedquestions.
 *
 * @copyright 2015 The Open University
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class randomquestion_loader extends \core_question\local\bank\random_question_loader{
    /** @var \qubaid_condition which usages to consider previous attempts from. */
    protected $qubaids;

    /** @var array qtypes that cannot be used by random questions. */
    protected $excludedqtypes;

    /** @var array categoryid & include subcategories => num previous uses => questionid => 1. */
    protected $availablequestionscache = [];

    /**
     * @var array questionid => num recent uses. Questions that have been used,
     * but that is not yet recorded in the DB.
     */
    protected $recentlyusedquestions;

    /**
     * Constructor.
     *
     * @param \qubaid_condition $qubaids the usages to consider when counting previous uses of each question.
     * @param array $usedquestions questionid => number of times used count. If we should allow for
     *      further existing uses of a question in addition to the ones in $qubaids.
     */
    public function __construct(\qubaid_condition $qubaids, array $usedquestions = []) {
        $this->qubaids = $qubaids;
        $this->recentlyusedquestions = $usedquestions;

        foreach (\question_bank::get_all_qtypes() as $qtype) {
            if (!$qtype->is_usable_by_random()) {
                $this->excludedqtypes[] = $qtype->name();
            }
        }
    }

   
    /**
     * Get the key into {@see $availablequestionscache} for this combination of options.
     *
     * @param int $categoryid the id of a category in the question bank.
     * @param bool $includesubcategories wether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids an array of tag ids.
     * @return string the cache key.
     */
    protected function get_category_key($categoryid, $includesubcategories, $tagids = [], $goalid = null, $boardid = null, $classid = null, $subjectid = null, $unitid = null, $chapterid = null, $topicid = null,$conceptid = null): string {
        if ($includesubcategories) {
            $key = $categoryid . '|1';
        } else {
            $key = $categoryid . '|0';
        }

        if (!empty($tagids)) {
            $key .= '|' . implode('|', $tagids);
        }
        if (!is_null($goalid)) {
            $key .= '|' . $goalid;
        }
        if (!is_null($boardid)) {
            $key .= '|' . $boardid;
        }
        if (!is_null($classid)) {
            $key .= '|' . $classid;
        }
        if (!is_null($subjectid)) {
            $key .= '|' . $subjectid;
        }
        if (!is_null($unitid)) {
            $key .= '|' . $unitid;
        }
        if (!is_null($chapterid)) {
            $key .= '|' . $chapterid;
        }
        if (!is_null($topicid)) {
            $key .= '|' . $topicid;
        }
        if (!is_null($conceptid)) {
            $key .= '|' . $conceptid;
        }

        return $key;
    }

    /**
     * Populate {@see $availablequestionscache} for this combination of options.
     *
     * @param int $categoryid The id of a category in the question bank.
     * @param bool $includesubcategories Whether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. If an array is provided, then
     *      only the questions that are tagged with ALL the provided tagids will be loaded.
     */
    protected function ensurequestions_for_category_loaded($categoryid, $includesubcategories, $tagids = [],$goalid = null,$boardid = null,$classid = null,$courseid = null,$coursetopicsid = null,$chapterid = null, $unitid = null,$conceptid = null): void {
        global $DB;

        $categorykey = $this->get_category_key($categoryid, $includesubcategories, $tagids, $goalid, $boardid, $classid, $subjectid, $unitid, $chapterid, $topicid,$conceptid);

        if (isset($this->availablequestionscache[$categorykey])) {
            // Data is already in the cache, nothing to do.
            return;
        }

        // Load the available questions from the question bank.
        if ($includesubcategories) {
            $categoryids = question_categorylist($categoryid);
        } else {
            $categoryids = [$categoryid];
        }

        list($extraconditions, $extraparams) = $DB->get_in_or_equal($this->excludedqtypes,
                SQL_PARAMS_NAMED, 'excludedqtype', false);

        $questionidsandcounts = \local_questions\bank\bank::get_finder()->get_questions_from_categories_and_tags_with_usage_counts(
              $categoryids, $this->qubaids, 'q.qtype ' . $extraconditions, $extraparams, $tagids,$goalid,$boardid ,$classid,$courseid,$coursetopicsid,$chapterid,$unitid,$conceptid);
        if (!$questionidsandcounts) {
            // No questions in this category.
            $this->availablequestionscache[$categorykey] = [];
            return;
        }

        // Put all the questions with each value of $prevusecount in separate arrays.
        $idsbyusecount = [];
        foreach ($questionidsandcounts as $questionid => $prevusecount) {
            if (isset($this->recentlyusedquestions[$questionid])) {
                // Recently used questions are never returned.
                continue;
            }
            $idsbyusecount[$prevusecount][] = $questionid;
        }

        // Now put that data into our cache. For each count, we need to shuffle
        // questionids, and make those the keys of an array.
        $this->availablequestionscache[$categorykey] = [];
        foreach ($idsbyusecount as $prevusecount => $questionids) {
            shuffle($questionids);
            $this->availablequestionscache[$categorykey][$prevusecount] = array_combine(
                    $questionids, array_fill(0, count($questionids), 1));
        }
        ksort($this->availablequestionscache[$categorykey]);
    }

   

    /**
     * Get the list of available question ids for the given criteria.
     *
     * @param int $categoryid The id of a category in the question bank.
     * @param bool $includesubcategories Whether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. If an array is provided, then
     *      only the questions that are tagged with ALL the provided tagids will be loaded.
     * @return int[] The list of question ids
     */
    protected function getquestion_ids($categoryid, $includesubcategories, $tagids = [],$goalid= null,$boardid = null,$classid = null,$courseid= null,$coursetopicsid = null,$chapterid = null, $unitid = null,$conceptid = null): array {
 
        $this->ensurequestions_for_category_loaded($categoryid, $includesubcategories, $tagids,$goalid,$boardid,$classid,$courseid,$coursetopicsid,$chapterid,$unitid,$conceptid);
        $categorykey = $this->get_category_key($categoryid, $includesubcategories, $tagids, $goalid, $boardid, $classid, $subjectid, $unitid, $chapterid, $topicid,$conceptid);
        $cachedvalues = $this->availablequestionscache[$categorykey];
        $questionids = [];

        foreach ($cachedvalues as $usecount => $ids) {
            $questionids = array_merge($questionids, array_keys($ids));
        }

        return $questionids;
    }



    /**
     * Get the list of available questions for the given criteria.
     *
     * @param int $categoryid The id of a category in the question bank.
     * @param bool $includesubcategories Whether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. If an array is provided, then
     *      only the questions that are tagged with ALL the provided tagids will be loaded.
     * @param int $limit Maximum number of results to return.
     * @param int $offset Number of items to skip from the begging of the result set.
     * @param string[] $fields The fields to return for each question.
     * @return \stdClass[] The list of question records
     */
    public function get_questions_quiz($categoryid, $includesubcategories, $tagids = [], $limit = 100, $offset = 0, $fields = [],$goalid = null,$boardid = null,$classid = null,$courseid =null,$coursetopicsid = null,$chapterid = null, $unitid = null,$conceptid = null) {
        global $DB;


        $questionids = $this->getquestion_ids($categoryid, $includesubcategories, $tagids,$goalid,$boardid,$classid,$courseid,$coursetopicsid,$chapterid,$unitid,$conceptid);
      
        if (empty($questionids)) {
            return [];
        }
        $fields['3'] ='questiontext as name';
        if (empty($fields)) {
            // Return all fields.
            $fieldsstring = '*';
        } else {
            $fieldsstring = implode(',', $fields);
        }

        // Create the query to get the questions (validate that at least we have a question id. If not, do not execute the sql).
        $hasquestions = false;
        if (!empty($questionids)) {
            $hasquestions = true;
        }
        if ($hasquestions) {
            list($condition, $param) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'questionid');
          
            $condition = 'WHERE q.id ' . $condition;
            $sql = "SELECT {$fieldsstring}
                      FROM (SELECT q.*, qbe.questioncategoryid as category
                      FROM {question} q
                      JOIN {question_versions} qv ON qv.questionid = q.id
                      JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid 
                      {$condition}) q ORDER BY q.id";



            return $DB->get_records_sql($sql, $param, $offset, $limit);
        } else {
            return [];
        }
    }

    /**
     * Count the number of available questions for the given criteria.
     *
     * @param int $categoryid The id of a category in the question bank.
     * @param bool $includesubcategories Whether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. If an array is provided, then
     *      only the questions that are tagged with ALL the provided tagids will be loaded.
     * @return int The number of questions matching the criteria.
     */
    public function count_questions($categoryid, $includesubcategories, $tagids = [],$goalid = null,$boardid = null,$classid = null,$coursetopicsid = null, $chapterid = null, $unitid = null,$conceptid = null): int {
        $questionids = $this->getquestion_ids($categoryid, $includesubcategories, $tagids,$goalid = null,$boardid = null,$classid = null,$coursetopicsid = null,$chapterid = null, $unitid = null,$conceptid = null);
        return count($questionids);
    }

        /**
     * Populate {@see $availablequestionscache} for this combination of options.
     *
     * @param int $categoryid The id of a category in the question bank.
     * @param bool $includesubcategories Whether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. If an array is provided, then
     *      only the questions that are tagged with ALL the provided tagids will be loaded.
     */
    protected function ensure_questions_for_category_loaded($categoryid, $includesubcategories, $tagids = [], $goalid = null, $boardid= null, $classid= null, $subjectid= null, $unitid= null, $chapterid= null, $topicid= null,$conceptid = null): void {
        global $DB;


        $categorykey = $this->get_category_key($categoryid, $includesubcategories, $tagids, $goalid, $boardid, $classid, $subjectid, $unitid, $chapterid, $topicid,$conceptid);

        if (isset($this->availablequestionscache[$categorykey])) {
            // Data is already in the cache, nothing to do.
            return;
        }

        // Load the available questions from the question bank.
        if ($includesubcategories) {
            $categoryids = question_categorylist($categoryid);
        } else {
            $categoryids = [$categoryid];
        }

        list($extraconditions, $extraparams) = $DB->get_in_or_equal($this->excludedqtypes,
                SQL_PARAMS_NAMED, 'excludedqtype', false);
        // $questionidsandcounts = \question_bank::get_finder()->get_questions_from_categories_and_tags_with_usage_counts(
        //         $categoryids, $this->qubaids, 'q.qtype ' . $extraconditions, $extraparams, $tagids);
        // $questionidsandcounts = \local_questions\bank\bank::get_finder()->get_questions_from_categories_and_tags_with_usage_counts(
        //         $categoryids, $this->qubaids, 'q.qtype ' . $extraconditions, $extraparams, $tagids, $goalid,$boardid,$classid,$subjectid,$coursetopicsid,$chapterid,$unitid);
         $questionidsandcounts = \local_questions\bank\bank::get_finder()->get_questions_from_categories_and_tags_with_usage_counts(
                $categoryids, $this->qubaids, 'q.qtype ' . $extraconditions, $extraparams, $tagids,  $goalid, $boardid, $classid, $subjectid, $unitid, $chapterid, $topicid,$conceptid);
        if (!$questionidsandcounts) {
            // No questions in this category.
            $this->availablequestionscache[$categorykey] = [];
            return;
        }

        // Put all the questions with each value of $prevusecount in separate arrays.
        $idsbyusecount = [];
        foreach ($questionidsandcounts as $questionid => $prevusecount) {
            if (isset($this->recentlyusedquestions[$questionid])) {
                // Recently used questions are never returned.
                continue;
            }
            $idsbyusecount[$prevusecount][] = $questionid;
        }

        // Now put that data into our cache. For each count, we need to shuffle
        // questionids, and make those the keys of an array.
        $this->availablequestionscache[$categorykey] = [];
        foreach ($idsbyusecount as $prevusecount => $questionids) {
            shuffle($questionids);
            $this->availablequestionscache[$categorykey][$prevusecount] = array_combine(
                    $questionids, array_fill(0, count($questionids), 1));
        }
        ksort($this->availablequestionscache[$categorykey]);
    }
    
    /**
     * Pick a question at random from the given category, from among those with the fewest uses.
     * If an array of tag ids are specified, then only the questions that are tagged with ALL those tags will be selected.
     *
     * It is up the the caller to verify that the cateogry exists. An unknown category
     * behaves like an empty one.
     *
     * @param int $categoryid the id of a category in the question bank.
     * @param bool $includesubcategories wether to pick a question from exactly
     *      that category, or that category and subcategories.
     * @param array $tagids An array of tag ids. A question has to be tagged with all the provided tagids (if any)
     *      in order to be eligible for being picked.
     * @return int|null the id of the question picked, or null if there aren't any.
     */
    public function get_next_question_id($categoryid, $includesubcategories, $tagids = [], $goalid = null, $boardid= null, $classid= null, $subjectid= null, $unitid= null, $chapterid= null, $topicid= null,$conceptid = null): ?int {
        $this->ensure_questions_for_category_loaded($categoryid, $includesubcategories, $tagids, $goalid, $boardid, $classid, $subjectid, $unitid, $chapterid, $topicid,$conceptid);

        $categorykey = $this->get_category_key($categoryid, $includesubcategories, $tagids, $goalid, $boardid, $classid,
                    $subjectid, $unitid, $chapterid, $topicid,$conceptid);
        if (empty($this->availablequestionscache[$categorykey])) {
            return null;
        }

        reset($this->availablequestionscache[$categorykey]);
        $lowestcount = key($this->availablequestionscache[$categorykey]);
        reset($this->availablequestionscache[$categorykey][$lowestcount]);
        $questionid = key($this->availablequestionscache[$categorykey][$lowestcount]);
        $this->use_question($questionid);
        return $questionid;
    }
     
}
