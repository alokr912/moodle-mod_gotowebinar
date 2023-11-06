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
 * GoToWebinar OAuth  file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gotowebinar;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/filelib.php');

use curl;

/**
 * GoToWebinat OAuthentication file.
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GotoOAuth {

    public const BASE_URL = "https://api.getgo.com";
    public const OAUTH_URL = "https://authentication.logmeininc.com";
    public const PLUGIN_NAME = "gotowebinar";
    public const ACCESS_TOKEN = "access_token";
    public const REFRESH_TOKEN = "refresh_token";
    public const ORGANISER_KEY = "organizer_key";
    public const ACCOUNT_KEY = "account_key";
    public const ACCESS_TOKEN_TIME = "access_token_time";
    public const EXPIRY_TIME_IN_SECOND = 3500;

    private $accesstoken;
    private $refreshtoken;
    public $organizerkey;
    private $accountkey;
    private $accesstokentime;
    private $consumerkey;
    private $consumersecret;
    private $curl;

    /**
     * 
     * @global type $CFG
     * @param type $code
     * @return type
     */
    public function getaccesstokenwithcode($code) {
        global $CFG;

        $pluginconfig = get_config(self::PLUGIN_NAME);
        $authorization = base64_encode($pluginconfig->consumer_key . ":" . $pluginconfig->consumer_secret);
        $headers = [
            'Authorization: Basic ' . $authorization,
            'Accept:application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];
        $this->curl->setHeader($headers);

        $redirecturl = $CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php';
        $data = ['redirect_uri' => $redirecturl, 'grant_type' => 'authorization_code', 'code' => $code];
        $serveroutput = $this->curl->post(self::OAUTH_URL . '/oauth/token', self::encode_attributes($data));

        $response = json_decode($serveroutput);
        return $this->update_access_token($response);
    }
    
    /**
     * 
     * @param type $refreshtoken
     * @return boolean
     */
    public function getaccesstokenwithrefreshtoken($refreshtoken) {
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];

        $this->curl->setHeader($headers);
        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshtoken];

        $serveroutput = $this->curl->post(self::OAUTH_URL . '/oauth/token', self::encode_attributes($data));

        $response = json_decode($serveroutput);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) &&
                isset($response->organizer_key) && isset($response->account_key)) {
            $this->update_access_token($response);
            $this->accesstoken = $response->access_token;
            $this->refreshtoken = $response->refresh_token;

            $this->accesstokentime = time();

            return $response->access_token;
        }
        return false;
    }
    
    /**
     * 
     * @return type
     */
    public function getaccesstoken() {

        if (isset($this->accesstokentime) && !empty($this->accesstokentime) &&
                $this->accesstokentime + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $this->accesstoken;
        } else {
            return $this->getaccesstokenwithrefreshtoken($this->refreshtoken);
        }
    }
    
    /**
     * 
     * @param type $endpoint
     * @param type $data
     * @return type
     */
    public function post($endpoint, $data) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];

        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->post(self::BASE_URL . $endpoint, json_encode($data));

        return json_decode($serveroutput);
    }
    
    /**
     * 
     * @param type $endpoint
     * @param type $data
     * @return boolean
     */
    public function put($endpoint, $data) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->put(self::BASE_URL . $endpoint, json_encode($data));

        $result = json_decode($serveroutput);
        return true;
    }
    
    /**
     * 
     * @param type $endpoint
     * @return type
     */
    public function get($endpoint) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->get(self::BASE_URL . $endpoint);

        return json_decode($serveroutput);
    }

    /**
     * 
     * @param type $endpoint
     * @param type $data
     * @return boolean
     */
    public function delete($endpoint, $data = null) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->delete(self::BASE_URL . $endpoint, json_encode($data));

        if (empty($serveroutput)) {
            return true;
        }
        return false;
    }
    
    /**
     * 
     * @return type
     */
    public function getsetupstatus() {

        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];
        $this->curl->setHeader($headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshtoken];

        $serveroutput = $this->curl->post(self::OAUTH_URL . "/oauth/token", self::encode_attributes($data));

        return json_decode($serveroutput);
    }

    /**
     * 
     * @param type $attributes
     * @return type
     */
    public static function encode_attributes($attributes) {

        $return = [];
        foreach ($attributes as $key => $value) {
            $return[] = urlencode($key) . '=' . urlencode($value);
        }
        return join('&', $return);
    }
    /**
     * 
     * @global type $DB
     * @param type $response
     * @return boolean
     */
    private function update_access_token($response) {
        global $DB;
        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) &&
                isset($response->organizer_key) && isset($response->account_key)) {
            $gotowebinarlicence = $DB->get_record('gotowebinar_licence', ['organizer_key' => $response->organizer_key]);

            if (!$gotowebinarlicence) {
                $gotowebinarlicence = new \stdClass();
                $gotowebinarlicence->email = $response->email;
                $gotowebinarlicence->first_name = $response->firstName;
                $gotowebinarlicence->last_name = $response->lastName;
                $gotowebinarlicence->access_token = $response->access_token;
                $gotowebinarlicence->refresh_token = $response->refresh_token;
                $gotowebinarlicence->token_type = $response->token_type;
                $gotowebinarlicence->expires_in = $response->expires_in;
                $gotowebinarlicence->account_key = $response->account_key;
                $gotowebinarlicence->organizer_key = $response->organizer_key;
                $gotowebinarlicence->timecreated = time();
                $gotowebinarlicence->timemodified = time();
                $gotowebinarlicence->access_token_time = time();
                $DB->insert_record('gotowebinar_licence', $gotowebinarlicence);
            } else {
                $gotowebinarlicence->access_token = $response->access_token;
                $gotowebinarlicence->refresh_token = $response->refresh_token;
                $gotowebinarlicence->timemodified = time();
                $gotowebinarlicence->access_token_time = time();

                $DB->update_record('gotowebinar_licence', $gotowebinarlicence);
            }

            return true;
        } else {
            return false;
        }
    }
    /**
     * 
     * @param type $accesstoken
     * @return type
     */
    private function getprofileinfo($accesstoken) {

        $headers = [
            'Authorization: Bearer ' . $accesstoken,
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);
        $serveroutput = $this->curl->get(self::BASE_URL . "/admin/rest/v1/me?includeAdmins=false&includeInvitation=false");

        return json_decode($serveroutput);
    }

}
