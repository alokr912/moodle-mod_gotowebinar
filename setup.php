<?php

/**
 * GoToMeeting module configtest  file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('./classes/gotooauth.class.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotowebinar/setup.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading('GoToMeeting config test report');
$PAGE->set_title('GoToMeeting config test report');
require_login();

if (!is_siteadmin()) {
    print_error('nopermissions', 'gotowebinar', '', null);
}

$gotowebinarconfig = get_config('gotowebinar');
$goToAuth = new mod_gotowebinar\GoToOAuth();

if (isset($gotowebinarconfig->consumer_key) && $gotowebinarconfig->consumer_key != '' && isset($gotowebinarconfig->consumer_secret) && $gotowebinarconfig->consumer_secret != '') {

    $redirect_url = $CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php';
    $url = mod_gotowebinar\GoToOAuth::BASE_URL . "/oauth/v2/authorize?client_id=$gotowebinarconfig->consumer_key&response_type=code&redirect_uri=$redirect_url";

    redirect($url);
} else {

    echo $OUTPUT->header();

    echo html_writer::div('GoToMeeting config validation ', 'alert alert-info');

    $consumerKey = trim($gotowebinarconfig->consumer_key);
    if (isset($gotowebinarconfig->consumer_key) && $gotowebinarconfig->consumer_key == '') {


        echo html_writer::div('GoToMeeting consumer key missing', 'alert alert-danger');
    }
    if (isset($gotowebinarconfig->consumer_secret) && $gotowebinarconfig->consumer_secret == '') {

        echo html_writer::div('GoToMeeting consumer secert missing', 'alert alert-danger');
    }
}