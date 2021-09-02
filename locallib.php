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
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
     $goToOauth = new mod_gotowebinar\GoToOAuth();
     $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
     if(!isset( $config->organizer_key) || empty($config->organizer_key)){
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
    if(!empty($gotowebinar->)){
     $attributes['emailSettings']   
    }
    "emailSettings": {
"confirmationEmail": {
"enabled": true
},
"reminderEmail": {
"enabled": true
},
"absenteeFollowUpEmail": {
"enabled": true
},
"attendeeFollowUpEmail": {
"enabled": true,
"includeCertificate": true
}

    $key = $config->organizer_key;

    $response = $goToOauth->post("/G2W/rest/v2/organizers/{$key}/webinars", $attributes);
   
    if ($response && !empty($response->webinarKey)) {
        return $response->webinarKey;
    }
    return false;
}

function updateGoToWebinar($oldgotowebinar, $gotowebinar) {
    global $USER, $DB, $CFG;
     require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
     $goToOauth = new mod_gotowebinar\GoToOAuth();
     $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
  
    $attributes = array();
    $dstoffset = dst_offset_on($gotowebinar->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotowebinar->name;
    $attributes['description'] = clean_param($gotowebinar->intro, PARAM_NOTAGS);
    $attributes['timeZone'] = get_user_timezone();
    $times = array();
    $startdate = usergetdate(usertime($gotowebinar->startdatetime - $dstoffset));
    $timearray = array();
    $timearray['startTime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' .
        $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotowebinar->enddatetime - $dstoffset));
    $timearray['endTime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' .
        $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['times'] = array($timearray);
   
       $key = $config->organizer_key;

    $response = $goToOauth->put("/G2W/rest/v2/organizers/{$key}/webinars/{$oldgotowebinar->webinarkey}", $attributes);
    if ($response) {
        return true;
    }
    return false;
}

function deleteGoToWebinar($gotoid) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
     $goToOauth = new mod_gotowebinar\GoToOAuth();
    $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
    $key = $config->organizer_key;
    $responce = $goToOauth->delete("/G2W/rest/v2/organizers/{$key}/webinars/{$gotoid}");
   
   
    if ($responce) {
        return true;
    } else {
        return false;
    }
}

function get_gotowebinar($gotowebinar) {
    global $USER, $DB, $CFG;
     require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
     $goToOauth = new mod_gotowebinar\GoToOAuth();
      $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
    $context = context_course::instance($gotowebinar->course);
     $organiser_key = $config->organizer_key;
    
    if (has_capability('mod/gotowebinar:organiser', $context) OR has_capability('mod/gotowebinar:presenter', $context)) {
        $coorganisers = $goToOauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers");

        if ($coorganisers ) {
     
            foreach ($coorganisers as $coorganiser) {
                if ($coorganiser->email == $USER->email) {
                    return $coorganiser->joinLink;
                }
            }
        } else {// No co organiser found , create one
            $attributes = array(array('external' => true, 'organizerKey' => $organiser_key, 'givenName' => fullname($USER), 'email' => $USER->email));
            $response = $goToOauth->post("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/coorganizers", $attributes);
       
            if ($response ) {
               
             
               
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
        $response = $goToOauth->post("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$gotowebinar->webinarkey}/registrants", $attributes);
    
        if (isset($response) && isset($response->registrantKey)  && isset($response->joinUrl)) {
         
   
          
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
    global $CFG;
    require_once $CFG->dirroot . '/mod/gotowebinar/lib/OSD.php';
    $config = get_config('gotowebinar');
    $context = context_course::instance($gotowebinar->course);
    OSD::setup(trim($config->gotowebinar_consumer_key), trim($config->consumer_secret));
    OSD::authenticate_with_password(trim($config->gotowebinar_userid), trim($config->gotowebinar_password));
    $organiser_key = OSD::$oauth->organizer_key;
}


function get_gotowebinar_attendance(){
     global $USER, $DB, $CFG;
     require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
     $goToOauth = new mod_gotowebinar\GoToOAuth();
     $config = get_config(mod_gotowebinar\GoToOAuth::PLUGIN_NAME);
     $organiser_key = $config->organizer_key;
      $response = $goToOauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/3633309102739548429/attendees");
    if(!empty($response) && !empty($response->_embedded) 
        && !empty($response->_embedded->attendeeParticipationResponses)){
        foreach($response->_embedded->attendeeParticipationResponses as $at){
           print_object($at);  
        }
    }
    
   
}