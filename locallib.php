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

require_once($CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

/**
 * This function create GoToWebinar meeting instance.
 * @param mixed $gotowebinar
 * @return boolean
 * @throws moodle_exception
 */
function creategotowebibnar($gotowebinar) {

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotowebinar');
    }

    $attributes = [];
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);

    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = [];
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' .
            $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' .
            $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = [$timearray];
    $attributes['timeZone'] = get_user_timezone();
    $attributes['type'] = 'single_session';
    $attributes['isPasswordProtected'] = 'false';

    $emailsettings = [];
    if (!empty($gotowebinar->confirmationemail)) {
        $emailsettings['confirmationEmail'] = ['enabled' => true];
    } else {
        $emailsettings['confirmationEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->reminderemail)) {
        $emailsettings['reminderEmail'] = ['enabled' => true];
    } else {
        $emailsettings['reminderEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->absenteefollowupemail)) {
        $emailsettings['absenteeFollowUpEmail'] = ['enabled' => true];
    } else {
        $emailsettings['absenteeFollowUpEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->attendeefollowupemail)) {
        $emailsettings['attendeeFollowUpEmail'] = ['enabled' => true, 'includeCertificate' => true];
    } else {
        $emailsettings['attendeeFollowUpEmail'] = ['enabled' => false];
    }

    $attributes['emailSettings'] = $emailsettings;

    $key = $gotooauth->organizerkey;

    $response = $gotooauth->post("/G2W/rest/v2/organizers/{$key}/webinars", $attributes);

    if ($response && !empty($response->webinarKey)) {
        return $response->webinarKey;
    }
    return false;
}

/**
 * This function update GoToWebinar meeting instance.
 * @param array $oldgotowebinar
 * @param array $gotowebinar
 * @return boolean
 * @throws moodle_exception
 */
function updategotowebinar($oldgotowebinar, $gotowebinar) {

    $gotooauth = new mod_gotowebinar\GoToOAuth($oldgotowebinar->gotowebinar_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotowebinar');
    }

    $attributes = [];
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);
    $attributes['timeZone'] = get_user_timezone();

    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = [];
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' .
            $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' .
            $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = [$timearray];
    $emailsettings = [];
    if (!empty($gotowebinar->confirmationemail)) {
        $emailsettings['confirmationEmail'] = ['enabled' => true];
    } else {
        $emailsettings['confirmationEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->reminderemail)) {
        $emailsettings['reminderEmail'] = ['enabled' => true];
    } else {
        $emailsettings['reminderEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->absenteefollowupemail)) {
        $emailsettings['absenteeFollowUpEmail'] = ['enabled' => true];
    } else {
        $emailsettings['absenteeFollowUpEmail'] = ['enabled' => false];
    }
    if (!empty($gotowebinar->attendeefollowupemail)) {
        $emailsettings['attendeeFollowUpEmail'] = ['enabled' => true, 'includeCertificate' => true];
    } else {
        $emailsettings['attendeeFollowUpEmail'] = ['enabled' => false];
    }

    $attributes['emailSettings'] = $emailsettings;

    $key = $gotooauth->organizerkey;

    $response = $gotooauth->put("/G2W/rest/v2/organizers/{$key}/webinars/{$oldgotowebinar->webinarkey}", $attributes);
    if ($response) {

        return true;
    }
    return false;
}

/**
 * This function delete GoToWebinar meeting instance.
 * @param int $gotoid
 * @param array $licence
 * @return boolean
 */
function deletegotowebinar($gotoid, $licence) {
    $gotooauth = new mod_gotowebinar\GoToOAuth($licence);

    $key = $gotooauth->organizerkey;
    $responce = $gotooauth->delete("/G2W/rest/v2/organizers/{$key}/webinars/{$gotoid}", null);

    if ($responce) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function Get GoToWebinar meeting instance.
 * @param array $gotowebinar
 * @return boolean
 */
function get_gotowebinar($gotowebinar) {
    global $USER, $DB;

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);

    $context = context_course::instance($gotowebinar->course);
    $$organiserkey = $gotooauth->organizerkey;

    if (has_capability('mod/gotowebinar:organiser', $context) || has_capability('mod/gotowebinar:presenter', $context)) {
        $coorganisers = $gotooauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$gotowebinar->webinarkey}/coorganizers");

        if ($coorganisers) {

            foreach ($coorganisers as $coorganiser) {
                if ($coorganiser->email == $USER->email) {
                    return $coorganiser->joinLink;
                }
            }
        } else {// No co organiser found , create one.
            $attributes = [['external' => true, 'organizerKey' => $organiserkey, 'givenName' => fullname($USER),
                    'email' => $USER->email, ], ];
            $response = $gotooauth->post("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$gotowebinar->webinarkey}/coorganizers",
                    $attributes);

            if ($response) {

                return $response[0]->joinLink;
            }
        }
    }
    // Now register and check registrant.
    $registrant = $DB->get_record('gotowebinar_registrant',
            ['userid' => $USER->id, 'gotowebinarid' => $gotowebinar->webinarkey]);

    if ($registrant) {
        return $registrant->joinurl;
    } else {
        $attributes = [];
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
        $attributes['responses'] = [['questionKey' => 0, 'responseText' => '', 'answerKey' => 0]];
        $response = $gotooauth->post("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$gotowebinar->webinarkey}/registrants",
                $attributes);

        if (isset($response) && isset($response->registrantKey) && isset($response->joinUrl)) {

            $gotowebinarregistrant = new stdClass();
            $gotowebinarregistrant->course = $gotowebinar->course;
            $gotowebinarregistrant->instanceid = '';
            $gotowebinarregistrant->joinurl = $response->joinUrl;
            $gotowebinarregistrant->registrantkey = $response->registrantKey;
            $gotowebinarregistrant->userid = $USER->id;
            $gotowebinarregistrant->gotowebinarid = $gotowebinar->webinarkey;
            $gotowebinarregistrant->timecreated = time();
            $gotowebinarregistrant->timemodified = time();
            $gotowebinarregistrant->id = $DB->insert_record('gotowebinar_registrant', $gotowebinarregistrant);

            return $response->joinUrl;
        }
    }
}

/**
 * This function get GoToWebinar meeting instance.
 * @param array $gotowebinar
 * @return boolean
 */
function get_gotowebinarinfo($gotowebinar) {

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
    $organiserkey = $gotooauth->organizerkey;
    return $gotooauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$gotowebinar->webinarkey}");
}

/**
 * This function create GoToWebinar meeting audio info.
 * @param string $webinarkey
 * @param int $license
 * @return array
 */
function get_gotowebinar_audio_info($webinarkey, $license) {

    $gotooauth = new mod_gotowebinar\GoToOAuth($license);

    $$organiserkey = $gotooauth->organizerkey;
    $audioinfo = $gotooauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$webinarkey}/audio");

    if ($audioinfo && $audioinfo->confCallNumbers && $audioinfo->confCallNumbers->IT->toll) {
        $response['toll'] = $audioinfo->confCallNumbers->IT->toll;
    }
    if ($audioinfo && $audioinfo->confCallNumbers && $audioinfo->confCallNumbers->IT &&
            $audioinfo->confCallNumbers->IT->accessCodes && $audioinfo->confCallNumbers->IT->accessCodes->organizer) {
        $audioinfo->confCallNumbers->IT->accessCodes->organizer;

        $response['organizer_accesscode'] = $audioinfo->confCallNumbers->IT->accessCodes->organizer;
        $response['attendee_accesscode'] = $audioinfo->confCallNumbers->IT->accessCodes->attendee;
        $response['panelist_accesscode'] = $audioinfo->confCallNumbers->IT->accessCodes->panelist;
    }

    return $response;
}

