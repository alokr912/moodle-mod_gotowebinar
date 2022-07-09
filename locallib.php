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
 * GoToWebinar module local library file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotoOAuth.php';
require_once($CFG->dirroot . '/lib/completionlib.php');

function creategotowebibnar($gotowebinar) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToWebinar setup");
    }

    $attributes = array();
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);

    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = array();
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' . $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' . $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = array($timearray);
    $attributes['timeZone'] = get_user_timezone();
    $attributes['type'] = 'single_session';
    $attributes['isPasswordProtected'] = 'false';

    $emailsettings = array();
    if (!empty($gotowebinar->confirmationemail)) {
        $emailsettings['confirmationEmail'] = array('enabled' => true);
    } else {
        $emailsettings['confirmationEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->reminderemail)) {
        $emailsettings['reminderEmail'] = array('enabled' => true);
    } else {
        $emailsettings['reminderEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->absenteefollowupemail)) {
        $emailsettings['absenteeFollowUpEmail'] = array('enabled' => true);
    } else {
        $emailsettings['absenteeFollowUpEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->attendeefollowupemail)) {
        $emailsettings['attendeeFollowUpEmail'] = array('enabled' => true, 'includeCertificate' => true);
    } else {
        $emailsettings['attendeeFollowUpEmail'] = array('enabled' => false);
    }

    $attributes['emailSettings'] = $emailsettings;

    $key = $gotooauth->organizerkey;

    $response = $gotooauth->post("/G2W/rest/v2/organizers/{$key}/webinars", $attributes);

    if ($response && !empty($response->webinarKey)) {
        return $response->webinarKey;
    }
    return false;
}

function updateGoToWebinar($oldgotowebinar, $gotowebinar) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';

    $gotooauth = new mod_gotowebinar\GoToOAuth($oldgotowebinar->gotowebinar_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToWebinar setup");
    }

    $attributes = array();
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);
    $attributes['timeZone'] = get_user_timezone();

    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = array();
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' .
            $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' .
            $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = array($timearray);
    $emailsettings = array();
    if (!empty($gotowebinar->confirmationemail)) {
        $emailsettings['confirmationEmail'] = array('enabled' => true);
    } else {
        $emailsettings['confirmationEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->reminderemail)) {
        $emailsettings['reminderEmail'] = array('enabled' => true);
    } else {
        $emailsettings['reminderEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->absenteefollowupemail)) {
        $emailsettings['absenteeFollowUpEmail'] = array('enabled' => true);
    } else {
        $emailsettings['absenteeFollowUpEmail'] = array('enabled' => false);
    }
    if (!empty($gotowebinar->attendeefollowupemail)) {
        $emailsettings['attendeeFollowUpEmail'] = array('enabled' => true, 'includeCertificate' => true);
    } else {
        $emailsettings['attendeeFollowUpEmail'] = array('enabled' => false);
    }

    $attributes['emailSettings'] = $emailsettings;

    $key = $gotooauth->organizerkey;

    $response = $gotooauth->put("/G2W/rest/v2/organizers/{$key}/webinars/{$oldgotowebinar->webinarkey}", $attributes);
    if ($response) {

        return true;
    }
    return false;
}

function deleteGoToWebinar($gotoid, $licence) {
    global $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
    $gotooauth = new mod_gotowebinar\GoToOAuth($licence);

    $key = $gotooauth->organizerkey;
    $responce = $gotooauth->delete("/G2W/rest/v2/organizers/{$key}/webinars/{$gotoid}", null);

    if ($responce) {
        return true;
    } else {
        return false;
    }
}

function get_gotowebinar($gotowebinar) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);

    $context = context_course::instance($gotowebinar->course);
    $organiser_key = $gotooauth->organizerkey;

    if (has_capability('mod/gotowebinar:organiser', $context) OR has_capability('mod/gotowebinar:presenter', $context)) {
        $coorganisers = $gotooauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers");

        if ($coorganisers) {

            foreach ($coorganisers as $coorganiser) {
                if ($coorganiser->email == $USER->email) {
                    return $coorganiser->joinLink;
                }
            }
        } else {// No co organiser found , create one
            $attributes = array(array('external' => true, 'organizerKey' => $organiser_key, 'givenName' => fullname($USER), 'email' => $USER->email));
            $response = $gotooauth->post("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers", $attributes);

            if ($response) {



                return $response[0]->joinLink;
            }
        }
    }
    // Now register and check registrant
    $registrant = $DB->get_record('gotowebinar_registrant', array('userid' => $USER->id, 'gotowebinarid' => $gotowebinar->webinarkey));

    if ($registrant) {
        return $registrant->joinurl;
    } else {
        $attributes = array();
        $attributes['firstName'] = $USER->firstname;
        $attributes['lastName'] = $USER->lastname;
        $attributes['email'] = $USER->email;
        $attributes['source'] = '';
        $attributes['address'] = '';
        $attributes['city'] = $USER->city;
        $attributes['state'] = '';
        $attributes['zipCode'] = '';
        $attributes['country'] = $USER->country;
        $attributes['phone'] = '';
        $attributes['organization'] = '';
        $attributes['jobTitle'] = '';
        $attributes['questionsAndComments'] = '';
        $attributes['industry'] = '';
        $attributes['numberOfEmployees'] = '';
        $attributes['purchasingTimeFrame'] = '';
        $attributes['purchasingRole'] = '';
        $attributes['responses'] = array(array('questionKey' => 0, 'responseText' => '', 'answerKey' => 0));
        $response = $gotooauth->post("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/registrants", $attributes);

        if (isset($response) && isset($response->registrantKey) && isset($response->joinUrl)) {



            $gotowebinar_registrant = new stdClass();
            $gotowebinar_registrant->course = $gotowebinar->course;
            $gotowebinar_registrant->instanceid = '';
            $gotowebinar_registrant->joinurl = $response->joinUrl;
            $gotowebinar_registrant->registrantkey = $response->registrantKey;
            $gotowebinar_registrant->userid = $USER->id;
            $gotowebinar_registrant->gotowebinarid = $gotowebinar->webinarkey;
            $gotowebinar_registrant->timecreated = time();
            $gotowebinar_registrant->timemodified = time();
            $gotowebinar_registrant->id = $DB->insert_record('gotowebinar_registrant', $gotowebinar_registrant);

            return $response->joinUrl;
        }
    }
}

