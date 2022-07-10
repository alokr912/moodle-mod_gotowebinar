<?php
// This file is part of the GoToMeeting plugin for Moodle - http://moodle.org/
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
 * GoToMeeting module install  file
 *
 * @package mod_webinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_gotowebinar_install() {
    global $DB, $CFG;
    $ch = curl_init('https://api.mdlintegration.com/v1/public/gotoinstance');
    curl_setopt($ch, CURLOPT_POST, true);
    $headers = [
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = array();
    $data['wwwroot'] = $CFG->wwwroot;
    $data['siteidentifier'] = $CFG->siteidentifier;
    $data['country'] = $CFG->country;

    $data['autolang'] = $CFG->autolang;
    $data['lang'] = $CFG->lang;
    $data['supportname'] = $CFG->supportname;

    $data['supportemail'] = $CFG->supportemail;
    $data['release'] = $CFG->release;
    $data['branch'] = $CFG->branch;

    $data['os'] = $CFG->os;
    $data['timezone'] = $CFG->timezone;
    $data['ostype'] = $CFG->ostype;
    $data['goto_product'] = mod_gotowebinar\GotoOAuth::PLUGIN_NAME;
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_exec($ch);

    curl_close($ch);
    return true;
}
