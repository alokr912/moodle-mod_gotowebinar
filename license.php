<?php

/**
 * GoToWebinar module view file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/gotowebinar/locallib.php');
require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
require_once($CFG->libdir . '/completionlib.php');
global $DB, $USER;
$id = required_param('id', PARAM_INT); // Course Module ID
$action = optional_param('action', 'list', PARAM_TEXT);
$sesskey = optional_param('sesskey', '', PARAM_RAW);

if (!is_siteadmin()) {
    print_error('nopermissions', 'gotowebinar');
}
$gotowebinar_licence = $DB->get_record('gotowebinar_licence', array('id' => $id), '*', MUST_EXIST);
$enabled = false;
$disabled = false;
if ($action == 'disable' && confirm_sesskey($sesskey)) {

    if ($gotowebinar_licence && $gotowebinar_licence->active) {
        $gotowebinar_licence->active = 0;
        $gotowebinar_licence->timemodified = time();
        if ($DB->update_record('gotowebinar_licence', $gotowebinar_licence)) {
            $disabled = true;
        }
    } else {
        print_error('nopermissions', 'gotowebinar');
    }
} else if ($action == 'enable' && confirm_sesskey($sesskey)) {
    if ($gotowebinar_licence && $gotowebinar_licence->active == 0) {
        $gotowebinar_licence->active = 1;
        $gotowebinar_licence->timemodified = time();
        if ($DB->update_record('gotowebinar_licence', $gotowebinar_licence)) {
            $enabled = true;
        }
    } else {
        print_error('worongaction', 'gotowebinar');
    }
}






$PAGE->set_url('/mod/gotowebinar/license.php', array('id' => $id, 'action' => $action));
$PAGE->set_title(get_string('license_title', 'mod_gotowebinar'));
$PAGE->set_heading(get_string('license_heading', 'mod_gotowebinar'));
echo $OUTPUT->header();
$link = $CFG->wwwroot . '/admin/settings.php?section=modsettinggotowebinar';
if ($enabled) {
    notice(get_string('license_enabled', 'mod_gotowebinar', $gotowebinar_licence->email), $link);
} else if ($disabled) {
    notice(get_string('license_disabled', 'mod_gotowebinar', $gotowebinar_licence->email), $link);
}


echo $OUTPUT->footer();
