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

namespace auth_lbssomoodle;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
use curl;
use stdClass;

class lib {
    public $configuration;

    private static $encryptmethod = 'AES-256-CBC';
    /**
     * Main Constructor
     */
    public function __construct() {
        $this->configuration = get_config('auth_lbssomoodle');
    }

    /**
     * Function to get Encryption key from configuration
     */
    public function get_encryption_key() {
        return $this->configuration ? $this->configuration->salt : '';
    }

    /**
     * Function to get Laravel Site Url from configuration
     */
    public function get_laravel_site_url() {
        return $this->configuration ? $this->configuration->laravel_site_url : '';
    }

    /**
     * Function to get Logout Redirection Url from configuration
     */
    public function get_logout_redirect_url() {
        return $this->configuration ? $this->configuration->logout_redirect_url : '';
    }

    /**
     * Checks if encryption key is valid or not
     * @param string $encryptionkey
     * @return boolean
     */
    public function is_valid_encryption_key($encryptionkey) {
        return ($this->get_encryption_key() == $encryptionkey) ? true : false;
    }

    /**
     * Validates Encryption key
     * @param string $encryptionkey
     * @return array
     */
    public function validate_encryption_key($encryptionkey) {
        if ($this->is_valid_encryption_key($encryptionkey)) {
            return array(
                'success'   => true,
                'message'   => get_string('connectionsuccessful', 'auth_lbssomoodle')
            );
        }
        return array(
            'success'   => false,
            'message'   => get_string('invalidencryptionkey', 'auth_lbssomoodle')
        );
    }

    /**
     * Validates if Request is authorized or not
     * @param int $userid
     * @param string $code
     * @return boolean
     */
    public function is_authorized_request($userid, $code) {
        $decryptedhash = $this->retrive_params_from_hashdata($userid, $code);
        if ($decryptedhash['hash'] != $code) {
            return false;
        }
        return true;
    }

    /**
     * Retrive parameters from hased data
     * @param int $userid
     * @param string $privatekey
     * @return array $response
     */
    public function retrive_params_from_hashdata($userid, $privatekey){
        $hash = $this->get_lbsso_user_session($userid);
        return unserialize($this->decrypt_data($hash, $privatekey));
    }

    /**
     * Gets User Session Information
     * @param int $userid
     * @return string $hash
     */
    public function get_lbsso_user_session($userid) {
        global $DB;
        $hash = $DB->get_field('user_preferences', 'value',
            array(
                'userid' => $userid,
                'name' => 'lbsso_onetime_hash'
            ), IGNORE_MISSING);
        return $hash;
    }

    /**
     * Deletes user session from database upon logout request
     * @param int $userid
     */
    public function unset_lbsso_user_session($userid) {
        global $DB;
        $DB->delete_records('user_preferences', array('userid' => $userid, 'name' => 'ebsso_onetime_hash'));
        return true;
    }

    /**
     * Insets lbsso User session into database
     * @param int $userid
     * @param string $hashdata
     * @return boolean
     */
    public function set_lbsso_user_session($userid, $hashdata) {
        return set_user_preference('lbsso_onetime_hash', $hashdata, $userid) ? true : false;
    }

    /**
     * Checks if Laravel user is linked
     * @param int $mdluserid
     * @return boolean
     */
    public function laravel_user_exists($mdluserid) {
        global $DB;
        $cmsuser = $DB->get_field('auth_lbsso_user_map', 'id',
            array('userid' => $mdluserid),
            IGNORE_MISSING);
        return $cmsuser ? true : false;
    }

    /**
     * Get Laravel User Id
     * @param int $mdluserid
     * @return int $cmsid
     */
    public function get_laravel_userid($mdluserid) {
        global $DB;
        $cmsid = $DB->get_field('auth_lbsso_user_map', 'laravel_userid',
            array('userid' => $mdluserid),
            IGNORE_MISSING);
        return $cmsid;
    }
    /**
     * Inserts laravel user information into database
     * @param int $mdluserid
     * @param int @luserid
     * @return int $cmsuser
     */
    public function set_laravel_userid($mdluserid, $luserid) {
        global $DB;
        $data = new stdClass;
        $data->laravel_userid = $luserid;
        $data->userid = $mdluserid;
        if ($id = $this->laravel_user_exists($mdluserid)) {
            $data->id = $id;
            $cmsuser = $DB->update_record('auth_lbsso_user_map', $data, false);
        } else {
            $cmsuser = $DB->insert_record('auth_lbsso_user_map', $data, true, false);
        }
        return $cmsuser;
    }

    /**
     * Sets one time hash from api in laravel database
     * @param int $cmsuser
     * @param string $code
     * @return array $response
     */
    public function set_onetime_hash_laravel($cmsuser, $hashdata) {
        
    }

    /**
     * Creates User on laravel database
     * @param array $payload
     * @return array $response
     */
    public function create_user_on_laravel($payload) {
       
    }

    /**
     * Encrypt Data
     * @param string $data
     * @param string $privatekey
     * @return string $encrypteddata
     */
    public function encrypt_data($data, $privatekey) {
        $secretkey = $this->get_encryption_key();
        $key = hash('sha256', $privatekey);
        $hashhmac = substr(hash('sha256', $secretkey), 0, 16);
        $encrypteddata = openssl_encrypt($data, self::$encryptmethod, $key, 0, $hashhmac);
        return base64_encode($encrypteddata);
    }

    /**
     * Decrypt Data
     * @param string $encrypteddata
     * @param string $privatekey
     * @return string $data
     */
    public function decrypt_data($encrypteddata, $privatekey) {
        $secretkey = $this->get_encryption_key();
        $key = hash('sha256', $privatekey);
        $hashhmac = substr(hash('sha256', $secretkey), 0, 16);
        $data = openssl_decrypt(base64_decode($encrypteddata), self::$encryptmethod, $key, 0, $hashhmac);
        return $data;
    }
}
