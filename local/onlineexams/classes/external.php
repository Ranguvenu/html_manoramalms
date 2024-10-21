<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Courses external API
 *
 * @package    local_onlineexams
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

defined('MOODLE_INTERNAL') || die;

use \local_onlineexams\form\custom_onlineexams_form as custom_onlineexams_form;
use \local_courses\action\insert as insert;
use \local_onlineexams\local\general_lib as general_lib;
use \local_courses\form\custom_courseevidence_form as custom_courseevidence_form;


require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/course/lib.php');
//require_once($CFG->dirroot . '/local/courses/lib.php');
//require_once($CFG->dirroot . '/local/costcenter/lib.php');
require_once($CFG->dirroot . '/local/onlineexams/lib.php');
require_once('../../config.php');
class local_onlineexams_external extends external_api
{

    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_onlineexams_form_parameters()
    {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'id' => new external_value(PARAM_INT, 'Course id', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create course form.
     *
     * @param int $contextid The context id for the course.
     * @param int $form_status form position.
     * @param int $id exam id -1 as default.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new Exam id.
     */
    public static function submit_create_onlineexams_form($contextid, $form_status, $id, $jsonformdata)
    {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/formslib.php');
       // require_once($CFG->dirroot . '/local/courses/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_create_onlineexams_form_parameters(),
            ['contextid' => $contextid, 'form_status' => $form_status,  'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $data = array();
        $serialiseddata = json_decode($params['jsonformdata']);
        if(is_object($serialiseddata)){
        $serialiseddata = serialize($serialiseddata);
        }
        parse_str($serialiseddata, $data);
        $warnings = array();
        if ($id) {
            $sql = "SELECT *
                      FROM {course_categories}
                     WHERE idnumber LIKE :idnumber";
            $category = $DB->get_record_sql($sql, ['idnumber' => 'yearbookv2']);
            $catcontext = context_coursecat::instance($category->id);
            $exam = get_course($id);
            // $category = $DB->get_record('course_categories', array('id' => $exam->category), '*', MUST_EXIST);
        } else {
            $exam = null;
        }
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true);
        $overviewfilesoptions = course_overviewfiles_options($exam);
        // if (!empty($exam)) {
        //     // Add context for editor.
        //     $editoroptions['context'] = $coursecontext;
        //     $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
        //     $exam = file_prepare_standard_editor($exam, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
        //     if ($overviewfilesoptions) {
        //         file_prepare_standard_filemanager($exam, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
        //     }
        //     $get_coursedetails = $DB->get_record('course', array('id' => $exam->id));
        // } else {
            // Editor should respect category context if course context is not set.
            $editoroptions['context'] = $catcontext;
            $editoroptions['subdirs'] = 0;
            $exam = file_prepare_standard_editor($exam, 'summary', $editoroptions, null, 'course', 'summary', null);
            if ($overviewfilesoptions) {
                file_prepare_standard_filemanager($exam, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
            }
        // }
        $params = array(
            'course' => $exam,
            'category' => $category,
            'editoroptions' => $editoroptions,
            'returnto' => $returnto,
            'get_coursedetails' => $get_coursedetails,
            'form_status' => $form_status,
            'costcenterid' => $data->open_path,
            'courseid' => $data['id'],
        );
        // The last param is the ajax submitted data.
        $mform = new custom_onlineexams_form(null, $params, 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {

            //quiz module ends here
            $formheaders = array_keys($mform->formstatus);
            $category_id = $data['category'];
            // if (is_siteadmin()) {
            //     $departmentarr=(array) $data['open_departmentid'];
            //     $open_departmentid = implode(',', $departmentarr);
            // } else {
            //     $open_departmentid = $data['open_departmentid'];
            // }
            // $subdepartmentarr=(array) $data['open_departmentid'];
            // $open_subdepartment = implode(',', $subdepartmentarr);
            // $open_departmentid = is_null($open_departmentid) ? 0  : $open_departmentid;
            // $open_subdepartment = is_null($open_subdepartment) ? 0 : $open_subdepartment;
            if ($validateddata->id <= 0) {
                $validateddata->category = $category_id;
                // $validateddata->open_departmentid = $open_departmentid;
                if ($validateddata->examtype==1) {
                    $validateddata->open_module = 'year_book';
                } else if ($validateddata->examtype==0) {
                    $validateddata->open_module = 'online_exams';
                } else {
                    $validateddata->open_module = 'year_book_mocktest';
                }
                // $validateddata->open_module = $validateddata->examtype==1 ? 'year_book' :'online_exams';
                $validateddata->format = $validateddata->format;
                // $validateddata->open_subdepartment = $open_subdepartment;
                $validateddata->startdate = time();
                $validateddata->enddate = 0;
                $validateddata->open_coursetype = 1;
                if(isset($validateddata->concatshortname) && !empty($validateddata->concatshortname)){
                    $validateddata->shortname = $validateddata->concatshortname.'_'.$validateddata->shortname;
                }
                $examid = create_course($validateddata, $editoroptions);
               // Update course tags.
                if (isset($validateddata->tags)) {
                    $coursecontext = context_course::instance($examid->id, MUST_EXIST);
                    local_tags_tag::set_item_tags('local_courses', 'courses', $examid->id, $coursecontext, $validateddata->tags, 0, $data['open_path'], $validateddata->open_departmentid);
                }
                if ($validateddata->format == 'singleactivity') {
                    $quiz= (object) $quiz;
                    $quiz->course = $examid->id;
                    $quiz->grademethod = $validateddata->grademethod;
                    $quiz->grade = $validateddata->gradepass;
                    $quiz->gradepass = $validateddata->gradepass;
                    $quiz->name = $validateddata->fullname;
                    $quiz->timeopen = $validateddata->timeopen;
                    $quiz->timeclose = $validateddata->timeclose;
                    $quiz->timelimit = $validateddata->timelimit;

                    if (!empty($validateddata->summary_editor['text']))
                        $quiz->introeditor['text'] = $validateddata->summary_editor['text'];
                    else
                        $quiz->introeditor['text'] = $validateddata->fullname;

                    $quiz->introeditor['format'] = $validateddata->summary_editor['format'];
                    $quiz->completion= 2;
                    $quiz->completionusegrade = 1;
                    $quiz->completionpassgrade = 1;

                    $quiz = add_onlineexam_quiz($validateddata, $examid);
                    add_moduleinfo($quiz, $examid);
                }

            } elseif ($validateddata->id > 0) {
                list($zero, $org, $ctr, $bu, $cu, $territory) = explode("/", $open_path);
                $validateddata->open_coursetype = 1;
                $coursedata = $DB->get_record('course', array('id' => $data['id']));
                if ($form_status == 0) {
                    $examid = (object) $examid;
                    $examid->id = $data['id'];
                    $validateddata->category = $category_id;
                    if ($validateddata->examtype==1) {
                        $validateddata->open_module = 'year_book';
                    } else if ($validateddata->examtype==0) {
                        $validateddata->open_module = 'online_exams';
                    } else {
                        $validateddata->open_module = 'year_book_mocktest';
                    }
                    // $validateddata->open_module = $validateddata->examtype == 1 ? 'year_book' :'online_exams';
                    update_course($validateddata, $editoroptions);

                    // purge appropriate caches in case fix_course_sortorder() did not change anything
                    cache_helper::purge_by_event('changesincourse');
                    cache_helper::purge_by_event('changesincoursecat');

                    //update Quizz
                    if ($validateddata->format == 'singleactivity') {
                        $quiz = update_onlineexam_quiz($validateddata, $data, $form_status);
                        if ($quiz->id > 0) {
                            $cm = get_coursemodule_from_instance('quiz', $quiz->id, $data['id'], false, MUST_EXIST);
                            $quiz->coursemodule = $cm->id;
                            $updated = update_moduleinfo($cm, $quiz, $coursedata, null);

                        } else {
                            $quiz= (object) $quiz;
                            $quiz->course = $examid->id;
                            $quiz->grademethod = $validateddata->grademethod;
                            $quiz->grade = $validateddata->gradepass;
                            $quiz->gradepass = $validateddata->gradepass;
                            $quiz->name = $validateddata->fullname;
                            $quiz->timeopen = $validateddata->timeopen;
                            $quiz->timeclose = $validateddata->timeclose;
                            $quiz->timelimit = $validateddata->timelimit;

                            if (!empty($validateddata->summary_editor['text']))
                                $quiz->introeditor['text'] = $validateddata->summary_editor['text'];
                            else
                                $quiz->introeditor['text'] = $validateddata->fullname;

                            $quiz->introeditor['format'] = $validateddata->summary_editor['format'];
                            $quiz->completion= 2;
                            $quiz->completionusegrade = 1;
                            $quiz->completionpassgrade = 1;

                            $quiz = add_onlineexam_quiz($validateddata, $examid);
                            add_moduleinfo($quiz, $examid);
                        }
                    }
                } else {

                    $data = (object)$data;
                    $examid = new stdClass();
                    $examid->id = $data->id;

                    update_course($data);
                    if ($form_status == 1 && $validateddata->format == 'singleactivity') {
                        $quiz = update_onlineexam_quiz($validateddata, $data, $form_status);
                        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $data->id, false, MUST_EXIST);
                        $quiz->coursemodule = $cm->id;
                        $updated = update_moduleinfo($cm, $quiz, $coursedata, null);
                    }
                    if ($form_status == 2) {

                        // local_onlineexams_get_costcenter_path($data);

                        // if ($data->open_path) {
                        //     $data->category = $DB->get_field('local_onlineexams', 'category', array('path' => $data->open_path));
                        // }
                    } else {
                        if ($validateddata->map_certificate == 1) {

                            $data->open_certificateid = $validateddata->open_certificateid;
                        } else {

                            $data->open_certificateid = null;
                        }
                    }

                    // purge appropriate caches in case fix_course_sortorder() did not change anything
                    cache_helper::purge_by_event('changesincourse');
                    cache_helper::purge_by_event('changesincoursecat');
                }
            }
            $next = $form_status + 1;
            $nextform = array_key_exists($next, $formheaders);
            if ($nextform !== false) {
                $form_status = $next;
                $error = false;
            } else {
                $form_status = -1;
                $error = true;
            }
            $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $examid->id, 'enrol' => 'manual'));
            // $existing_method = $DB->get_record('enrol', array('courseid' => $examid->id, 'enrol' => 'self'));
            // if ($courseenrolid == 1) {
            //     $existing_method->status = 0;
            //     $existing_method->customint6 = 1;
            // } else {
            //     $existing_method->status = 1;
            // }
            // $DB->update_record('enrol', $existing_method);
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
        $return = array(
            'courseid' => $examid->id,
            'enrolid' => $enrolid,
            'form_status' => $form_status
        );

        return $return;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_onlineexams_form_returns()
    {
        return new external_single_structure(array(
            'courseid' => new external_value(PARAM_INT, 'Exam id'),
            'enrolid' => new external_value(PARAM_INT, 'manual enrol id for the course'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
     */
    public static function onlineexams_view_parameters()
    {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }

    /**
     * lists all courses
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array courses list.
     */
    public static function onlineexams_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::onlineexams_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_onlineexams($stable, $filtervalues,$options);
        $totalcount = $data['totalcourses'];
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'is_siteadmin'=>$data['is_siteadmin'],
            'records' => $data,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function onlineexams_view_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'filterdata'),
            'length' => new external_value(PARAM_RAW, 'length'),
            'is_siteadmin' => new external_value(PARAM_BOOL, 'is_siteadmin', VALUE_OPTIONAL),                                
            'records' => new external_single_structure(
                array(
                    'hascourses' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'coursename' => new external_value(PARAM_RAW, 'coursename'),
                                'coursenameCut' => new external_value(PARAM_RAW, 'coursenameCut', VALUE_OPTIONAL),
                                'catname' => new external_value(PARAM_RAW, 'catname'),
                                'shortname' => new external_value(PARAM_RAW, 'shortname'),
                                'catnamestring' => new external_value(PARAM_RAW, 'catnamestring'),
                                //'courseimage' => new external_value(PARAM_RAW, 'catnamestring'),
                                'enrolled_count' => new external_value(PARAM_INT, 'enrolled_count', VALUE_OPTIONAL),
                                'courseid' => new external_value(PARAM_INT, 'courseid'),
                                'completed_count' => new external_value(PARAM_INT, 'completed_count', VALUE_OPTIONAL),
                                // 'points' => new external_value(PARAM_INT, 'points', VALUE_OPTIONAL),
                                'coursetype' => new external_value(PARAM_RAW, 'coursetype', VALUE_OPTIONAL),
                                // 'onlineexamsummary' => new external_value(PARAM_RAW, 'onlineexamsummary', VALUE_OPTIONAL),
                                'courseurl' => new external_value(PARAM_RAW, 'courseurl', VALUE_OPTIONAL),
                                'enrollusers' => new external_value(PARAM_RAW, 'enrollusers', VALUE_OPTIONAL),
                                // 'enrolledusers' => new external_value(PARAM_RAW, 'enrolledusers', VALUE_OPTIONAL),
                                'editcourse' => new external_value(PARAM_RAW, 'editcourse', VALUE_OPTIONAL),
                                'update_status' => new external_value(PARAM_RAW, 'update_status', VALUE_OPTIONAL),
                                // 'course_class' => new external_value(PARAM_TEXT, 'course_status', VALUE_OPTIONAL),
                                'deleteaction' => new external_value(PARAM_RAW, 'designation', VALUE_OPTIONAL),
                                'deleteenable' => new external_value(PARAM_RAW, 'deleteenable', VALUE_OPTIONAL),
                                // 'grader' => new external_value(PARAM_RAW, 'grader', VALUE_OPTIONAL),
                                // 'activity' => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                                // 'requestlink' => new external_value(PARAM_RAW, 'requestlink', VALUE_OPTIONAL),
                                // 'skillname' => new external_value(PARAM_RAW, 'skillname', VALUE_OPTIONAL),
                                // 'attemptreport' => new external_value(PARAM_RAW, 'attemptreport', VALUE_OPTIONAL),
                                // 'analyticsreport' => new external_value(PARAM_RAW, 'analyticsreport', VALUE_OPTIONAL),
                                // 'importquestions' => new external_value(PARAM_RAW, 'importquestions', VALUE_OPTIONAL),
                                // 'ratings_value' => new external_value(PARAM_RAW, 'ratings_value', VALUE_OPTIONAL),
                                // 'ratingenable' => new external_value(PARAM_BOOL, 'ratingenable', VALUE_OPTIONAL),
                                // 'tagstring' => new external_value(PARAM_RAW, 'tagstring', VALUE_OPTIONAL),
                                // 'tagenable' => new external_value(PARAM_BOOL, 'tagenable', VALUE_OPTIONAL),
                                // 'report_view' => new external_value(PARAM_BOOL, 'report_view', VALUE_OPTIONAL),
                                // 'grade_view' => new external_value(PARAM_BOOL, 'grade_view', VALUE_OPTIONAL),
                                'delete' => new external_value(PARAM_BOOL, 'delete', VALUE_OPTIONAL),
                                'update' => new external_value(PARAM_BOOL, 'update', VALUE_OPTIONAL),
                                'enrol' => new external_value(PARAM_BOOL, 'enrol', VALUE_OPTIONAL),
                                'enrolled' => new external_value(PARAM_BOOL, 'enrolled', VALUE_OPTIONAL),
                                'actions' => new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL),
                                // 'examfromdate' => new external_value(PARAM_RAW, 'examfromdate', VALUE_OPTIONAL),
                                // 'examtodate' => new external_value(PARAM_RAW, 'examtodate', VALUE_OPTIONAL),
                                // 'passgrade' => new external_value(PARAM_RAW, 'passgrade', VALUE_OPTIONAL),
                                // 'maxgrade' => new external_value(PARAM_RAW, 'maxgrade', VALUE_OPTIONAL),
                                // 'fullonlineexamsummary' => new external_value(PARAM_RAW, 'fullonlineexamsummary', VALUE_OPTIONAL),                                
                                'course_module' => new external_value(PARAM_RAW, 'course_module', VALUE_OPTIONAL),                                
                                'is_siteadmin' => new external_value(PARAM_BOOL, 'is_siteadmin', VALUE_OPTIONAL),
                                'timelimit' => new external_value(PARAM_RAW, 'timelimit', VALUE_OPTIONAL),
                                'noofquestion' => new external_value(PARAM_RAW, 'timelimit', VALUE_OPTIONAL),
                                'marks' => new external_value(PARAM_RAW, 'marks', VALUE_OPTIONAL),
                            )
                        )
                    ),
                    //  'request_view' => new external_value(PARAM_INT, 'request_view', VALUE_OPTIONAL),

                    'nocourses' => new external_value(PARAM_BOOL, 'nocourses', VALUE_OPTIONAL),
                    'totalcourses' => new external_value(PARAM_INT, 'totalcourses', VALUE_OPTIONAL),
                    'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                )
            )

        ]);
    }
    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
     */
    public static function delete_onlineexams_parameters()
    {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
            )
        );
    }

    /**
     * Deletes course
     *
     * @param int $action
     * @param int $confirm
     * @param int $id course id
     * @param string $name
     * @return int new course id.
     */
    public static function delete_onlineexams($action, $id, $confirm, $name)
    {
        global $DB;
        try {
            if ($confirm) {
                $corcat = $DB->get_field('course', 'category', array('id' => $id));
                $category = $DB->get_record('course_categories', array('id' => $corcat));
                delete_course($id, false);
                $category->coursecount = $category->coursecount - 1;
                $DB->update_record('course_categories', $category);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function delete_onlineexams_returns()
    {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function course_update_status_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'The survey id for wellness'),
                'name' => new external_value(PARAM_RAW, 'optional parameter for default application'),
                'onlineexamsstatus' => new external_value(PARAM_RAW, 'online exam status'),
                'action' => new external_value(PARAM_RAW, 'action to perform'),
                'confirm' => new external_value(PARAM_RAW, 'confirm status'),
                'status' => new external_value(PARAM_RAW, 'status'),
                )
        );
    }
    public static function course_update_status($id, $name, $onlineexamsstatus,$action,$confirm,$status)
    {
        global $DB;
        $params = self::validate_parameters(
            self::course_update_status_parameters(),
            ['id' => $id, 'name' => $name, 'onlineexamsstatus' => $onlineexamsstatus,'action' => $action, 'confirm' => $confirm,'status' => $status ]
        );
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $course = $DB->get_record('course', array('id' => $id), 'id, visible');
        $course->visible = $course->visible ? 0 : 1;
        $course->timemodified = time();
        $return = $DB->update_record('course', $course);
        return $return;
    }
    public static function course_update_status_returns()
    {
        return new external_value(PARAM_BOOL, 'Status');
    }
    public static function data_for_onlineexams_parameters()
    {
        $filter = new external_value(PARAM_TEXT, 'Filter text');
        $filter_text = new external_value(PARAM_TEXT, 'Filter name', VALUE_OPTIONAL);
        $filter_offset = new external_value(PARAM_INT, 'Offset value', VALUE_OPTIONAL);
        $filter_limit = new external_value(PARAM_INT, 'Limit value', VALUE_OPTIONAL);
        $params = array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        );
        return new external_function_parameters($params);
    }
    public static function data_for_onlineexams($filter, $filter_text = '', $filter_offset = 0, $filter_limit = 0)
    {
        global $PAGE;

        $params = self::validate_parameters(self::data_for_onlineexams_parameters(), array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        ));
        $PAGE->set_context((new \local_onlineexams\lib\accesslib())::get_module_context());
        $renderable = new local_onlineexams\output\userdashboard($params['filter'], $params['filter_text'], $params['filter_offset'], $params['filter_limit']);
        $output = $PAGE->get_renderer('local_onlineexams');
        $data = $renderable->export_for_template($output);
        return $data;
    }
    public static function data_for_onlineexams_returns()
    {
        $return  = new external_single_structure(array(
            'total' => new external_value(PARAM_INT, 'Number of enrolled onlineexams.', VALUE_OPTIONAL),
            'inprogresscount' =>  new external_value(PARAM_INT, 'Number of inprogress course count.'),
            'completedcount' =>  new external_value(PARAM_INT, 'Number of complete course count.'),
            'onlineexams_view_count' =>  new external_value(PARAM_INT, 'Number of onlineexams count.'),
            'enableslider' =>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
            'inprogress_elearning_available' =>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
            'course_count_view' =>  new external_value(PARAM_TEXT, 'to add course count class'),
            'functionname' => new external_value(PARAM_TEXT, 'Function name'),
            'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
            'elearningtemplate' => new external_value(PARAM_INT, 'template name', VALUE_OPTIONAL),
            'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
            'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, true),
            'moduledetails' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                        'lastaccessdate' => new external_value(PARAM_RAW, 'Last access Time'),
                        'course_image_url' => new external_value(PARAM_RAW, 'Course Image'),
                        'onlineexamsummary' => new external_value(PARAM_RAW, 'Course Summary'),
                        'fullonlineexamsummary' => new external_value(PARAM_RAW, 'full onlineexams Summary', VALUE_OPTIONAL),
                        'progress' => new external_value(PARAM_RAW, 'Course Progress'),
                        'progress_bar_width' => new external_value(PARAM_RAW, 'Course Progress bar width'),
                        'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                        'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                        'course_url' => new external_value(PARAM_RAW, 'Course Url'),
                        'inprogress_coursename_fullname' => new external_value(PARAM_RAW, 'Course Url'),
                        'rating_element' => new external_value(PARAM_RAW, 'Ratings'),
                        'element_tags' => new external_value(PARAM_RAW, 'Course Tags', VALUE_OPTIONAL),
                        'index' => new external_value(PARAM_INT, 'Index of Card'),
                        'course_completedon' => new external_value(PARAM_RAW, 'course_completedon'),
                        'label_name' => new external_value(PARAM_RAW, 'course_completedon'),
                    )
                )
            ),
            'viewMoreCard' => new external_value(PARAM_BOOL, 'More info card to display', false),
            'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
            'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
            'index' => new external_value(PARAM_INT, 'number of onlineexams count'),
            'filter_text' => new external_value(PARAM_TEXT, 'filtertext content', VALUE_OPTIONAL),
            'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
            'templatename' => new external_value(PARAM_TEXT, 'Templatename for tab content'),
            'pluginname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
            'tabname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
            'status' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
            'enrolled_url' => new external_value(PARAM_URL, 'view_more_url for tab'), //added revathi
            'inprogress_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
            'completed_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
        ));
        return $return;
    }
    public static function data_for_onlineexams_paginated_parameters()
    {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }
    public static function data_for_onlineexams_paginated($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_url('/local/onlineexams/userdashboard.php', array());
        $PAGE->set_context($contextid);

        $decodedoptions = (array)json_decode($options);
        $decodedfilter = (array)json_decode($filterdata);
        $filter = $decodedoptions['filter'];
        $filter_text = isset($decodedfilter['search_query']) ? $decodedfilter['search_query'] : '';
        $filter_offset = $offset;
        $filter_limit = $limit;

        $renderable = new local_onlineexams\output\userdashboard($filter, $filter_text, $filter_offset, $filter_limit);
        $output = $PAGE->get_renderer('local_onlineexams');
        $data = $renderable->export_for_template($output);
        $totalcount = $renderable->onlineexamsViewCount;
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' => array($data),
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }
    public static function data_for_onlineexams_paginated_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'total' => new external_value(PARAM_INT, 'Number of enrolled onlineexams.', VALUE_OPTIONAL),
                        'inprogresscount' =>  new external_value(PARAM_INT, 'Number of inprogress course count.'),
                        'completedcount' =>  new external_value(PARAM_INT, 'Number of complete course count.'),
                        'onlineexams_view_count' =>  new external_value(PARAM_INT, 'Number of onlineexams count.'),

                        'inprogress_elearning_available' =>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
                        'course_count_view' =>  new external_value(PARAM_TEXT, 'to add course count class'),
                        'functionname' => new external_value(PARAM_TEXT, 'Function name'),
                        'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
                        'elearningtemplate' => new external_value(PARAM_INT, 'template name', VALUE_OPTIONAL),
                        'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
                        'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, false),
                        'moduledetails' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                                    'lastaccessdate' => new external_value(PARAM_RAW, 'Last access Time'),
                                    'course_image_url' => new external_value(PARAM_RAW, 'Course Image'),
                                    'onlineexamsummary' => new external_value(PARAM_RAW, 'Course Summary'),
                                    'fullonlineexamsummary' => new external_value(PARAM_RAW, 'full onlineexams Summary', VALUE_OPTIONAL),
                                    'progress' => new external_value(PARAM_RAW, 'Course Progress'),
                                    'progress_bar_width' => new external_value(PARAM_RAW, 'Course Progress bar width'),
                                    'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                                    'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                                    'course_url' => new external_value(PARAM_RAW, 'Course Url'),
                                    'inprogress_coursename_fullname' => new external_value(PARAM_RAW, 'Course Url'),
                                    'rating_element' => new external_value(PARAM_RAW, 'Ratings'),
                                    'element_tags' => new external_value(PARAM_RAW, 'Course Tags', VALUE_OPTIONAL),
                                    'index' => new external_value(PARAM_INT, 'Index of Card'),
                                    'course_completedon' => new external_value(PARAM_RAW, 'course_completedon'),
                                    'label_name' => new external_value(PARAM_RAW, 'course_completedon'),
                                )
                            )
                        ),
                        'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
                        'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
                        'index' => new external_value(PARAM_INT, 'number of onlineexams count'),
                        'filter_text' => new external_value(PARAM_TEXT, 'filtertext content', VALUE_OPTIONAL),
                        'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
                        'templatename' => new external_value(PARAM_TEXT, 'Templatename for tab content'),
                        'pluginname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
                        'tabname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
                        'status' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_onlineexams'),
                    )
                )
            )
        ]);
    }
    public static function get_users_onlineexams_information_parameters() {
        return new external_function_parameters(
            array('status' => new external_value(PARAM_RAW, 'status of course', true),
                'searchterm' => new external_value(PARAM_RAW, 'searchterm', VALUE_OPTIONAL),
                'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 15),
                'source' => new external_value(PARAM_TEXT, 'Parameter to validate the mobile ', VALUE_OPTIONAL,'mobile')
            )
        );
    }
    public static function get_users_onlineexams_information($status, $searchterm = '', $page = 0, $perpage = 15, $source = 'mobile') {
        global $USER, $DB,$CFG;
        require_once($CFG->dirroot.'/local/ratings/lib.php');
        $result = array();
        if ($status == 'completed') {
            $user_course_info = general_lib::completed_onlineexamnames($searchterm, $page * $perpage, $perpage, $source);
            $total = general_lib::completed_onlineexamnames_count($searchterm, $source);
        } else if ($status == 'inprogress') {
            $user_course_info = general_lib::inprogress_onlineexamnames($searchterm, $page * $perpage, $perpage, $source);
            $total = general_lib::inprogress_onlineexamnames_count($searchterm, $source);
        } else if($status == 'enrolled') {
            if ($page == -1) {
                $page = 0;
                $perpage = 0;
            }
            $user_course_info = general_lib::enrolled_onlineexamnames($searchterm, $page * $perpage, $perpage, $source);
            $total = general_lib::enrolled_onlineexamnames_count($searchterm, $source);
        }

        foreach ($user_course_info as $userinfo) {
            //course image
            if(file_exists($CFG->dirroot.'/local/includes.php')){
                require_once($CFG->dirroot.'/local/includes.php');
                $includes = new user_course_details();
                $courseimage = $includes->course_summary_files($userinfo);                
                if(is_object($courseimage)){
                    $courseimage = $courseimage->out();                    
                }else{
                    $courseimage = $courseimage;
                }                
            } 
            $module = $DB->get_record('quiz', array('course'=>$userinfo->id));
            $cm = get_coursemodule_from_instance('quiz', $module->id, 0, false, MUST_EXIST);
            $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$module->id, 'itemmodule'=>'quiz', 'courseid'=>$userinfo->id));
            $sql="SELECT * FROM {quiz_attempts} where id=(SELECT max(id) id from {quiz_attempts} where userid={$USER->id} and quiz={$module->id})";
            $userattempt = $DB->get_record_sql($sql);
            $attempts = ($userattempt->attempt) ? $userattempt->attempt : 0;
            $grademax = ($gradeitem->grademax) ? round($gradeitem->grademax): '-';
            $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass): '-';
            if ($gradeitem->id)
            $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = $gradeitem->id AND userid = $USER->id");
            if ($usergrade) {
                $mygrade = round($usergrade->finalgrade, 2);
                if ($usergrade->finalgrade >= $gradepass) {
                    $completedon = date("j M 'Y", $usergrade->timemodified);
                    $status = 'Completed';
                    $can_review = 1;
                } else {
                    $status = 'Incomplete';
                    $completedon = '-';
                }

            } else {
                $mygrade = 0;
                $status = 'Pending';
                $completedon = '-';
                $attempts = 0;
            }
            $context = context_course::instance($userinfo->id, IGNORE_MISSING);
            list($userinfo->summary,$userinfo->summaryformat) =
                external_format_text($userinfo->summary ,$userinfo->summaryformat , $context->id, 'course', 'summary', null);
                $progress = null;
            // Return only private information if the user should be able to see it.
            if ($userinfo->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userinfo, $userid);
            }
            $modulerating = $DB->get_field('local_ratings_likes', 'module_rating', array('module_id' => $userinfo->id, 'module_area' => 'local_courses'));
            if(!$modulerating){
                 $modulerating = 0;
            }
            $likes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$userinfo->id, 'likestatus'=>'1'));
            $dislikes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$userinfo->id, 'likestatus'=>'2'));
            $avgratings = get_rating($userinfo->id, 'local_courses');
            $avgrating = $avgratings->avg;
            $ratingusers = $avgratings->count;
            $certificateid = $DB->get_field('tool_certificate_issues', 'code', array('userid' => $USER->id, 'moduletype' => 'course', 'moduleid' => $userinfo->id));
            if(!$certificateid){
                $certificateid = null;
            }
            $result[] = array(
                'id' => $userinfo->id,
                'fullname' => $userinfo->fullname,
                'shortname' => $userinfo->shortname,
                'summary' => $userinfo->summary,
                'summaryformat' => $userinfo->summaryformat,
                'startdate' => $userinfo->startdate,
                'enddate' => $userinfo->enddate,
                'timecreated' => $userinfo->timecreated,
                'timemodified' => $userinfo->timemodified,
                'visible' => $userinfo->visible,
                'idnumber' => $userinfo->idnumber,
                'format' => $userinfo->format,
                'showgrades' => $userinfo->showgrades,
                'modname' => 'quiz',
                'modplural' => 'Quizzes',
                'maxgrade' => $grademax,
                'enrolledon' => $enrolledon,
                'completedon' => $completedon,
                'status' =>$status,
                'canreview' => $can_review,
                'timeopen' => $userinfo->timeopen,
                'timeclose' =>$userinfo->timeclose,
                'userattemptid'=> $userattempt->id,
                'url' => $CFG->wwwroot .'/mod/quiz/view.php?id='. $cm->id .'',
                'passgrade' => $gradepass,
                'mygrade' => $mygrade,
                'attempts' => $attempts,
                'lang' => clean_param($userinfo->lang,PARAM_LANG),
                'enablecompletion' => $userinfo->enablecompletion,
                'category' => $userinfo->category,
                'progress' => $progress,
                'rating' => $modulerating,
                'avgrating' => $avgrating,
                'ratingusers' => $ratingusers,
                'likes' => $likes,
                'dislikes' => $dislikes,
                'certificateid' => $certificateid,
                'examimage' => $courseimage
            );
        }
        if ($total > $perpage) {
            $maxPages = ceil($total/$perpage);
        } else {
            $maxPages = 1;
        }
        return array('modules' => $result, 'total' => $total);
    }
    public static function get_users_onlineexams_information_returns(){
        return new external_single_structure(
            array(
                'modules' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'=> new external_value(PARAM_INT, 'id of course'),
                            'fullname'=> new external_value(PARAM_RAW, 'fullname of course'),
                            'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                            'summary' => new external_value(PARAM_RAW, 'course summary'),
                            'summaryformat' => new external_value(PARAM_RAW, 'course summary format'),
                            'startdate' => new external_value(PARAM_RAW, 'startdate of course'),
                            'enddate' => new external_value(PARAM_RAW, 'enddate of course'),
                            'timecreated' => new external_value(PARAM_RAW, 'course create time'),
                            'timemodified' => new external_value(PARAM_RAW, 'course modified time'),
                            'visible' => new external_value(PARAM_RAW, 'course status'),
                            'idnumber' => new external_value(PARAM_RAW, 'course idnumber'),
                            'modname' => new external_value(PARAM_RAW, 'activity module type'),
                            'modplural' => new external_value(PARAM_RAW, 'activity module plural name'),
                            'maxgrade' => new external_value(PARAM_RAW, 'activity max grade'),
                            'status' =>new external_value(PARAM_RAW, 'activity status'),
                            'canreview' => new external_value(PARAM_RAW, 'activity review'),
                            'userattemptid'=> new external_value(PARAM_RAW, 'activity attempt id'),
                            'url' => new external_value(PARAM_RAW, 'module url'),
                            'passgrade' =>new external_value(PARAM_RAW, 'activity pass grade'),
                            'mygrade' => new external_value(PARAM_RAW, 'activity user grade'),
                            'attempts' => new external_value(PARAM_RAW, 'activity attempts'),
                            'showgrades' => new external_value(PARAM_RAW, 'course grade status'),
                            'lang' => new external_value(PARAM_RAW, 'course language'),
                            'enablecompletion' => new external_value(PARAM_RAW, 'course completion'),
                            'category' => new external_value(PARAM_RAW, 'course category'),
                            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage'),
                            'rating' => new external_value(PARAM_RAW, 'Course rating'),
                            'avgrating' => new external_value(PARAM_FLOAT, 'Course Avg rating'),
                            'ratingusers' => new external_value(PARAM_INT, 'Course rating users'),
                            'likes' => new external_value(PARAM_INT, 'Course Likes'),
                            'dislikes' => new external_value(PARAM_INT, 'Course Dislikes'),
                            'certificateid' => new external_value(PARAM_RAW, 'Certifictate Code', VALUE_OPTIONAL),
                            'examimage' => new external_value(PARAM_RAW, 'Courseimage', VALUE_OPTIONAL),
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total Pages')
            )
        );
    }
}