function get_gotowebinarinfo($gotowebinar) {

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
    $organiser_key = $gotooauth->organizerkey;
    return $gotooauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}");
}

function get_gotowebinar_attendance1() {
    global $USER, $DB, $CFG;

    $gotooauth = new mod_gotowebinar\GoToOAuth();
    $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
    $organiser_key = $config->organizer_key;
    $response = $gotooauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/3633309102739548429/attendees");
    if (!empty($response) && !empty($response->_embedded) && !empty($response->_embedded->attendeeParticipationResponses)) {
        foreach ($response->_embedded->attendeeParticipationResponses as $at) {
            print_object($at);
        }
    }
}

function get_gotowebinar_audio_info($webinarkey, $license) {
    global $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
    $gotooauth = new mod_gotowebinar\GoToOAuth($license);

    $organiser_key = $gotooauth->organizerkey;
    $audio_info = $gotooauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$webinarkey}/audio");

    if ($audio_info && $audio_info->confCallNumbers && $audio_info->confCallNumbers->IT->toll) {
        $response['toll'] = $audio_info->confCallNumbers->IT->toll;
    }
    if ($audio_info && $audio_info->confCallNumbers && $audio_info->confCallNumbers->IT && $audio_info->confCallNumbers->IT->accessCodes && $audio_info->confCallNumbers->IT->accessCodes->organizer) {
        $audio_info->confCallNumbers->IT->accessCodes->organizer;

        $response['organizer_accesscode'] = $audio_info->confCallNumbers->IT->accessCodes->organizer;
        $response['attendee_accesscode'] = $audio_info->confCallNumbers->IT->accessCodes->attendee;
        $response['panelist_accesscode'] = $audio_info->confCallNumbers->IT->accessCodes->panelist;
    }

    return $response;
}

function sync_gotowebinar_completion_status() {
    global $DB;
    $start_time = time();
    $enddatetime1 = $enddatetime2 = time() - 15 * 60;
    $filter = array('enddatetime1' => $enddatetime1, 'enddatetime2' => $enddatetime2);
    $sql = "SELECT * FROM {gotowebinar}  enddatetime>=:enddatetime1 and enddatetime<=:enddatetime2 ";
    $webinars = $DB->get_records_sql($sql, $filter);
    foreach ($webinars as $webinar) {
        get_gotowebinar_attendance();
    }
}

function sync_gotowebinar_registration() {
    
}

function get_gotowebinar_attendance() {
    global $DB;
    $gotowebinars = $DB->get_records('gotowebinar');

    foreach ($gotowebinars as $gotowebinar) {
        $course = get_course($gotowebinar->course);
        $completion = new completion_info($course);

        $cm = get_coursemodule_from_instance('gotowebinar', $gotowebinar->id);

        if (!$completion->is_enabled($cm) || empty($gotowebinar->completionparticipation)) {
            echo "Completion not enabled<br>";
            continue;
        }

        $required_duration = (($gotowebinar->enddatetime - $gotowebinar->startdatetime) * $gotowebinar->completionparticipation) / 100;

        $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
        $organiser_key = $gotooauth->organizerkey;
        $webinarkey = $gotowebinar->webinarkey;
        $response = $gotooauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$webinarkey}/attendees");
        foreach ($response->_embedded->attendeeParticipationResponses as $at) {

            $gotowebinar_registrant = $DB->get_record('gotowebinar_registrant', array('registrantkey' => $at->registrantKey));

            if ($gotowebinar_registrant && $required_duration <= $at->attendanceTimeInSeconds) {
                echo "Marking completion for ==>$gotowebinar_registrant->userid in CMID==>$cm->id";

                $completion->update_state($cm, COMPLETION_COMPLETE, $gotowebinar_registrant->userid);
            }
        }
    }
}
