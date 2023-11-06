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
 * GoToWebinar module view file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/gotowebinar/locallib.php');
require_once($CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.php');
require_once($CFG->libdir . '/completionlib.php');
global $DB, $USER;

$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'list', PARAM_TEXT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);
require_admin();

$gotowebinarlicence = $DB->get_record('gotowebinar_licence', ['id' => $id], '*', MUST_EXIST);
$enabled = false;
$disabled = false;
$settingslink = $CFG->wwwroot . '/admin/settings.php?section=modsettinggotowebinar';
if ($action == 'disable' && confirm_sesskey($sesskey)) {

    if ($gotowebinarlicence && $gotowebinarlicence->active) {
        $gotowebinarlicence->active = 0;
        $gotowebinarlicence->timemodified = time();
        if ($DB->update_record('gotowebinar_licence', $gotowebinarlicence)) {
            $disabled = true;
        }
    } else {
        throw new moodle_exception('worongaction', 'gotowebinar', $settingslink);
    }
} else if ($action == 'enable' && confirm_sesskey($sesskey)) {
    if ($gotowebinarlicence && $gotowebinarlicence->active == 0) {
        $gotowebinarlicence->active = 1;
        $gotowebinarlicence->timemodified = time();
        if ($DB->update_record('gotowebinar_licence', $gotowebinarlicence)) {
            $enabled = true;
        }
    } else {
        throw new moodle_exception('worongaction', 'gotowebinar', $settingslink);
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/gotowebinar/license.php', ['id' => $id, 'action' => $action]);
$PAGE->set_title(get_string('license_title', 'mod_gotowebinar'));
$PAGE->set_heading(get_string('license_heading', 'mod_gotowebinar'));
echo $OUTPUT->header();

if ($enabled) {
    notice(get_string('license_enabled', 'mod_gotowebinar', $gotowebinarlicence->email), $settingslink);
} else if ($disabled) {
    notice(get_string('license_disabled', 'mod_gotowebinar', $gotowebinarlicence->email), $settingslink);
}


echo $OUTPUT->footer();
