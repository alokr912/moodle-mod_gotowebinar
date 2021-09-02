<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace mod_gotowebinar;
class GoToOAuth {

    public const BASE_URL = "https://api.getgo.com";
    public const PLUGIN_NAME = "gotowebinar";
    public const ACCESS_TOKEN = "access_token";
    public const REFRESH_TOKEN = "refresh_token";
    public const ORGANISER_KEY = "organizer_key";
    public const ACCOUNT_KEY = "account_key";
    public const ACCESS_TOKEN_TIME = "access_token_time";
    public const EXPIRY_TIME_IN_SECOND = 3500;

    private $access_token;
    private $refresh_token;
    private $organizer_key;
    private $account_key;
    private $access_token_time;
    private $consumer_key;
    private $consumer_secret;

    function __construct() {

        $config = get_config(self::PLUGIN_NAME);
       

        if (isset($config) && !empty($config->access_token)) {
            $this->access_token = $config->access_token;
        }
        if (isset($config) && !empty($config->refresh_token)) {
            $this->refresh_token = $config->refresh_token;
        }
        if (isset($config) && !empty($config->consumer_key)) {
            $this->consumer_key = $config->consumer_key;
        }
        if (isset($config) && !empty($config->consumer_secret)) {
            $this->consumer_secret = $config->consumer_secret;
        }
        if (isset($config) && !empty($config->access_token_time)) {
            $this->access_token_time = $config->access_token_time;
        }
        if (isset($config) && !empty($config->account_key)) {
            $this->account_key = $config->account_key;
        }
        if (isset($config) && !empty($config->organizer_key)) {
            $this->organizer_key = $config->organizer_key;
        }
    }

    public function getAccessTokenWithCode($code) {
        global $CFG;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
             
        $headers = [
            'Authorization: Basic ' . base64_encode($this->consumer_key . ":" . $this->consumer_secret),
            'Accept:application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // echo $authorization;
        $redirect_url = $CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php';
        $data = ['redirect_uri' => $redirect_url, 'grant_type' => 'authorization_code', 'code' => $code];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

       // curl_setopt($ch, CURLOPT_VERBOSE, true);
        $server_output = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($server_output);


        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) && isset($response->organizer_key) && isset($response->account_key)) {
            $this->update_settings($response->access_token, $response->refresh_token,
                    $response->organizer_key, $response->account_key);
            return true;
        } else {
            return false;
        }
    }

    public function getAccessTokenWithRefreshToken($refreshToken) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);

        $headers = [
            'Authorization: Basic ' . base64_encode($this->consumer_key . ":" . $this->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($server_output);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) && isset($response->organizer_key) && isset($response->account_key)) {
            $this->update_settings($response->access_token, $response->refresh_token,
                    $response->organizer_key, $response->account_key);

            return $response->access_token;
        }
        return false;
    }

    function getAccessToken() {
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);
        if (isset($gotowebinarconfig->access_token_time) && !empty($gotowebinarconfig->access_token_time) && $gotowebinarconfig->access_token_time + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $gotowebinarconfig->access_token;
        } else {
            return $this->getAccessTokenWithRefreshToken($gotowebinarconfig->refresh_token);
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

        // echo $authorization;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        
        curl_close($ch);
       // if ($chinfo['http_code'] == 202) {
            //return json_decode($server_output);
       // }
        return json_decode($server_output);
    }

    public function put($endpoint, $data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");


        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // echo $authorization;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);
        if ($chinfo['http_code'] == 202) {
            return true;
        }

        return false;
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

        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);
        if ($chinfo['http_code'] == 200) {
            return json_decode($server_output);
        }
        return false;
    }

    public function delete($endpoint) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
       

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        if($chinfo['http_code']){
            
        }
        curl_close($ch);

        $result = json_decode($server_output);
    }

    public function getSetupStatus() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);

        if (empty($this->consumer_key) || empty($this->consumer_secret) || empty($this->refresh_token)) {
            return false;
        }

        $headers = [
            'Authorization: Basic ' . base64_encode($this->consumer_key . ":" . $this->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refresh_token];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);


        if ($chinfo['http_code'] === 200) {

            return json_decode($server_output);
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

    public function update_settings($access_token, $refresh_token, $organizer_key, $account_key) {

        set_config(self::ACCESS_TOKEN, $access_token, self::PLUGIN_NAME);
        set_config(self::REFRESH_TOKEN, $refresh_token, self::PLUGIN_NAME);
        set_config(self::ACCESS_TOKEN_TIME, time(), self::PLUGIN_NAME);
        set_config(self::ORGANISER_KEY, $organizer_key, self::PLUGIN_NAME);
        set_config(self::ACCOUNT_KEY, $account_key, self::PLUGIN_NAME);


        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
        $this->organizer_key = $organizer_key;
        $this->account_key = $account_key;

        $this->access_token_time = time();
    }

}
