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

class GotoOAuth {

    public const BASE_URL = "https://api.getgo.com";
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

    public function __construct($licence_id = null) {
        global $DB;

        $licence = $DB->get_record('gotowebinar_licence', array('id' => $licence_id));

        if ($licence) {
            $this->organizerkey = !empty($licence->organizer_key) ? $licence->organizer_key : null;
            $this->refreshtoken = !empty($licence->refresh_token) ? $licence->refresh_token : null;
            $this->accesstoken = !empty($licence->access_token) ? $licence->access_token : null;
            $this->accesstokentime = !empty($licence->access_token_time) ? $licence->access_token_time : null;
        }
    }

    public function getaccesstokenwithcode($code) {
        global $CFG, $DB;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $pluginconfig = get_config(self::PLUGIN_NAME);
        $authorization = base64_encode($pluginconfig->consumer_key . ":" . $pluginconfig->consumer_secret);
        $headers = [
            'Authorization: Basic ' . $authorization,
            'Accept:application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $redirecturl = $CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php';
        $data = ['redirect_uri' => $redirecturl, 'grant_type' => 'authorization_code', 'code' => $code];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($serveroutput);
        return $this->update_access_token($response);
    }

    public function getaccesstokenwithrefreshtoken($refreshtoken) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshtoken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);
        curl_close($ch);

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

    public function getaccesstoken() {

        if (isset($this->access_token_time) && !empty($this->access_token_time) &&
                $this->access_token_time + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $this->accesstoken;
        } else {
            return $this->getaccesstokenwithrefreshtoken($this->refreshtoken);
        }
    }

    public function post($endpoint, $data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        return json_decode($serveroutput);
    }

    public function put($endpoint, $data) {
        global $CFG;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($serveroutput);
        return true;
    }

    public function get($endpoint) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        return json_decode($serveroutput);
    }

    public function delete($endpoint, $data=null) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        

        $headers = [
            'Authorization: Bearer ' .$this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if($data){
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));    
        }
       

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($serveroutput);
    }

    public function getsetupstatus() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshtoken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($chinfo['http_code'] === 200) {

            return json_decode($serveroutput);
        }

        return false;
    }

    public static function encode_attributes($attributes) {

        $return = array();
        foreach ($attributes as $key => $value) {
            $return[] = urlencode($key) . '=' . urlencode($value);
        }
        return join('&', $return);
    }

    private function update_access_token($response) {
        global $DB;
        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) &&
                isset($response->organizer_key) && isset($response->account_key)) {
            $gotowebinarlicence = $DB->get_record('gotowebinar_licence', array('organizer_key' => $response->organizer_key));

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

}
