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
 * local_brightcove
 * @package local_brightcove
 * @copyright 2023 Moodle India
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_brightcove;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/filelib.php');

class api extends \curl {
	public $baseurl = '';
	public $accountid;
	public $clientid = '';
	public $clientsecret = '';
	public $authurl = '';
	public $expirytime = 0;
	public $accesstoken;

	public function __construct(){
		$settings = get_config('local_brightcove');
		// var_dump($settings);exit;
		$this->baseurl = $settings->baseurl;
		$this->accountid = $settings->accountid;
		$this->clientid = $settings->clientid;
		$this->clientsecret = $settings->clientsecret;
		$this->authurl = $settings->authurl;
		parent::__construct(['debug' => false]);
	}
	public function generate_token(){
		if(!empty($this->authurl)){
			$now = time();
			$this->setopt(array('CURLOPT_USERPWD' => "{$this->clientid}:{$this->clientsecret}"));
			$response = json_decode($this->post($this->authurl.'?grant_type=client_credentials'));
			if(isset($response->access_token)){
				$this->accesstoken = $response->access_token;
				$this->expirytime = $now+$response->expires_in;
			}
		}
	}
	public function push_video_to_brightcove($downloadtoken, $recordingurlinfo, $recording){
		global $DB, $USER;
		if($DB->record_exists('local_brightcove_recording', array('zoomrecordingid' => $recording->zoomrecordingid, 'meetinguuid' => $recording->meetinguuid))){
			mtrace('Recording already transferred with id '.$recording->id);
			return false;
		}
		$moduleinfo = $DB->get_record('zoom', array('id' => $recording->zoomid));
		$videoid = $this->get_newvideoid($moduleinfo, $recording);
		$this->download_recording($videoid, $downloadtoken ,$recordingurlinfo);
		$injestresponse = $this->injest_video_url($videoid);
		if(isset($injestresponse->id)){
			$videoobject = new \stdClass();
			$videoobject->zoomid = $recording->zoomid;
			$videoobject->meetinguuid = $recording->meetinguuid;
			$videoobject->zoomrecordingid = $recording->zoomrecordingid;
			$videoobject->videoid = $videoid;
			$videoobject->recordingurl = $recording->externalurl;
			$videoobject->courseid = $moduleinfo->course;
			$videoobject->timecreated = time();
			$videoobject->usercreated = $USER->id;
			$DB->insert_record('local_brightcove_recording', $videoobject);
			mtrace('Recording transferred with id '.$recording->id);
		}

	}
	public function download_recording($videoid, $downloadtoken ,$recordingurlinfo){
		global $DB, $CFG;
		$content = $this->get("{$recordingurlinfo->download_url}?access_token={$downloadtoken}");
		if (!file_exists($CFG->dataroot.DIRECTORY_SEPARATOR.'zoomrecordings')) {
            mkdir($CFG->dataroot.DIRECTORY_SEPARATOR.'zoomrecordings', 0777, true);
        }
        $filepath = "$CFG->dataroot/zoomrecordings/{$videoid}.mp4";
		file_put_contents($filepath, $content);
        $syscontext = \context_system::instance();
	    $filerecord = array(
	        'contextid' => $syscontext->id,
	        'component' => 'local_brightcove',
	        'filearea'  => 'zoomvideo',
	        'filepath'  => '/brightcovevideos/',
	        'filename'  => "{$videoid}.mp4",
	    );
	    $filerecord['itemid'] = $videoid;
	    $pathhash = sha1('/'.$filerecord['contextid'].'/'.$filerecord['component'].'/'.$filerecord['filearea'].'/'.$filerecord['itemid'].$filerecord['filepath'].$filerecord['filename']);
	    $fs = get_file_storage();
    	$fs->create_file_from_pathname($filerecord, $filepath);
    	unlink($filepath);
		mtrace("Stored file with file name {$videoid}.mp4");
	}
	public function get_newvideoid($moduleinfo, $recording){
		if($this->expirytime < time()-30){
			$this->generate_token();
		}
		$headers = [
			'Content-Type : application/json',
			'Accept : application/json',
			"Authorization : Bearer {$this->accesstoken}"
		];
		$curl = new \curl();
		$curl->setHeader($headers);

		$postfields = [
            'name' => $moduleinfo->name,
            'description' => $moduleinfo->intro,
            'reference_id' => uniqid($moduleinfo->id)
        ];
        $apiparams = json_encode($postfields);
		$response = json_decode($curl->post("{$this->baseurl}/v1/accounts/{$this->accountid}/videos", $apiparams));
		return $response->id;
	}
	public function injest_video_url($videoid){
		if($this->expirytime < time()-30){
			$this->generate_token();
		}
		$headers = [
			'Content-Type : text/plain',
			'Accept : application/json',
			"Authorization : Bearer {$this->accesstoken}"
		];
		$curl = new \curl(['debug' => 0]);
		$curl->setHeader($headers);
		$recordingurl = \moodle_url::make_pluginfile_url(1, 'local_brightcove', 'zoomvideo', $videoid, '/brightcovevideos/', "$videoid.mp4");
		$postfields = [
            'master' => ['url' => $recordingurl->out()],
            'profile' => 'multi-platform-extended-static',
            'capture-images' => true
        ];
        $apiparams = json_encode($postfields);

		$response = json_decode($curl->post("https://ingest.api.brightcove.com/v1/accounts/{$this->accountid}/videos/{$videoid}/ingest-requests", $apiparams));
		mtrace('injest id '.$response->id);
		mtrace($recordingurl->out());
		return $response;
	}
}