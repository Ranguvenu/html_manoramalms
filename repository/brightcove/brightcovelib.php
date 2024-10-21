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
define('HEX2BIN_WS', " \t\n\r");
class brighcovelib extends local_brightcove\api{
    const THUMBS_PER_PAGE = 10;
    public function createSearchApiUrl() {
        // return $this->api_url."/api/v1/videos/index";
 		return "{$this->baseurl}/v1/accounts/{$this->accountid}/videos";
  	}
  	public function get_listing_params(){
        global $CFG;
  		$tokenurl = $this->api_url."/api/v1/apis/token";
  		$c = new \curl();
        $tokenjson = $c->post($tokenurl, array('key'=> $this->client_id, 'secret' => $this->secret, 'domain' => $CFG->wwwroot));
        $tokenInfo = json_decode($tokenjson);
        $token = $tokenInfo->token;
        $params = array('token' => $token);
        return $params;
  	}
    public function get_videos_listing($search = '', $offset = 0){
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
        $params = ['limit' => 10, 'offet' => $offset, 'sort' => '-updated_at'];
        if(!empty(trim($search))){
            $params['query'] = $search; 
        }
        // var_dump($this->createSearchApiUrl());exit;
        $response = $curl->get($this->createSearchApiUrl(), $params);
        // print_r(json_decode($response));exit;
        return json_decode($response);
    }
    // public function createListingApiUrl(){
    //     return $this->api_url."/api/v1/videos/fvideos";
    // }
    public function get_upload_data(){
        $search_url = $this->api_url."/api/v1/videos/uploaddata";
        $c = new \curl();
        $params = $this->get_listing_params();
        $params['key'] = $this->client_id;
        $params['secret'] = $this->secret;
        $content = $c->post($search_url, $params);
        return $content;
    }
}