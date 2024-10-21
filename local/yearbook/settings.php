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
$reporting = new admin_category('local_yearbook', new lang_string('settings', 'local_yearbook'),false);
$ADMIN->add('localsettings', $reporting);
$settings = new admin_settingpage('local_yearbook', get_string('settings', 'local_yearbook'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
    $settings->add((new admin_setting_configtext('local_yearbook/clientid',
            get_string('clientid', 'local_yearbook'), get_string('clientid_help', 'local_yearbook'), '')));
}