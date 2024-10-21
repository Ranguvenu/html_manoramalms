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
 * @package   theme_horizon
 * @copyright 2016 Ryan Wyllie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_horizon_admin_settingspage_tabs('themesettinghorizon', get_string('configtitle', 'theme_horizon'));
    $page = new admin_settingpage('theme_horizon_general', get_string('generalsettings', 'theme_horizon'));

    // Unaddable blocks.
    // Blocks to be excluded when this theme is enabled in the "Add a block" list: Administration, Navigation, Courses and
    // Section links.
    $default = 'navigation,settings,course_list,section_links';
    $setting = new admin_setting_configtext('theme_horizon/unaddableblocks',
        get_string('unaddableblocks', 'theme_horizon'), get_string('unaddableblocks_desc', 'theme_horizon'), $default, PARAM_TEXT);
    $page->add($setting);

    // Preset.
    $name = 'theme_horizon/preset';
    $title = get_string('preset', 'theme_horizon');
    $description = get_string('preset_desc', 'theme_horizon');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_horizon', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'horizon');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_horizon/presetfiles';
    $title = get_string('presetfiles','theme_horizon');
    $description = get_string('presetfiles_desc', 'theme_horizon');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Background image setting.
    $name = 'theme_horizon/backgroundimage';
    $title = get_string('backgroundimage', 'theme_horizon');
    $description = get_string('backgroundimage_desc', 'theme_horizon');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login Background image setting.
    $name = 'theme_horizon/loginbackgroundimage';
    $title = get_string('loginbackgroundimage', 'theme_horizon');
    $description = get_string('loginbackgroundimage_desc', 'theme_horizon');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_horizon/brandcolor';
    $title = get_string('brandcolor', 'theme_horizon');
    $description = get_string('brandcolor_desc', 'theme_horizon');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ED396C');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_horizon_advanced', get_string('advancedsettings', 'theme_horizon'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_horizon/scsspre',
        get_string('rawscsspre', 'theme_horizon'), get_string('rawscsspre_desc', 'theme_horizon'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_horizon/scss', get_string('rawscss', 'theme_horizon'),
        get_string('rawscss_desc', 'theme_horizon'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // Custom settings.
    $page = new admin_settingpage('theme_horizon_custom', get_string('customsettings', 'theme_horizon'));

    // Login logo image setting.
    $name = 'theme_horizon/loginlogo';
    $title = get_string('loginlogo', 'theme_horizon');
    $description = get_string('loginlogo_desc', 'theme_horizon');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginlogo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/font';
    $title = get_string('font', 'theme_horizon');
    $description = get_string('font_desc', 'theme_horizon');
    $default = 0;
    $choices = array('Poppins', 'Lato', 'Open Sans', 'PT Sans', 'Roboto', 'Maven Pro', 'Comfortaa');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/footnote';
    $title = get_string('footnote', 'theme_horizon');
    $description = get_string('footnote_desc', 'theme_horizon');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/siteurl';
    $title = get_string('siteurl', 'theme_horizon');
    $description = get_string('siteurl_desc', 'theme_horizon');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/sitelabel';
    $title = get_string('sitelabel', 'theme_horizon');
    $description = get_string('sitelabel_desc', 'theme_horizon');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/ssourl';
    $title = get_string('ssourl', 'theme_horizon');
    $description = get_string('ssourl_desc', 'theme_horizon');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/ssolabel';
    $title = get_string('ssolabel', 'theme_horizon');
    $description = get_string('ssolabel_desc', 'theme_horizon');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/aboutusurl';
    $title = get_string('aboutusurl', 'theme_horizon');
    $description = get_string('aboutusurl_desc', 'theme_horizon');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_horizon/userguide';
    $title = get_string('userguide', 'theme_horizon');
    $description = get_string('userguide_desc', 'theme_horizon');
    $default = get_string('stepsforuserguide', 'theme_horizon');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default, PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
