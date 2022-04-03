<?php

/**
 * GoToWebinar module upgrade  file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_gotowebinar_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017070901) {
        // Define field hidegrader to be added to gotowebinar_registrant.
        $table = new xmldb_table('gotowebinar_registrant');
        $field = new xmldb_field('attendance_time_in_seconds', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'registrantkey');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_mod_savepoint(true, 2017070901, 'gotowebinar');
    }
    if ($oldversion < 2017070902) {
        // Define field hidegrader to be added to gotowebinar_registrant.
        $table = new xmldb_table('gotowebinar');
        $field = new xmldb_field('confirmationemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'meetingpublic');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reminderemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'confirmationemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('absenteefollowupemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'reminderemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('attendeefollowupemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'absenteefollowupemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('sendcancellationemails', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'attendeefollowupemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_mod_savepoint(true, 2017070902, 'gotowebinar');
    }
      if ($oldversion < 2017070902) {
        // Define field hidegrader to be added to gotowebinar_registrant.
        $table = new xmldb_table('gotowebinar_licence');
        $field = new xmldb_field('confirmationemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'meetingpublic');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reminderemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'confirmationemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('absenteefollowupemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'reminderemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('attendeefollowupemail', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'absenteefollowupemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('sendcancellationemails', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'attendeefollowupemail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_mod_savepoint(true, 2017070902, 'gotowebinar');
    }
    



    return true;
}
