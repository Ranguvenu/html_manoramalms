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

$string['pluginname'] = 'Laravel Bridge SSO';
$string['connectionsuccessful'] = "Connection successful";
$string['invalidencryptionkey'] = "Invalid encyption key";
$string['auth_lbssomoodledescription'] = "This plugin is used to authenticate the user on moodle from laravel";
$string['auth_lbssomoodle_salt'] = "Saltkey";
$string['auth_lbssomoodle_salt_desc'] = "Saltkey is used as a lock while encrypting the date, make sure to maintain the same salt key on both Laravel & Moodle sites";
$string['auth_lbssomoodle_laravel_site_url'] = "Laravel site url";
$string['auth_lbssomoodle_laravel_site_url_desc'] = "Enter laravel site url";
$string['auth_lbssomoodle_logout_redirect_url'] = "Logout redirect url";
$string['auth_lbssomoodle_logout_redirect_url_desc'] = "Enter logout redirect url";
