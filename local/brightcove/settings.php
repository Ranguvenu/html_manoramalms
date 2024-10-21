<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package
 * @subpackage local_brightcove
 */
defined('MOODLE_INTERNAL') || die;
$reporting = new admin_category('local_brightcove', new lang_string('settings', 'local_brightcove'),false);
$ADMIN->add('localsettings', $reporting);
$settings = new admin_settingpage('local_brightcove', get_string('settings', 'local_brightcove'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
    $settings->add((new admin_setting_configtext('local_brightcove/baseurl',
            get_string('baseurl', 'local_brightcove'), get_string('baseurl_help', 'local_brightcove'), 'https://cms.api.brightcove.com')));
    $settings->add((new admin_setting_configtext('local_brightcove/accountid',
            get_string('accountid', 'local_brightcove'), get_string('accountid_help', 'local_brightcove'), '')));
    $settings->add((new admin_setting_configtext('local_brightcove/clientid',
            get_string('clientid', 'local_brightcove'), get_string('clientid_help', 'local_brightcove'), '')));
    $settings->add((new admin_setting_configtext('local_brightcove/clientsecret',
            get_string('clientsecret', 'local_brightcove'), get_string('clientsecret_help', 'local_brightcove'), '')));
    $settings->add((new admin_setting_configtext('local_brightcove/authurl',
            get_string('authurl', 'local_brightcove'), get_string('authurl_help', 'local_brightcove'), 'https://oauth.brightcove.com/v4/access_token')));
    $settings->add((new admin_setting_configtext('local_brightcove/playerid',
            get_string('playerid', 'local_brightcove'), get_string('playerid_help', 'local_brightcove'), '')));
}