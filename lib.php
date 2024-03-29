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
 * GoToWebinar module  library file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/calendar/lib.php');
require_once('locallib.php');

/**
 * This method provide course module info.
 * @param stdClass $coursemodule
 * @return \cached_cm_info
 */
function gotowebinar_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($gotowebinar = $DB->get_record('gotowebinar', ['id' => $coursemodule->instance], 'id, name, startdatetime')) {
        $info = new cached_cm_info();
        $info->name = $gotowebinar->name . "  " . userdate($gotowebinar->startdatetime, '%d/%m/%Y %H:%M');
        return $info;
    } else {
        return null;
    }
}

/**
 * Add GotoWebinar instance.
 * @param array $data
 * @param array $mform
 * @return boolean
 */
function gotowebinar_add_instance($data, $mform = null) {

    global $USER, $DB;

    $response = createGoToWebibnar($data);

    if ($response) {
        $data->userid = $USER->id;
        $data->timecreated = time();
        $data->timemodified = time();

        $data->webinarkey = $response;

        $data->id = $DB->insert_record('gotowebinar', $data);
    }

    if (!empty($data->id)) {
        // Add event to calendar.
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

        $event = \mod_gotowebinar\event\gotowebinar_created::create([
                    'objectid' => $data->id,
                    'context' => context_module::instance($data->coursemodule),
                    'other' => ['modulename' => $data->name, 'startdatetime' => $data->startdatetime],
        ]);
        $event->trigger();

        return $data->id;
    }
    return false;
}

/**
 * GoToWebinar support features.
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
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 * @param mixed $gotowebinar Id of the module instance
 * @return boolean Success/Failure
 */
function gotowebinar_update_instance($gotowebinar) {
    global $DB;

    if (!($oldgotowebinar = $DB->get_record('gotowebinar', ['id' => $gotowebinar->instance]))) {
        return false;
    }
    $result = updateGoToWebinar($oldgotowebinar, $gotowebinar);
    // Variable $oldgotowebinar->meetingtype is always empty, set it up like this or add an invisible option to the mod_form.
    if ($result) {

        $oldgotowebinar->name = $gotowebinar->name;
        $oldgotowebinar->intro = $gotowebinar->intro;
        $oldgotowebinar->startdatetime = $gotowebinar->startdatetime;
        $oldgotowebinar->enddatetime = $gotowebinar->enddatetime;
        $oldgotowebinar->timemodified = time();
        $oldgotowebinar->confirmationemail = $gotowebinar->confirmationemail;
        $oldgotowebinar->reminderemail = $gotowebinar->reminderemail;
        $oldgotowebinar->absenteefollowupemail = $gotowebinar->absenteefollowupemail;
        $oldgotowebinar->attendeefollowupemail = $gotowebinar->attendeefollowupemail;
        $oldgotowebinar->sendcancellationemails = $gotowebinar->sendcancellationemails;

        $DB->update_record('gotowebinar', $oldgotowebinar);
        $param = ['courseid' => $gotowebinar->course, 'instance' => $gotowebinar->instance,
            'groupid' => 0, 'modulename' => 'gotowebinar', ];

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
    $event = \mod_gotowebinar\event\gotowebinar_updated::create([
                'objectid' => $gotowebinar->instance,
                'context' => context_module::instance($gotowebinar->coursemodule),
                'other' => ['modulename' => $gotowebinar->name, 'startdatetime' => $gotowebinar->startdatetime],
    ]);
    $event->trigger();
    return $result;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 * @param int $id An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function gotowebinar_delete_instance($id) {
    global $DB, $CFG;

    if (!$gotowebinar = $DB->get_record('gotowebinar', ['id' => $id])) {

        return false;
    }

    if (!$cm = get_coursemodule_from_instance('gotowebinar', $id)) {

        return false;
    }
    $context = context_module::instance($cm->id);
    if (deleteGoToWebinar($gotowebinar->webinarkey, $gotowebinar->gotowebinar_licence)) {

        return true;
    }

    return false;
}

/**
 * Get activity completion state
 * @param mixed $course
 * @param mixed $cm
 * @param int $userid
 * @param mixed $type
 * @return boolean
 */
function gotowebinar_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/gotowebinar/classes/gotoOAuth.php');

    $completion = new completion_info($course);
    $gotowebinar = $DB->get_record('gotowebinar', ['id' => $cm->instance]);

    if (!$completion->is_enabled($cm) || empty($gotowebinar->completionparticipation)) {

        return false;
    }

    $requiredduration = (($gotowebinar->enddatetime - $gotowebinar->startdatetime) * $gotowebinar->completionparticipation) / 100;

    $gotooauth = new mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
    $organiserkey = $gotooauth->organizerkey;
    $webinarkey = $gotowebinar->webinarkey;
    $response = $gotooauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$webinarkey}/attendees");
    foreach ($response->_embedded->attendeeParticipationResponses as $at) {

        $gotowebinarregistrant = $DB->get_record('gotowebinar_registrant', ['registrantkey' => $at->registrantKey]);

        if ($gotowebinarregistrant && $requiredduration <= $at->attendanceTimeInSeconds) {
            return true;

        }
    }

    return false;
}
