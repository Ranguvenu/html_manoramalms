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
 * Version information
 *
 * @version    1.0.0
 * @package    auth_lbssomoodle
 * @author     Ranga Reddy<ranga.seguri@moodle.com>
 * @copyright  2023 Ranga Reddy (https://moodle.com/in)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('auth_lbssomoodle/pluginname', '',
        new lang_string('auth_lbssomoodledescription', 'auth_lbssomoodle')
    ));

    $settings->add(
        new admin_setting_configtext('auth_lbssomoodle/salt',
            get_string('auth_lbssomoodle_salt', 'auth_lbssomoodle'),
            get_string('auth_lbssomoodle_salt_desc', 'auth_lbssomoodle'),
            '',
            PARAM_RAW
        )
    );

    $settings->add(
        new admin_setting_configtext('auth_lbssomoodle/laravel_site_url',
            get_string('auth_lbssomoodle_laravel_site_url', 'auth_lbssomoodle'),
            get_string('auth_lbssomoodle_laravel_site_url_desc', 'auth_lbssomoodle'),
            '',
            PARAM_RAW
        )
    );

    $settings->add(
        new admin_setting_configtext('auth_lbssomoodle/logout_redirect_url',
            get_string('auth_lbssomoodle_logout_redirect_url', 'auth_lbssomoodle'),
            get_string('auth_lbssomoodle_logout_redirect_url_desc', 'auth_lbssomoodle'),
            '',
            PARAM_RAW
        )
    );
}