/**
 * This function create GoToWebinar meeting instance.
 */
function sync_gotowebinar_completion_status() {
    global $DB;
    $starttime = time();
    $enddatetime1 = $enddatetime2 = time() - 15 * 60;
    $filter = ['enddatetime1' => $enddatetime1, 'enddatetime2' => $enddatetime2];
    $sql = "SELECT * FROM {gotowebinar}  enddatetime>=:enddatetime1 and enddatetime<=:enddatetime2 ";
    $webinars = $DB->get_records_sql($sql, $filter);
    foreach ($webinars as $webinar) {
        get_gotowebinar_attendance();
    }
}

/**
 * This function create GoToWebinar meeting instance.
 */
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

        $requiredduration = (($gotowebinar->enddatetime - $gotowebinar->startdatetime) *
                $gotowebinar->completionparticipation) / 100;

        $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
        $organiserkey = $gotooauth->organizerkey;
        $webinarkey = $gotowebinar->webinarkey;
        $response = $gotooauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$webinarkey}/attendees");
        foreach ($response->_embedded->attendeeParticipationResponses as $at) {

            $gotowebinarregistrant = $DB->get_record('gotowebinar_registrant', ['registrantkey' => $at->registrantKey]);

            if ($gotowebinarregistrant && $requiredduration <= $at->attendanceTimeInSeconds) {
                echo "Marking completion for ==>$gotowebinarregistrant->userid in CMID==>$cm->id";

                $completion->update_state($cm, COMPLETION_COMPLETE, $gotowebinarregistrant->userid);
            }
        }
    }
}
