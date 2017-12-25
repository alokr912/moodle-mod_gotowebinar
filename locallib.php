<?php

/**
 * GoToWebinar module local library file 
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

function createGoToWebibnar($gotowebinar) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar');
    OSD::setup(trim($config->consumer_key));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));
    $attributes = array();
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);

    $times = array();
    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = array();
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' . $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' . $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = array($timearray);
    $attributes['timeZone'] = get_user_timezone();
    $attributes['type'] = 'single_session';
    $attributes['isPasswordProtected'] = 'false';

    $key = OSD::$oauth->organizer_key;

    $response = OSD::post("/G2W/rest/organizers/{$key}/webinars", $attributes);

    if ($response && $response->status == 201) {
        return $response;
    }
    return false;
}

function updateGoToWebinar($oldgotowebinar, $gotowebinar) {


    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar'); 
    OSD::setup(trim($config->consumer_key));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));
    $attributes = array();
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);
    $attributes['timeZone'] = get_user_timezone();
    $times = array();

    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = array();
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' . $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' . $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = array($timearray);
    $key =  OSD::$oauth->organizer_key;

    $response = OSD::request('PUT', "/G2W/rest/organizers/{$key}/webinars/{$oldgotowebinar->webinarkey}", $attributes);

    if ($response && $response->status == 202) {
        return true;
    }
    return false;
}

function deleteGoToWebinar($webinarkey) {
    global $USER, $DB, $CFG;
   
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar');
    OSD::setup(trim($config->consumer_key));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));
    $key =  OSD::$oauth->organizer_key;
    $responce = OSD::request('DELETE', "/G2W/rest/organizers/{$key}/webinars/{$webinarkey}");
    if ($responce->status == 204) {
        return true;
    } else {
       
        return false;
    }
}

function get_gotowebinar($gotowebinar) {

    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar');
    $context = context_course::instance($gotowebinar->course);
    OSD::setup(trim($config->consumer_key));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));
    $organiser_key = OSD::$oauth->organizer_key;
    if (is_siteadmin() OR has_capability('mod/gotowebinar:organiser', $context) OR has_capability('mod/gotowebinar:presenter', $context)) {
        $response = OSD::get("/G2W/rest/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers");

        if ($response && $response->body != '[]') {
            $coorganisers = json_decode($response->body);
            foreach ($coorganisers as $coorganiser) {
                if ($coorganiser->email == $USER->email) {
                    return $coorganiser->joinLink;
                }
            }
        } else {// No co organiser found , create one
            $attributes = array(array('external' => true, 'organizerKey' => $organiser_key, 'givenName' => fullname($USER), 'email' => $USER->email));
            $response = OSD::post("/G2W/rest/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers", $attributes);

            if ($response && $response->status == '201') {
                $coorganiser = json_decode($response->body);
                return $coorganiser[0]->joinLink;
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


        $response = OSD::post("/G2W/rest/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/registrants", $attributes);

        if ($response && $response->status == 201) {
            $registrstioninfo = json_decode($response->body);
            $gotowebinar_registrant = new stdClass();
            $gotowebinar_registrant->course = $gotowebinar->course;
            $gotowebinar_registrant->instanceid = '';
            $gotowebinar_registrant->joinurl = $registrstioninfo->joinUrl;
            $gotowebinar_registrant->registrantkey = $registrstioninfo->registrantKey;
            $gotowebinar_registrant->userid = $USER->id;
            $gotowebinar_registrant->webinarkey = $gotowebinar->webinarkey;
            $gotowebinar_registrant->timecreated = time();
            $gotowebinar_registrant->timemodified = time();
            $gotowebinar_registrant->id = $DB->insert_record('gotowebinar_registrant', $gotowebinar_registrant);

            return $registrstioninfo->joinUrl;
        }
    }
}

function get_gotowebinarinfo($gotowebinar) {
    global $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar');
    $context = context_course::instance($gotowebinar->course);
    OSD::setup(trim($config->gotowebinar_consumer_key));
    OSD::authenticate_with_password(trim($config->gotowebinar_userid), trim($config->gotowebinar_password));
    $organiser_key = OSD::$oauth->organizer_key;
}
