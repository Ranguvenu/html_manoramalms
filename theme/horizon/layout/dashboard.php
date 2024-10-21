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
 * A drawer based layout for the horizon theme.
 *
 * @package   theme_horizon
 * @copyright 2021 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB;
require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);
$isloggedin = isloggedin()  ? true : false;
if ($isloggedin) {
    // $navdraweropen = true;
    $navdraweropen = (get_user_preferences('drawer-open-index', true) == true);
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    // $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    // $blockdraweropen = false;
    $navdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

// $blockshtml = $OUTPUT->blocks('side-pre');
// $hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
// if (!$hasblocks) {
//     $blockdraweropen = false;
// }
$navdrawer = $blockshtml = $hasblocks = '';
$courseindex = core_course_drawer();
if ($isloggedin) {
    $blockshtml = $OUTPUT->blocks('side-pre');
    $hasblocks = strpos($blockshtml, 'data-block=') !== false;
    $navdrawer = $OUTPUT->get_left_navigation();
}
if (!$courseindex) {
    $courseindexopen = false;

}
if(!$navdrawer) {
   $navdraweropen = false;
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);
$fontpath = $OUTPUT->get_font_path();

$role = $DB->get_record_sql('SELECT ra.id,r.archetype,r.shortname FROM {role} as r,{role_assignments} as ra WHERE ra.roleid=r.id && userid='.$USER->id.' GROUP BY ra.userid');
$isuser = false; 
if($role->shortname == 'student') {
    $isuser = true;  
}

//Laravel CMS url
$laravelurl = get_config('auth_lbssomoodle','laravel_site_url');
if (isloggedin() && has_capability('local/packages:accesssitefromlms', context_system::instance())) {// Adding capability confirmation for users to have the CMS Dashboard Button.
    $islaravelurl = true;
} else {
    $laravelurl = $laravelurl.'/home';
    $islaravelurl = false;
}
if(isloggedin()){
$isloggedingotowebsite = true;
}else{
$isloggedingotowebsite = false;
}
 


$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    // 'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'navdrawer' => $navdrawer,
    'navdraweropen' => $navdraweropen,
    //'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'isloggedin' => $isloggedin,
    'font_path' => $fontpath,
    'isuser' => $isuser, 
    'laravelurl' => $laravelurl, 
    'islaravelurl'=>$islaravelurl,
    'isloggedingotowebsite'=>$isloggedingotowebsite,
];

echo $OUTPUT->render_from_template('theme_horizon/dashboard', $templatecontext);
