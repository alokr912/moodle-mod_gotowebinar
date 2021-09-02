<?php

/**
 * GoToWebinar module config test file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotolms/configtest.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading('GoToLMS config test');
$PAGE->set_title('GoToLMS config test report');
 require_login();
echo $OUTPUT->header();
if (!is_siteadmin()) {
    print_error('nopermissions', 'gotolms', '', null);
}

echo $OUTPUT->heading('GoToLMS config test report');

$gotolmsconfig = get_config('gotowebinar');


echo html_writer::div('GoToWebinar config validation ', 'alert alert-info');
$validconsumerkey = true;
$validuserid = true;
$validpassword = true;
$validconsumersecret = true;
if (isset($gotolmsconfig->consumer_key) && $gotolmsconfig->consumer_key == '') {
    $validconsumerkey = false;
    echo html_writer::div('GoToWebinar consumer key missing', 'alert alert-danger');
}
if (isset($gotolmsconfig->consumer_secret) && $gotolmsconfig->consumer_secret == '') {
    $validconsumersecret = false;
    echo html_writer::div('GoToWebinar consumer secret is missing', 'alert alert-danger');
}
if (isset($gotolmsconfig->userid) && $gotolmsconfig->userid == '') {
    $validuserid = false;
    echo html_writer::div('GoToWebinar userid missing', 'alert alert-danger');
}
if (isset($gotolmsconfig->password) && $gotolmsconfig->password == '') {
    $validpassword = false;
    echo html_writer::div('GoToWebinar password missing', 'alert alert-danger');
}
if ($validconsumerkey && $validuserid && $validpassword) {
    OSD::setup(trim($gotolmsconfig->consumer_key), trim($gotolmsconfig->consumer_secret));
    if (OSD::authenticate_with_password(trim($gotolmsconfig->userid), trim($gotolmsconfig->password))) {
        $auth = OSD::$oauth;
        $content = 'Authentication successfull with '
            . '  organizer_key:  ' . $auth->organizer_key;
        echo html_writer::div($content, 'alert alert-success');
    } else {
        echo html_writer::div(OSD::$last_response->body, 'alert alert-danger');
    }
}

echo $OUTPUT->footer();
