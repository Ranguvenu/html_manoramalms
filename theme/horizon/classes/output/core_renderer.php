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

namespace theme_horizon\output;

use moodle_url;
use html_writer;
use get_string;
use context_course;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_horizon
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer {

    /**
     * Returns HTML to display a "Turn editing on/off" button in a form.
     *
     * @param moodle_url $url The URL + params to send through when clicking the button
     * @param string $method
     * @return string HTML the button
     */
    public function edit_button(moodle_url $url, string $method = 'post') {
        if ($this->page->theme->haseditswitch) {
            return;
        }
        $url->param('sesskey', sesskey());
        if ($this->page->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('turneditingon');
        }
        $button = new \single_button($url, $editstring, $method, ['class' => 'btn btn-primary']);
        return $this->render_single_button($button);
    }

    /**
     * Renders the "breadcrumb" for all pages in horizon.
     *
     * @return string the HTML for the navbar.
     */
    public function navbar(): string {
        $newnav = new \theme_horizon\horizonnavbar($this->page);
        return $this->render_from_template('core/navbar', $newnav);
    }

    /**
     * Renders the context header for the page.
     *
     * @param array $headerinfo Heading information.
     * @param int $headinglevel What 'h' level to make the heading.
     * @return string A rendered context header.
     */
    public function context_header($headerinfo = null, $headinglevel = 1): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $context = $this->page->context;
        $heading = null;
        $imagedata = null;
        $userbuttons = null;

        // Make sure to use the heading if it has been set.
        if (isset($headerinfo['heading'])) {
            $heading = $headerinfo['heading'];
        } else {
            $heading = $this->page->heading;
        }

        // The user context currently has images and buttons. Other contexts may follow.
        if ((isset($headerinfo['user']) || $context->contextlevel == CONTEXT_USER) && $this->page->pagetype !== 'my-index') {
            if (isset($headerinfo['user'])) {
                $user = $headerinfo['user'];
            } else {
                // Look up the user information if it is not supplied.
                $user = $DB->get_record('user', array('id' => $context->instanceid));
            }

            // If the user context is set, then use that for capability checks.
            if (isset($headerinfo['usercontext'])) {
                $context = $headerinfo['usercontext'];
            }

            // Only provide user information if the user is the current user, or a user which the current user can view.
            // When checking user_can_view_profile(), either:
            // If the page context is course, check the course context (from the page object) or;
            // If page context is NOT course, then check across all courses.
            $course = ($this->page->context->contextlevel == CONTEXT_COURSE) ? $this->page->course : null;

            if (user_can_view_profile($user, $course)) {
                // Use the user's full name if the heading isn't set.
                if (empty($heading)) {
                    $heading = fullname($user);
                }

                $imagedata = $this->user_picture($user, array('size' => 100));

                // Check to see if we should be displaying a message button.
                if (!empty($CFG->messaging) && has_capability('moodle/site:sendmessage', $context)) {
                    $userbuttons = array(
                        'messages' => array(
                            'buttontype' => 'message',
                            'title' => get_string('message', 'message'),
                            'url' => new moodle_url('/message/index.php', array('id' => $user->id)),
                            'image' => 'message',
                            'linkattributes' => \core_message\helper::messageuser_link_params($user->id),
                            'page' => $this->page
                        )
                    );

                    if ($USER->id != $user->id) {
                        $iscontact = \core_message\api::is_contact($USER->id, $user->id);
                        $contacttitle = $iscontact ? 'removefromyourcontacts' : 'addtoyourcontacts';
                        $contacturlaction = $iscontact ? 'removecontact' : 'addcontact';
                        $contactimage = $iscontact ? 'removecontact' : 'addcontact';
                        $userbuttons['togglecontact'] = array(
                                'buttontype' => 'togglecontact',
                                'title' => get_string($contacttitle, 'message'),
                                'url' => new moodle_url('/message/index.php', array(
                                        'user1' => $USER->id,
                                        'user2' => $user->id,
                                        $contacturlaction => $user->id,
                                        'sesskey' => sesskey())
                                ),
                                'image' => $contactimage,
                                'linkattributes' => \core_message\helper::togglecontact_link_params($user, $iscontact),
                                'page' => $this->page
                            );
                    }

                    $this->page->requires->string_for_js('changesmadereallygoaway', 'moodle');
                }
            } else {
                $heading = null;
            }
        }

        $prefix = null;
        if ($context->contextlevel == CONTEXT_MODULE) {
            if ($this->page->course->format === 'singleactivity') {
                $heading = format_string($this->page->course->fullname, true, ['context' => $context]);
            } else {
                $heading = $this->page->cm->get_formatted_name();
                $iconurl = $this->page->cm->get_icon_url();
                $iconclass = $iconurl->get_param('filtericon') ? '' : 'nofilter';
                $iconattrs = [
                    'class' => "icon activityicon $iconclass",
                    'aria-hidden' => 'true'
                ];
                $imagedata = html_writer::img($iconurl->out(false), '', $iconattrs);
                $purposeclass = plugin_supports('mod', $this->page->activityname, FEATURE_MOD_PURPOSE);
                $purposeclass .= ' activityiconcontainer';
                $purposeclass .= ' modicon_' . $this->page->activityname;
                $imagedata = html_writer::tag('div', $imagedata, ['class' => $purposeclass]);
                if (!empty($USER->editing)) {
                    $prefix = get_string('modulename', $this->page->activityname);
                }
            }
        }

        $contextheader = new \context_header($heading, $headinglevel, $imagedata, $userbuttons, $prefix);
        return $this->render_context_header($contextheader);
    }

     /**
      * Renders the header bar.
      *
      * @param context_header $contextheader Header bar object.
      * @return string HTML for the header bar.
      */
    protected function render_context_header(\context_header $contextheader) {

        // Generate the heading first and before everything else as we might have to do an early return.
        if (!isset($contextheader->heading)) {
            $heading = $this->heading($this->page->heading, $contextheader->headinglevel, 'h2');
        } else {
            $heading = $this->heading($contextheader->heading, $contextheader->headinglevel, 'h2');
        }

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image mr-2');
        }

        // Headings.
        if (isset($contextheader->prefix)) {
            $prefix = html_writer::div($contextheader->prefix, 'text-muted text-uppercase small line-height-3');
            $heading = $prefix . $heading;
        }
        $html .= html_writer::tag('div', $heading, array('class' => 'page-header-headings'));

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'togglecontact') {
                        \core_message\helper::togglecontact_requirejs();
                    }
                    if ($button['buttontype'] === 'message') {
                        \core_message\helper::messageuser_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * See if this is the first view of the current cm in the session if it has fake blocks.
     *
     * (We track up to 100 cms so as not to overflow the session.)
     * This is done for drawer regions containing fake blocks so we can show blocks automatically.
     *
     * @return boolean true if the page has fakeblocks and this is the first visit.
     */
    public function firstview_fakeblocks(): bool {
        global $SESSION;

        $firstview = false;
        if ($this->page->cm) {
            if (!$this->page->blocks->region_has_fakeblocks('side-pre')) {
                return false;
            }
            if (!property_exists($SESSION, 'firstview_fakeblocks')) {
                $SESSION->firstview_fakeblocks = [];
            }
            if (array_key_exists($this->page->cm->id, $SESSION->firstview_fakeblocks)) {
                $firstview = false;
            } else {
                $SESSION->firstview_fakeblocks[$this->page->cm->id] = true;
                $firstview = true;
                if (count($SESSION->firstview_fakeblocks) > 100) {
                    array_shift($SESSION->firstview_fakeblocks);
                }
            }
        }
        return $firstview;
    }

    public function get_left_navigation() {
        global $USER,$DB,$CFG;
        $systemcontext = \context_system::instance();
        $user_picture = new \user_picture($USER, array('size' => 80, 'class' => 'sidebaruserimg', 'link'=>false));
        $user_picture = $user_picture->get_url($this->page);
        $userimg = $user_picture->out();
        $pcategory = $DB->get_field_sql("SELECT id from {question_categories} WHERE idnumber = 'local_questions_categories'");
        $category = $pcategory.','.$systemcontext->id;
        $links = [];
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $pageurl = "https";
        } else {
            $pageurl = "http";
        }
        $pageurl .= "://";
        $pageurl .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $listclass = 'menu_list_item';
        $linkspanclass = 'sidebaricon';
        $spanclass = 'sidebarname';
        $linkclass = 'sidebarlink';
        $listid = 'reports';
        if(is_siteadmin()){
            if ($pageurl === $CFG->wwwroot.'/blocks/reportdashboard/reports.php' && $listid == 'dashboard') {
                $listclass1 = $listclass.' active';
            } else {
                $listclass1 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass1,
                'listid' => 'dashboard',
                'link' => $CFG->wwwroot.'/blocks/reportdashboard/reports.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('dashboard', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'dashboardicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('dashboard', 'theme_horizon'),
            ];
        } else {
            if ($pageurl === $CFG->wwwroot.'/my/' || $pageurl === $CFG->wwwroot.'/my/index.php') {
                $listclass2 = $listclass.' active';
            } else {
                $listclass2 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass2,
                'listid' => 'mycourses',
                'link' => $CFG->wwwroot,
                'linkclass' => $linkclass,
                'linktitle' => get_string('mycourses', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'mycoursesicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('mycourses', 'theme_horizon'),
            ];
        }
        if ($pageurl === $CFG->wwwroot.'/blocks/notification/calendar.php') {
            $listclass3 = $listclass.' active';
        } else {
            $listclass3 = $listclass.' inactive';
        }
        $links[] = [
                'listclass' => $listclass3,
                'listid' => 'mycalendar',
                'link' => $CFG->wwwroot . '/blocks/notification/calendar.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('calendar', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'mycalendaricon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('calendar', 'theme_horizon'),
            ];
        if(is_siteadmin() || has_capability('local/questions:questionhierarchy', \context_system::instance())){
            if ($pageurl === $CFG->wwwroot.'/local/units/index.php') {
                $listclass4 = $listclass.' active';
            } else {
                $listclass4 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass4,
                'listid' => 'questionshierarchy',
                'link' => $CFG->wwwroot . '/local/units/index.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('questions:questionhierarchy', 'local_questions'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'questionshierarchyicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('questions:questionhierarchy', 'local_questions'),
            ];
            // $questionshierarchyurl = $CFG->wwwroot . '/local/units/index.php';
        }
        if(is_siteadmin() || has_capability('local/questions:questionallow', \context_system::instance())){
            if ($pageurl === $CFG->wwwroot.'/local/questions/questionbank_view.php?courseid=1&cat='.$category) {
                $listclass5 = $listclass.' active';
            }  else {
                $listclass5 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass5,
                'listid' => 'questions',
                'link' => $CFG->wwwroot . '/local/questions/questionbank_view.php?courseid=1&cat=' . $category,
                'linkclass' => $linkclass,
                'linktitle' => get_string('questions', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'questionsicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('questions', 'theme_horizon'),
            ];
            // $questionsallowurl = $CFG->wwwroot . '/local/questions/questionbank_view.php?courseid=1&cat=' . $category;
        }
        if(is_siteadmin() || has_capability('local/questions:adminreports', \context_system::instance())){
            if ($pageurl === $CFG->wwwroot.'/blocks/reportdashboard/reports.php' && $listid == 'reports') {
                $listclass6 = $listclass.' active';
            } else {
                $listclass6 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass6,
                'listid' => 'reports',
                'link' => $CFG->wwwroot . '/blocks/reportdashboard/reports.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('reports'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'reportsicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('reports'),
            ];
            // $viewreportsadmin = true;
        }
        if(!is_siteadmin() && has_capability('local/questions:studentreports', \context_system::instance())){
            if ($pageurl === $CFG->wwwroot.'/blocks/reportdashboard/studentprofile.php?filter_users='.$USER->id) {
                $listclass7 = $listclass.' active';
            } else {
                $listclass7 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass7,
                'listid' => 'reports',
                'link' => $CFG->wwwroot . '/blocks/reportdashboard/studentprofile.php?filter_users='.$USER->id,
                'linkclass' => $linkclass,
                'linktitle' => get_string('reports'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'reportsicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('reports'),
            ];
            // $viewreportsstudent = true;
        }
        if (is_siteadmin() || has_capability('local/onlineexams:view', \context_system::instance())) {
            if ($pageurl === $CFG->wwwroot.'/local/onlineexams/index.php') {
                $listclass8 = $listclass.' active';
            } else {
                $listclass8 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass8,
                'listid' => 'onlineexams',
                'link' => $CFG->wwwroot.'/local/onlineexams/index.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('onlineexams', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'onlineexamsicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('onlineexams', 'theme_horizon'),
            ];
            // $onlineexamsurl = $CFG->wwwroot.'/local/onlineexams/index.php';
        }
        if(is_siteadmin() || has_capability('local/faq:view', \context_system::instance())){
            if ($pageurl === $CFG->wwwroot.'/local/faq/index.php') {
                $listclass9 = $listclass.' active';
            } else {
                $listclass9 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass9,
                'listid' => 'faq',
                'link' => $CFG->wwwroot . '/local/faq/index.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('faq', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'faqicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('helpandsupport', 'theme_horizon'),
            ];
            // $faqallow = $CFG->wwwroot . '/local/faq/index.php';
        }
        if (is_siteadmin()) {
            if ($pageurl === $CFG->wwwroot.'/admin/search.php') {
                $listclass10 = $listclass.' active';
            } else {
                $listclass10 = $listclass.' inactive';
            }
            $links[] = [
                'listclass' => $listclass10,
                'listid' => 'administration',
                'link' => $CFG->wwwroot . '/admin/search.php',
                'linkclass' => $linkclass,
                'linktitle' => get_string('administration', 'theme_horizon'),
                'linkspanclass' => $linkspanclass,
                'linkspanid' => 'administrationicon',
                'spanclass' => $spanclass,
                'displaystring' => get_string('administration', 'theme_horizon'),
            ];
        }
        $linkstosend = array_values($links); 
        $templatecontext = [
            'userimg' => $userimg,
            'userid' => $USER->id,
            'username' => $USER->firstname . ' ' . $USER->lastname,
            'isadmin' => $isadmin,
            'linkstosend' => $linkstosend,
            // 'question_url' => $CFG->wwwroot . '/local/questions/questionbank_view.php?courseid=1&cat=' . $category,
            // 'isquestionhierarchy' => $questionshierarchyurl,
            // 'isquestionallow' => $questionsallowurl,
            // 'viewreportsadmin' => $viewreportsadmin,
            // 'viewreportsstudent' => $viewreportsstudent,
            // 'onlineexamsurl' => $onlineexamsurl,
	        //'tests_url' => $CFG->wwwroot . '/local/onlineexams/index.php',
            //'packages_url' => $CFG->wwwroot . '/local/packages/index.php',
            //'goals_url' => $CFG->wwwroot . '/local/goals/index.php',
            // 'faq_url' =>  $faqallow,
            // 'mycalendar_url' => $CFG->wwwroot . '/blocks/notification/calendar.php',
        ];
        return $this->render_from_template('theme_horizon/sidebar', $templatecontext);
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $CFG, $SITE;

        $context = $form->export_for_template($this);

        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->loginlogo = $this->get_custom_loginlogo();
        $context->sitename = format_string($SITE->fullname, true,
                ['context' => context_course::instance(SITEID), "escape" => false]);
        $configurations = get_config('theme_horizon');
        if (isset($configurations->siteurl) && !empty($configurations->siteurl) && isset($configurations->sitelabel) && !empty($configurations->sitelabel)){
            $context->customlogininfo[] = ['logininfourl' => $configurations->siteurl, 'logininfotext' => $configurations->sitelabel];
        }
        if (isset($configurations->ssourl) && !empty($configurations->ssourl) && isset($configurations->ssolabel) && !empty($configurations->ssolabel)){
            $context->customlogininfo[] = ['logininfourl' => $configurations->ssourl, 'logininfotext' => $configurations->ssolabel];
        }

        return $this->render_from_template('core/customloginform', $context);
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_customlogin(\core_auth\output\login $form) {
        global $CFG, $SITE;

        $context = $form->export_for_template($this);

        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->loginlogo = $this->get_custom_loginlogo();
        $context->sitename = format_string($SITE->fullname, true,
                ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Whether we should display the logo.
     *
     * @author Rizwana Shaik
     * @return string url
     */
    public function get_custom_loginlogo() {
        $loginlogo = '';
        if($this->page->theme->setting_file_url('loginlogo', 'loginlogo')) {
            $loginlogo = $this->page->theme->setting_file_url('loginlogo', 'loginlogo');
        }
        
        // if(empty($logopath)) {
        //     $default_logo = $this->image_url('default_logo', 'theme_horizon');
        //     $logopath = $default_logo;
        // }
        return $loginlogo;
    }

    /**
     * Path for the selected font will return default as 0: lato
     *
     * @param array('Lato', 'Open Sans', 'PT Sans', 'Roboto', 'Maven Pro', 'Comfortaa')
     * @return url path for the selected font family name
     */
    function get_font_path(){

        $font_value = get_config('theme_horizon', 'font');

        $return = '';
        switch($font_value){
            case 0://for Poppins font
                $return = new moodle_url('/theme/horizon/fonts/poppins.css');
            break;
            case 1://for Lato font
                $return = new moodle_url('/theme/horizon/fonts/lato.css');
            break;
            case 2://for Open Sans font
                $return = new moodle_url('/theme/horizon/fonts/opensans.css');
            break;
            case 3://for PT Sans font
                $return = new moodle_url('/theme/horizon/fonts/ptsans.css');
            break;
            case 4://for Roboto font
                $return = new moodle_url('/theme/horizon/fonts/roboto.css');
            break;
            case 5://for Maven Pro font
                $return = new moodle_url('/theme/horizon/fonts/mavenpro.css');
            break;
            case 6://for Comfortaa font
                $return = new moodle_url('/theme/horizon/fonts/Comfortaa.css');
            break;
            case 7://for Comfortaa font
                $return = new moodle_url('/theme/horizon/fonts/Panch.css');
            break;
                
        }
        return $return;
    }
    function newsreader_fontpath() {
        return new moodle_url('/theme/horizon/fonts/newsreader.css');
    }

    /**
     * Returns the footnote text.
     *
     * @author Rizwana Shaik
     * @return $string
     */
    public function footnote() {
        $footnote = '';
        $footnote = $this->page->theme->settings->footnote;
        if (empty($footnote)) {
            $footnote = '';
        }
        return $footnote;
    }

    public function get_profile_info() {
        global $USER, $CFG,$DB;
        if($_REQUEST['id']){
            $user = $DB->get_record('user',['id' => $_REQUEST['id']]);
        }else{
            $user = $USER;
        }
        $systemcontext = \context_system::instance();
        $user_picture = new \user_picture($user, array('size' => 80, 'class' => 'profileuserimg', 'link'=>false));
        $user_picture = $user_picture->get_url($this->page);
        $userimg = $user_picture->out();
        if (has_capability('moodle/user:loginas', $systemcontext) && ($user->id != $USER->id)) {
            $loginasurl = new moodle_url('/course/loginas.php', array('id' => 1, 'user' => $user->id, 'sesskey' => sesskey()));
        } else {
            $loginasurl = false;
        }
        if (!\core\session\manager::is_loggedinas() && $USER->id == $user->id) {
            $editprofileurl = get_config('auth_lbssomoodle','laravel_site_url').'/user/profile';
        } else {
            $editprofileurl = false;
        }
        $mycourses_renderer = $this->page->get_renderer('block_mycourses');
        $templatecontext = [
            'userimg' => $userimg,
            'userid' => $user->id,
            'username' => $user->firstname.' '.$user->lastname,
            'userdob' => $user->dob != NULL ? $user->dob : 'N/A',
            'usergender' => $user->gender != NULL ? $user->gender : 'N/A',
            'usermail' => $user->email != NULL ? $user->email : 'N/A',
            'userphone' => $user->phone1 != NULL ? $user->phone1 : 'N/A',
            'useraddress' => $user->address != NULL ? $user->address : 'N/A',
            'inprogresscourses' => $mycourses_renderer->render_inprogress_packages($user->id),
            'completescourses' => $mycourses_renderer->render_completed_packages($user->id),
            'loginasurl' => $loginasurl,
            'editprofileurl' => $editprofileurl,
        
            
        ];
        return $this->render_from_template('theme_horizon/profile_info', $templatecontext);
    }

    /**
     * [get_category description]
     */
    public function get_category()
    {

        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

        // added hierarchy table inclusion as we do not get courses listed without creating the package
        $category = $DB->get_records_sql("SELECT cc.id,cc.name,lp.valid_to,count(c.id) as coursecount, lh.image FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} AS c ON c.id = e.courseid 
        JOIN {course_categories} AS cc ON c.category = cc.id 
        JOIN {local_packages} AS lp on lp.categoryid = cc.id 
        LEFT JOIN {local_hierarchy} AS lh on lh.categoryid = cc.id 
        WHERE ue.userid = :userid AND ue.status = 0 AND ue.timestart < :timenow1 AND (ue.timeend > :timenow2 OR ue.timeend = 0)  AND cc.visible=1 GROUP BY cc.id,cc.name ", ['userid' => $USER->id, 'timenow1' => time(), 'timenow2' => time()]);
        return $category;
    }

    public function render_navbar_select()
    {
        global $DB, $USER, $OUTPUT, $CFG, $PAGE;

        $category = $this->get_category();
        $count = 0;
        $params = array();
        $cids = [];
        $catimg = $OUTPUT->image_url('cat', 'block_mycourses');
        $multicourses = $singlecourses = [];
        foreach ($category as $cat) {
                $enrolled = enrol_get_my_courses();

                $courses = [];
                $percentage_of_completion = 0;
                $modules_in_course = 0;
                $completed_moduleincourse = 0;
                $total_completed_activity1 = [];
                foreach ($enrolled as $ecourse) {

                    if ($ecourse->category == $cat->id) {
                        $course_completed = 0;
                        $courseparams = [];
                        $courseparams['url'] =  $ecourse->id;
                        $courseparams['coursename'] = $ecourse->fullname;

                        $courses[] = $courseparams;
                    }
                }

            if ($cat->coursecount == 1) {
                $params[$count]['singlecourse'] = true;
            } else {
                $params[$count]['singlecourse'] = false;

                foreach ($courses as $ckey => $cvalue) {
                    $cids[] = $cvalue['url'];
                }
                $sql = "SELECT ue.timeend
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE ue.userid = ? ";
                if (!empty($cids)) {
                    $courseids = implode(',', $cids);
                    $sql .= " AND e.courseid IN ($courseids)";
                }
                $timeenddate = $DB->get_fieldset_sql($sql, [$USER->id]);

                $freetrailsql = "SELECT e.enrol
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE ue.userid = ? ";
                if (!empty($cids)) {
                    $courseids = implode(',', $cids);
                    $freetrailsql .= " AND e.courseid IN ($courseids)";
                }
                $getfreetrail = $DB->get_fieldset_sql($freetrailsql, [$USER->id]);

            }

            $params[$count]['courses'] = $courses;
            $params[$count]['categoryid'] = $cat->id;
            $params[$count]['categoryname'] = $cat->name;
            $params[$count]['catimg_url'] = $catimg_url;
            $params[$count]['loginasurl'] = $CFG->wwwroot;
            if ($cat->coursecount == 1) {
                $singlecourses[] = $params[$count];
            } else {
                $multicourses[] = $params[$count];
            }
            $count++;
        }
        $returnparams = array_merge($multicourses, $singlecourses); // To display multiple courses first and then single courses.
        $data = array(
            "categorydetails" => $returnparams,
        );
        $isenrolled = enrol_get_my_courses();
        if ((count($isenrolled) > 0) && ($category)) {
            return  $this->render_from_template('theme_horizon/courseselect',  $data);
        }
    }



    public function render_navbar_select_bk1() {
        global $DB, $COURSE, $CFG;
        $enrolled = enrol_get_my_courses();
        $options = [];

        if (count($enrolled) > 1) {
            $categorycourses = [];

            foreach ($enrolled as $ecourse) {
                $categorycourses[$ecourse->category][] = [
                    'id' => $ecourse->id,
                    'coursename' => $ecourse->fullname,
                    'category' => $DB->get_field('course_categories', 'name', ['id' => $ecourse->category]),
                ];
            }

            $multicourses = $singlecourses = [];

            foreach ($categorycourses as $category => $courses) {
                if (count($courses) > 1) {
                    $multicourses[] = [
                        'category' => $DB->get_field('course_categories', 'name', ['id' => $category]),
                        'courses' => [],
                    ];
                } else if (count($courses) == 1) {
                    $singlecourses[] = [
                        // 'category' => $DB->get_field('course_categories', 'name', ['id' => $category]),
                        'courses' => [],
                    ];
                }

                foreach ($courses as $course) {
                    $group = [
                        'courseid' => $course['id'],
                        'coursename' => $course['coursename'],
                        'loginasurl' => $CFG->wwwroot,
                    ];

                    if (count($courses) > 1) {
                        $multicourses[count($multicourses) - 1]['courses'][] = $group;
                    } else if (count($courses) == 1) {
                        $singlecourses[count($singlecourses) - 1]['courses'][] = $group;
                    }
                }
            }

            $options = array_merge($multicourses, $singlecourses);

            $templatecontext = [
                'courses' => $options,
            ];


            return $this->render_from_template('theme_horizon/navbar_select_dropdown', $templatecontext);
        }
    }
    
    
    public function render_navbar_select_bk(){
        global $DB, $COURSE, $CFG;
        $enrolled = enrol_get_my_courses();

        $multicourses = $singlecourses = [];
        // $count = 0;
        // $params = array();
        // if ($cat->coursecount == 1) {
        //         $params[$count]['singlecourse'] = true;
        //     } else {
        //         $params[$count]['singlecourse'] = false;
        // }
        if(count($enrolled) > 1) {

            $categorycourses = [];
            foreach ($enrolled as $ecourse) {

                $categorycourses[$ecourse->category][$ecourse->id] = $ecourse->fullname;
            }
            $options = [];
            foreach($categorycourses AS $category => $groups){
                if(count($groups) == 1) {
                    $singlecourses[] = [$catname => $groups];
                } else if (count($groups) > 1) {
                $catname = $DB->get_field('course_categories', 'name', ['id' => $category]);
                    $multicourses[] = [$catname => $groups];
                }
            }
            $options = array_merge($multicourses, $singlecourses);
            $addoptions = array_merge([1 => get_string('dashboard', 'theme_horizon')], $options);
     
            $id = optional_param('id', 1, PARAM_INT);
            if ($id == 0) {
                redirect($CFG->wwwroot . '/my');
            }else{
                $url = '/course/view.php';
            }
            $options = array_merge($multicourses, $singlecourses);
            return $this->single_select(new moodle_url($url), 'id', $addoptions, $COURSE->id, null,
                    null, []);
        }
    }
}
