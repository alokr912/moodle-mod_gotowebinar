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

namespace mod_gotowebinar\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';

/**
 * The main scheduled task for the forum.
 *
 * @package    mod_forum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    const LAST_SYNC_TIME = "GOTOWEBINAR_LAST_SYNC_TIME";

    public function execute() {
        global $DB;
        $current_time = time();
        $last_sync = get_config('gotowebinar', self::LAST_SYNC_TIME);
        if ($last_sync) {
            $last_sync = 0;
        }

        $sql = "SELECT * FROM {gotowebinar} WHERE enddatetime >= :enddatetime1  AND enddatetime <= :enddatetime2 ";
        $gotowebinars = $DB->get_records_sql($sql, array('enddatetime1' => $last_sync, 'enddatetime2' => $current_time));

        foreach ($gotowebinars as $gotowebinar) {
            $course = get_course($gotowebinar->course);
            $completion = new \completion_info($course);

            $cm = get_coursemodule_from_instance('gotowebinar', $gotowebinar->id);

            if (!$completion->is_enabled($cm) || empty($gotowebinar->completionparticipation)) {

                continue;
            }

            $required_duration = (($gotowebinar->enddatetime - $gotowebinar->startdatetime) * $gotowebinar->completionparticipation) / 100;

            $goToOauth = new \mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
            $organiser_key = $goToOauth->organizerkey;
            $webinarkey = $gotowebinar->webinarkey;
            $response = $goToOauth->get("/G2W/rest/v2/organizers/{$organiser_key}/webinars/{$webinarkey}/attendees");
            foreach ($response->_embedded->attendeeParticipationResponses as $at) {

                $gotowebinar_registrant = $DB->get_record('gotowebinar_registrant', array('registrantkey' => $at->registrantKey));

                if ($gotowebinar_registrant && $required_duration <= $at->attendanceTimeInSeconds) {

                    $completion->update_state($cm, COMPLETION_COMPLETE, $gotowebinar_registrant->userid);
                }
            }
        }
        set_config(self::LAST_SYNC_TIME, $current_time, 'gotowebinar');
    }

    public function get_name(): string {
        return get_string('crontask', 'mod_gotowebinar');
    }

}
