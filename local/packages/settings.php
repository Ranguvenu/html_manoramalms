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
 * along with this program.  If not, see <http:/www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package
 * @subpackage local_packages
 */
defined('MOODLE_INTERNAL') || die;
$reporting = new admin_category('local_packages', new lang_string('settings', 'local_packages'),false);
$ADMIN->add('localsettings', $reporting);
$settings = new admin_settingpage('local_packages', get_string('settings', 'local_packages'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
    $settings->add((new admin_setting_configtext('local_packages/packagesurl',
            get_string('packagesurl', 'local_packages'), get_string('packagesurl_help', 'local_packages'), '/api/v1/packages/')));
    $settings->add((new admin_setting_configtext('local_packages/hierarchyurl',
            get_string('hierarchyurl', 'local_packages'), get_string('hierarchyurl_help', 'local_packages'), '/api/v1/hierarchy/')));
    $settings->add((new admin_setting_configtext('local_packages/displayablegoals',
            get_string('displayablegoals', 'local_packages'), get_string('displayablegoals_help', 'local_packages'), 'k12')));
    
}