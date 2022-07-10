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
 * GoToMeeting module configtest  file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('./classes/gotooauth.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotowebinar/setup.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('setup', 'gotowebinar'));
$PAGE->set_title(get_string('setup', 'gotowebinar'));
require_admin();

$gotowebinarconfig = get_config('gotowebinar');

if (isset($gotowebinarconfig->consumer_key) && $gotowebinarconfig->consumer_key != '' &&
        isset($gotowebinarconfig->consumer_secret) && $gotowebinarconfig->consumer_secret != '') {

    $redirecturl = $CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php';
    $url = mod_gotowebinar\GoToOAuth::BASE_URL . "/oauth/v2/authorize?client_id="
            . "$gotowebinarconfig->consumer_key&response_type=code&redirect_uri=$redirecturl";

    redirect($url);
} else {

    echo $OUTPUT->header();
    echo html_writer::div(get_string('setup', 'gotowebinar'), 'alert alert-info');

    if (isset($gotowebinarconfig->consumer_key) && $gotowebinarconfig->consumer_key == '') {
        echo html_writer::div(get_string('keymissing', 'gotowebinar'), 'alert alert-danger');
    }
    if (isset($gotowebinarconfig->consumer_secret) && $gotowebinarconfig->consumer_secret == '') {

        echo html_writer::div(get_string('secretmissing', 'gotowebinar'), 'alert alert-danger');
    }
    echo $OUTPUT->footer();
}
