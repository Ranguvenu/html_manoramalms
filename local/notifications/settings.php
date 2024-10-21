<?php
/**
 * @package    local_notifications
 * @copyright  2012-2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $ADMIN admin_root */

defined('MOODLE_INTERNAL') || die;

if (has_capability('moodle/site:config', context_system::instance())) {
    $settings = new admin_settingpage('local_notifications', get_string('pluginname', 'local_notifications'));
    $ADMIN->add('localplugins', $settings);

    $defaultmsg = '[[sender]] would like to draw your attention to the activity/resource '
        . '[[linkactivity]] available within the course [[linkcourse]].';
    $description = get_string('descriptionmsg', 'local_notifications');
    $message = new admin_setting_configtextarea('message_body',
        get_string('body', 'local_notifications'),
        $description,
        $defaultmsg);
    $message->plugin = 'local_notifications';
    $settings->add($message);
}
