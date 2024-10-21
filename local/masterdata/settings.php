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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$reporting = new admin_category('local_masterdata', new lang_string('settings', 'local_masterdata'),false);
$ADMIN->add('localsettings', $reporting);
$settings = new admin_settingpage('local_masterdata', get_string('settings', 'local_masterdata'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
        
        $settings->add((new admin_setting_configtext('local_masterdata/mastercourseurl',get_string('mastercourseurl', 'local_masterdata'), get_string('mastercourseurl_help', 'local_masterdata'), 'https://stag-api.manoramahorizon.com/learningmap/course-structure-data-details/')));
       
        $settings->add((new admin_setting_configtext('local_masterdata/nodedetailsurl',get_string('nodedetailsurl', 'local_masterdata'), get_string('nodedetailsurl_help', 'local_masterdata'), 'https://stag-api.manoramahorizon.com/learningmap/fetch-node-details/')));

        $settings->add((new admin_setting_configtext('local_masterdata/mediacontenturl',get_string('mediacontenturl', 'local_masterdata'), get_string('mediacontenturl_help', 'local_masterdata'), 'https://stag-media.manoramahorizon.com/')));

        $settings->add((new admin_setting_configtext('local_masterdata/apihosturl',get_string('apihosturl', 'local_masterdata'), get_string('apihosturl_help', 'local_masterdata'), 'https://stag-api.manoramahorizon.com/')));
       
        $settings->add((new admin_setting_configtext('local_masterdata/bearertoken',get_string('bearertoken', 'local_masterdata'), get_string('bearertoken_help', 'local_masterdata'), '3I563ghDI5V')));
}


$reporting = new admin_category('local_sthreesettings', new lang_string('sthreeconfigurations', 'local_masterdata'),false);
$ADMIN->add('localsettings', $reporting);
$settings = new admin_settingpage('local_sthreesettings', get_string('sthreeconfigurations', 'local_masterdata'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {        
    $settings->add((new admin_setting_configtext('local_masterdata/sthreerootpath',get_string('sthreerootpath', 'local_masterdata'), get_string('sthreerootpath_help', 'local_masterdata'), '')));
}
