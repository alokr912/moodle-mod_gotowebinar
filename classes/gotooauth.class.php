<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
            $gotowebinar_licence = $DB->get_record('gotowebinar_licence', array('organizer_key' => $response->organizer_key));

            if (!$gotowebinar_licence) {
                $gotowebinar_licence = new \stdClass();
                $gotowebinar_licence->email = $response->email;
                $gotowebinar_licence->first_name = $response->firstName;
                $gotowebinar_licence->last_name = $response->lastName;
                $gotowebinar_licence->access_token = $response->access_token;
                $gotowebinar_licence->refresh_token = $response->refresh_token;
                $gotowebinar_licence->token_type = $response->token_type;
                $gotowebinar_licence->expires_in = $response->expires_in;
                $gotowebinar_licence->account_key = $response->account_key;
                $gotowebinar_licence->organizer_key = $response->organizer_key;
                $gotowebinar_licence->timecreated = time();
                $gotowebinar_licence->timemodified = time();
                $gotowebinar_licence->access_token_time = time();
                $DB->insert_record('gotowebinar_licence', $gotowebinar_licence);
            } else {
                $gotowebinar_licence->access_token = $response->access_token;
                $gotowebinar_licence->refresh_token = $response->refresh_token;
                $gotowebinar_licence->timemodified = time();
                $gotowebinar_licence->access_token_time = time();

                $DB->update_record('gotowebinar_licence', $gotowebinar_licence);
            }

            return true;
        } else {
            return false;
        }
    }

}
