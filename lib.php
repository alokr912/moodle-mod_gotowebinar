<?php


/**
 * GoToWebinar module  library file 
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
//require_once 'locallib.php';
require_once $CFG->dirroot . '/calendar/lib.php';

//ini_set('display_errors', 1);
//ini_set('error_reporting',E_ALL);
function gotowebinar_add_instance($data, $mform = null) {

    global $USER, $DB;

    $response = createGoToWebibnar($data);
   
    if ($response && $response->status == 201) {
        $data->userid = $USER->id;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->meetinfo = trim($response->body, '"');
        $jsonresponse = json_decode($response->body);
        $data->webinarkey = $jsonresponse->webinarKey;    


        $data->id = $DB->insert_record('gotowebinar', $data);
    }
   
    if (!empty($data->id)) {
        // Add event to calendar
        $event = new stdClass();
        $event->name = $data->name;
        $event->description = $data->intro;
        $event->courseid = $data->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->instance = $data->id;
        $event->eventtype = 'course';
        $event->timestart = $data->startdatetime;
        $event->timeduration = $data->enddatetime - $data->startdatetime;
        $event->visible = 1;
        $event->modulename = 'gotowebinar';
        calendar_event::create($event);
        
        $event = \mod_gotowebinar\event\gotowebinar_created::create(array(
                'objectid' => $data->id,
                'context' => context_module::instance($data->coursemodule),
                'other' => array('modulename' => $data->name, 'startdatetime' => $data->startdatetime),
    ));
    $event->trigger();
     return $data->id;
    }else {
        return FALSE;
    }


    

   
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function gotowebinar_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return false;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return false;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE: return false;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_COMPLETION_HAS_RULES: return false;
        default: return null;
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function gotowebinar_update_instance($gotowebinar) {

    global $DB;
    if (!$oldgotowebinar = $DB->get_record('gotowebinar', array('id' => $gotowebinar->instance))) {
        return false;
    }
    $result = false;
    if ($oldgotowebinar->meetingtype == 'gotomeeting' && $gotowebinar->meetingtype == 'gotomeeting') {
        $result = updateGoToMeeting($oldgotowebinar, $gotowebinar);
    } else if ($oldgotowebinar->meetingtype == 'gotowebinar' && $gotowebinar->meetingtype == 'gotowebinar') {
        $result = updateGoToWebinar($oldgotowebinar, $gotowebinar);
    } else if ($oldgotowebinar->meetingtype == 'gototraining' && $gotowebinar->meetingtype == 'gototraining') {
        $result = updateGoToTraining($oldgotowebinar, $gotowebinar);
    }
    if ($result) {

        $oldgotowebinar->name = $gotowebinar->name;
        $oldgotowebinar->intro = $gotowebinar->intro;
        $oldgotowebinar->startdatetime = $gotowebinar->startdatetime;
        $oldgotowebinar->enddatetime = $gotowebinar->enddatetime;
        $oldgotowebinar->timemodified = time();
        $DB->update_record('gotowebinar', $oldgotowebinar);
        $param = array('courseid' => $gotowebinar->course, 'instance' => $gotowebinar->instance,
            'groupid' => 0, 'modulename' => 'gotowebinar');

        $eventid = $DB->get_field('event', 'id', $param);

        if (!empty($eventid)) {

            $event = new stdClass();
            $event->id = $eventid;
            $event->name = $gotowebinar->name;
            $event->description = $gotowebinar->intro;
            $event->courseid = $gotowebinar->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->instance = $gotowebinar->instance;
            $event->eventtype = 'course';
            $event->timestart = $gotowebinar->startdatetime;
            $event->timeduration = $gotowebinar->enddatetime - $gotowebinar->startdatetime;
            $event->visible = 1;
            $event->modulename = 'gotowebinar';
            $calendarevent = calendar_event::load($eventid);
            $calendarevent->update($event);
        }
    }
    $event = \mod_gotowebinar\event\gotowebinar_updated::create(array(
                'objectid' => $gotowebinar->instance,
                'context' => context_module::instance($gotowebinar->coursemodule),
                'other' => array('modulename' => $gotowebinar->name, 'startdatetime' => $gotowebinar->startdatetime),
    ));
    $event->trigger();
    return $result;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $adobeconnect An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function gotowebinar_delete_instance($id) {
    global $DB, $CFG;

    $result = false;
    if (!$gotowebinar = $DB->get_record('gotowebinar', array('id' => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('gotowebinar', $id)) {
        return false;
    }
    $context = context_module::instance($cm->id);
    if ($gotowebinar->meetingtype == 'gotomeeting') {
        if (deleteGoToMeeting($gotowebinar->gotoid)) {
            $params = array('id' => $gotowebinar->id);
            $result = $DB->delete_records('gotowebinar', $params);
        }
    } else if ($gotowebinar->meetingtype == 'gotowebinar') {
        if (deleteGoToWebinar($gotowebinar->gotoid)) {
            $params = array('id' => $gotowebinar->id);
            $result = $DB->delete_records('gotowebinar', $params);
        }
    } else if ($gotowebinar->meetingtype == 'gototraining') {
        if (deleteGoToTraining((int) $gotowebinar->gotoid)) {
            $params = array('id' => $gotowebinar->id);
            $result = $DB->delete_records('gotowebinar', $params);
        }
    }
    // Delete calendar  event
    $param = array('courseid' => $gotowebinar->course, 'instance' => $gotowebinar->id,
        'groupid' => 0, 'modulename' => 'gotowebinar');

    $eventid = $DB->get_field('event', 'id', $param);
    if ($eventid) {
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    $event = \mod_gotowebinar\event\gotowebinar_deleted::create(array(
                'objectid' => $id,
                'context' => $context,
                'other' => array('modulename' => $gotowebinar->name, 'startdatetime' => $gotowebinar->startdatetime),
    ));


    $event->trigger();



    return $result;
}

/*
 * 
 * 
 * 
 */

function gotowebinar_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    $result = $type;
    if (!($gotowebinar = $DB->get_record('gotowebinar', array('id' => $cm->instance)))) {
        throw new Exception("Can't find GoToLMS {$cm->instance}");
    } // as of now it is not implemented will implement it soon 
    /* if ($gotowebinar->completionparticipation && $gotowebinar->completionparticipation > 0 && $gotowebinar->completionparticipation <= 100) {
      if ($gotowebinar->meetingtype == 'gotowebinar') {
      $config = get_config('gotowebinar');
      OSD::setup(trim($config->gotowebinar_consumer_key));
      OSD::authenticate_with_password(trim($config->gotowebinar_userid), trim($config->gotowebinar_password));
      } else if ($gotowebinar->meetingtype == 'gototraining') {
      $config = get_config('gotowebinar');
      OSD::setup(trim($config->gototraining_consumer_key));
      OSD::authenticate_with_password(trim($config->gototraining_userid), trim($config->gototraining_password));
      }
      } */
    return true;
}
