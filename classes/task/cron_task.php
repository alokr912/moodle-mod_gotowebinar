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
require_once($CFG->dirroot . '/mod/gotowebinar/classes/gotoOAuth.php');

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
        $currenttime = time();
        $lastsync = get_config('gotowebinar', self::LAST_SYNC_TIME);
        if ($lastsync) {
            $lastsync = 0;
        }

        $sql = "SELECT * FROM {gotowebinar} WHERE enddatetime >= :enddatetime1  AND enddatetime <= :enddatetime2 ";
        $gotowebinars = $DB->get_records_sql($sql, array('enddatetime1' => $lastsync, 'enddatetime2' => $currenttime));

        foreach ($gotowebinars as $gotowebinar) {
            $course = get_course($gotowebinar->course);
            $completion = new \completion_info($course);

            $cm = get_coursemodule_from_instance('gotowebinar', $gotowebinar->id);

            if (!$completion->is_enabled($cm) || empty($gotowebinar->completionparticipation)) {

                continue;
            }

            $requiredduration = (($gotowebinar->enddatetime - $gotowebinar->startdatetime) *
                    $gotowebinar->completionparticipation) / 100;

            $gototauth = new \mod_gotowebinar\GoToOAuth($gotowebinar->gotowebinar_licence);
            $organiserkey = $gototauth->organizerkey;
            $webinarkey = $gotowebinar->webinarkey;
            $response = $gototauth->get("/G2W/rest/v2/organizers/{$organiserkey}/webinars/{$webinarkey}/attendees");
            foreach ($response->_embedded->attendeeParticipationResponses as $at) {

                $gotowebinarregistrant = $DB->get_record('gotowebinar_registrant', array('registrantkey' => $at->registrantKey));

                if ($gotowebinarregistrant && $requiredduration <= $at->attendanceTimeInSeconds) {

                    $completion->update_state($cm, COMPLETION_COMPLETE, $gotowebinarregistrant->userid);
                }
            }
        }
        set_config(self::LAST_SYNC_TIME, $currenttime, 'gotowebinar');
    }

    public function get_name(): string {
        return get_string('crontask', 'mod_gotowebinar');
    }

}
