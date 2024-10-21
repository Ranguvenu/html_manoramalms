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
 * version control file
 * @package   repository_brightcove
 * @copyright Moodle India
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * This plugin is used to access stream video
 * @package    repository_stream
 */
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot.'/repository/brightcove/brightcovelib.php');

/**
 * This plugin is used to access user's private stream video
 * @package    repository_stream
 */
class repository_brightcove extends repository {
    /** @var int maximum number of thumbs per page */
    const THUMBS_PER_PAGE = 10;
    public $listingUrl;
    /**
     * brightcove plugin constructor
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     */
  public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
		// $this->api_url  = $this->get_option('api_url');
		// $this->api_key = $this->get_option('api_key');
        // $this->secret  = $this->get_option('secret');
		// $this->email_address  = $this->get_option('email_address');
		// $this->user_name  = $this->get_option('user_name');
		$this->api = new brighcovelib();
    }

    public function check_login() {
        return true;
    }
    public function search($search_text, $page = 0) {
        $ret  = array();
        $ret['nologin'] = true;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }

        $start = ($ret['page'] - 1) * self::THUMBS_PER_PAGE + 1;
        $max = self::THUMBS_PER_PAGE;
		$start = $start-1;
        $this->search_url = $this->stream->createSearchApiUrl();

        $params = $this->stream->get_listing_params();
        $params['q'] = $search_text;
        $params['sort'] = $sort;
        $params['perpage'] = self::THUMBS_PER_PAGE;
		$request = new curl();
        $content = $request->post($this->search_url, $params);

		$content = json_decode($content,true);
        // $params = array(
        //     'context' => $context,
        //     'objectid' => $content
        // );
        // $eventcheck = \repository_stream\event\get_videos::create($params);
        // $eventcheck->trigger();
        $ret['list'] = $this->_get_collection($content);
        $ret['norefresh'] = true;
        $ret['nosearch'] = false;
		$ret['total'] = $content['meta']['total'];
		$ret['pages'] = ceil($content['meta']['total']/self::THUMBS_PER_PAGE);
        $ret['perpage'] = self::THUMBS_PER_PAGE;
        return $ret;
    }

    /**
     * Private method to get video list
     */
    private function _get_collection($content) {
        $list = array();
    	if(!empty($content)) {
    		foreach ($content as $entry) {
    			$list[] = array(
    		        'shorttitnamele' => $entry->name,
    		        'thumbnail_title' => $entry->name,
    		        'title' => $entry->name.'.avi', // this is a hack so we accept this file by extension
                    'thumbnail' => $entry->images->thumbnail->src,
    		        'videoid' => stripslashes($entry->id),
    		        'thumbnail_width' => 150,
    		        'thumbnail_height' => 150,
    		        'size' => 1*1024*1024,
    		        'date' => strtotime($entry->created_at),
    				'license' => 'unknown',
    				'author' => ' ',
                    'source' => "brighcove_recording_for_streaming_{$entry->id}"
                    // 'source' => "https://players.brightcove.net/5819061496001/GGSDGwZCJl_default/index.html?videoId={$entry->id}"
        		);
    		}
    	}
		return $list;
    }

    public static function get_type_option_names() {
        return array('api_key', 'secret', 'api_url', 'pluginname');
    }

    /**
     * file types supported by stream plugin
     * @return array
     */
    public function supported_filetypes() {
        return array('video');
    }

    /**
     * Is this repository accessing private data?
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }

    /**
     * Tells how the file can be picked from this repository
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }

    /**
     * Does this repository used to browse moodle files?
     *
     * @return boolean
     */
    public function has_moodle_files() {
        return false;
    }

    public function get_listing($path='', $page = '') {
        global $OUTPUT;
        $folderUrl = $OUTPUT->pix_url('f/folder-128')->out();
        $filesList = $this->api->get_videos_listing();
        $return['list'] = $this->_get_collection($filesList);
        return $return;
    }
}
