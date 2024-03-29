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
 * Global Settings file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {


    $name = 'gotowebinar/consumer_key';
    $visiblename = get_string('gtw_consumer_key', 'gotowebinar');
    $description = get_string('gtw_consumer_key_desc', 'gotowebinar');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    $name = 'gotowebinar/consumer_secret';
    $visiblename = get_string('gtw_consumer_secret', 'gotowebinar');
    $description = get_string('gtw_consumer_secret_desc', 'gotowebinar');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    $licences = $DB->get_records('gotowebinar_licence');
    $actionshtml = html_writer::start_div('container');
    foreach ($licences as $licence) {
        if ($licence->active) {

            $class = "btn-outline-danger";
            $url = new moodle_url('/mod/gotowebinar/license.php',
                    ['id' => $licence->id, 'action' => 'disable', 'sesskey' => sesskey()]);

            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Disable ' . $licence->email, ['class' => 'btn btn-outline-danger']);
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        } else {
            $class = "btn-secondary";
            $url = new moodle_url('/mod/gotowebinar/license.php',
                    ['id' => $licence->id, 'action' => 'enable', 'sesskey' => sesskey()]);
            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Enable ' . $licence->email, ['class' => 'btn btn-secondary']);
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        }
    }

    $class = "btn-primary";
    $url = new moodle_url('/mod/gotowebinar/setup.php', ['sesskey' => sesskey()]);
    $actionshtml .= html_writer::start_div('row mt-5 mb-5');
    $actionshtml .= html_writer::start_div('col-md-12');
    $actionshtml .= html_writer::link($url, get_string('setup', 'mod_gotowebinar'), ['class' => 'btn btn-secondary']);
    $actionshtml .= html_writer::end_div();
    $actionshtml .= html_writer::end_div();
    $actionshtml .= html_writer::end_div();
    $settings->add(new admin_setting_heading('gotowebinar_license', '', $actionshtml));
}

