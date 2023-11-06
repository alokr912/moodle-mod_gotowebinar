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
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../config.php');
require_once('./classes/gotooauth.php');
$code = required_param('code', PARAM_RAW);
require_admin();

$gotooauth = new mod_gotowebinar\GoToOAuth();
global $CFG;
$result = $gotooauth->getAccessTokenWithCode($code);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php', ['code' => $code]));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('oauth_status_heading', 'gotowebinar'));
$PAGE->set_title(get_string('oauth_status_title', 'gotowebinar'));
echo $OUTPUT->header();
$link = new moodle_url('/admin/settings.php', ['section' => 'modsettinggotowebinar']);
if ($result) {

    $successmessage = get_string('license_added_successfully', 'gotowebinar');

    notice($successmessage, $link);
} else {

    $failuremessage = get_string('license_added_failure', 'gotowebinar');
    notice($failuremessage, $link);
}

echo $OUTPUT->footer();

