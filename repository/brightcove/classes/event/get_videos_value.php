<?php
namespace repository_stream\event;

defined('MOODLE_INTERNAL') || die();

class get_videos_value extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'stream';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('eventgetvideosvalue', 'repository_stream');
    }

    public function get_description() {
        return "searched value is {$this->objectid}.";
    }

    public function get_url() {
        return new \moodle_url('/course/modedit.php?add=stream',
            array('id' => $this->objectid));
    }
}
