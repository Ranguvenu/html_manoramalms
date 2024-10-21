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
 * Goals hierarchy
 *
 * This file defines the current version of the local_faq Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_faq
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_faq;

use core_form\dynamic_form;
use dml_exception;
use moodle_url;
use context;
use context_system;
use html_writer;
use stdClass;

use core_course_category;

/**
 * Goals controller class
 */
class controller
{

    /** @var $usermodified */
    private $usermodified;

    /** @var $usermodified */
    private $usercreated;

    /** @var $usermodified */
    private $timecreated;

    /** @var $timemodified */
    private $timemodified;

    /** @var $context */
    private $context;

    /** Construct */
    public function __construct()
    {
        global $USER;
        $this->usermodified = $USER->id;
        $this->usercreated = $USER->id;
        $this->timecreated = time();
        $this->timemodified = time();
        $this->context = context_system::instance();
    }


    public function save_draft_area_files($attachment)
    {
        $context = context_system::instance();
        file_save_draft_area_files(
            // The $data->attachments property contains the itemid of the draft file area.
            $attachment,
            $context->id,
            'local_faq',
            'local_faq',
            $attachment,
        );
        return true;
    }


    /**
     * Create and update faq query.
     * @param [type] $data [description]
     */

    public function create_update_query($data)
    {
        global $DB, $CFG;

        $context = context_system::instance();

        $textfieldoptions = array(
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => -1,
            'maxbytes' => $CFG->maxbytes,
            'context' => $context,
        );

        if ($data->id > 0) {
            // Update existing record
            $data->logo = $this->save_draft_area_files($data->attachments);

            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $textfieldoptions,
                $context,
                'local_faq',
                'description',
                $data->id
            );

            $data->timemodified = $this->timemodified;
            $data->usermodified = $this->usermodified;

            $DB->update_record('local_faq_queries', $data);
        } else {
            // Insert new record
            $data->logo = $this->save_draft_area_files($data->attachments);
            $data->timecreated = $this->timecreated;
            $data->usercreated = $this->usercreated;
            $data->id = $DB->insert_record('local_faq_queries', $data);
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $textfieldoptions,
                $context,
                'local_faq',
                'description',
                $data->id
            );
            $DB->update_record('local_faq_queries', $data);
        }
    }


    /**
     * Create and update faq category.
     * @param [type] $data [description]
     */

    public function create_update_category($data)
    {
        global $DB, $CFG;
        $context = context_system::instance();

        $textfieldoptions = array(
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => -1,
            'maxbytes' => $CFG->maxbytes,
            'context' => $context,
        );

        if ($data->id > 0) {

            $id = $data->id;
            $data->logo = $this->save_draft_area_files($data->logo);
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $textfieldoptions,
                $context,
                'local_faq',
                'description',
                $id
            );
            $data->timemodified = $this->timemodified;
            $data->usermodified = $this->usermodified;

            $id = $DB->update_record('local_faq_categories', $data);
        } else {

            $data->logo = $this->save_draft_area_files($data->logo);

            $data->timecreated = $this->timecreated;
            $data->usercreated = $this->usercreated;

            $data->id = $DB->insert_record('local_faq_categories', $data);

            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $textfieldoptions,
                $context,
                'local_faq',
                'description',
                $data->id
            );
            $DB->update_record('local_faq_categories', $data);
        }

        return $id;
    }


    public function set_faq_category($id)
    {
        global $DB;
        // $systemcontext = context_system::instance();
        $data = $DB->get_record('local_faq_categories', ['id' => $id], '*');

        if ($data) {
            $data->description_editor = $data->description;
        } else {
            $data = new stdClass();
        }
        return $data;
    }

    public function set_faq_query($id)
    {
        global $DB;

        $data = $DB->get_record('local_faq_queries', ['id' => $id], '*');

        if ($data) {
            $data->description_editor = $data->description;
        } else {
            $data = new stdClass();
        }

        return $data;
    }


    /**
     * Deleting Faq category
     * @param $data [Array]
     */
    public function delete_faqcategory($data)
    {
        global $DB;


        try {
            $DB->delete_records('local_faq_categories', ['id' => $data['id']]);
        } catch (dml_exception $e) {
            print_r($e);
        }


        return true;
    }


    /**
     * Deleting Faq Querie
     * @param $data [Array]
     */
    public function delete_faqquery($data)
    {
        global $DB;

        try {
            $DB->delete_records('local_faq_queries', ['id' => $data['id']]);
        } catch (dml_exception $e) {
            print_r($e);
        }


        return true;
    }

    public function faq_category_list()
    {
        global $DB;

        $categorylist = $DB->get_records_sql_menu("SELECT fc.id, fc.name as fullname 
        FROM {local_faq_categories} fc");

        return $categorylist;
    }

    public function get_twitter_image_src($tablename, $fieldname, $id)
    {
        global $DB;

        $itemid = $DB->get_field($tablename, $fieldname, array('id' => $id));
        if ($itemid) {
            $context = context_system::instance();

            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'local_faq', 'local_faq', $itemid, 'sortorder', false);
            if ($files) {
                foreach ($files as $file) {
                    $fileurl = \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                }

                return $fileurl->out();
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function faq_data()
    {
        global $DB, $CFG;
        $sql = "SELECT fc.id as categoryid, fc.name as categoryname, fc.logo, fc.sortorder, fc.description FROM {local_faq_categories} fc ORDER BY fc.sortorder ASC";
        $faqcategories = $DB->get_records_sql($sql);
        $systemcontext = context_system::instance();
        $faqinfo = [];
        $caneditdeletecat = false;
        if (is_siteadmin() || has_capability('local/faq:category_delete', $systemcontext)) {
            $caneditdeletecat = true;
        }
        $caneditdeletequery = false;
        if (is_siteadmin() || has_capability('local/faq:query_create', $systemcontext)) {
            $caneditdeletequery = true;
        }

        foreach ($faqcategories as $faqcategory) {
            $data = new stdClass();
            $data->categoryid = $faqcategory->categoryid;
            $data->categoryname = $faqcategory->categoryname;
            $data->logo = $faqcategory->logo;
            $data->description = file_rewrite_pluginfile_urls(
                $faqcategory->description,
                'pluginfile.php',
                1,
                'local_faq',
                'description',
                $faqcategory->categoryid
            );
            $data->wwwroot = $CFG->wwwroot;
            $data->caneditdeletecategory = $caneditdeletecat;

            // Modify the query to include a condition for the category
            $qsql = "SELECT fq.id as queryid, fq.name as queryname, fq.description as querydescription, fq.attachments FROM {local_faq_queries} fq WHERE fq.categoryid = :category_id";
            $params = ['category_id' => $faqcategory->categoryid];
            $faqqueries = $DB->get_records_sql($qsql, $params);

            $faqqueryinfo = [];

            foreach ($faqqueries as $faqquery) {
                $qdata = new stdClass();
                $qdata->queryid = $faqquery->queryid;
                $qdata->queryname = $faqquery->queryname;
                $data->caneditdeletequery = $caneditdeletequery;
                $qdata->querydescription = file_rewrite_pluginfile_urls(
                    $faqquery->querydescription,
                    'pluginfile.php',
                    1,
                    'local_faq',
                    'description',
                    $faqquery->queryid
                );
                $qdata->attachments = $faqquery->attachments;

                $faqqueryinfo[] = $qdata;
            }

            $data->queries = $faqqueryinfo;
            $faqinfo[] = $data;
        }

        return $faqinfo;
    }



    public function view_faq_data()
    {
        global $DB, $CFG;

        $sql = "SELECT fc.id as categoryid, fc.name as categoryname, fc.logo, fc.sortorder, fc.description FROM {local_faq_categories} fc ORDER BY fc.sortorder ASC";
        $faqcategories = $DB->get_records_sql($sql);

        $faqinfo = [];

        foreach ($faqcategories as $faqcategory) {
            $data = new stdClass();
            $data->id = $faqcategory->categoryid;
            $data->name = $faqcategory->categoryname;
            $data->description = file_rewrite_pluginfile_urls(
                $faqcategory->description,
                'pluginfile.php',
                1,
                'local_faq',
                'description',
                $faqcategory->categoryid
            );

            // Modify the query to include a condition for the category
            $qsql = "SELECT fq.id as queryid, fq.name as queryname, fq.description as querydescription, fq.attachments FROM {local_faq_queries} fq WHERE fq.categoryid = :category_id";
            $params = ['category_id' => $faqcategory->categoryid];
            $faqqueries = $DB->get_records_sql($qsql, $params);

            $faqqueryinfo = [];

            foreach ($faqqueries as $faqquery) {
                $qdata = new stdClass();
                $qdata->id = $faqquery->queryid;
                $qdata->name = $faqquery->queryname;
                $qdata->description = file_rewrite_pluginfile_urls(
                    $faqquery->querydescription,
                    'pluginfile.php',
                    1,
                    'local_faq',
                    'description',
                    $faqquery->queryid
                );
                $qdata->attachments = $this->get_twitter_image_src($tablename = 'local_faq_queries', $fieldname = 'attachments', $faqquery->queryid);

                $faqqueryinfo[] = $qdata;
            }

            $data->queries = $faqqueryinfo;

            $faqinfo['categories'][] = $data;
        }
        return $faqinfo;
    }
}
