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
class backup_gotowebinar_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $gotowebinar = new backup_nested_element('gotowebinar', array('id'),
                array('course',
            'name',
            'intro',
            'introformat',
            'meetingtype',
            'userid',
            'meetinginfo',
            'gotoid',
            'startdatetime',
            'enddatetime',
            'completionparticipation',
            'meetingpublic',
            'timecreated',
            'timemodified'));

        $gotowebinarregistrants = new backup_nested_element('gotowebinar_registrants', array('id'),
                array('course', 'cmid', 'email', 'status', 'joinurl',
            'confirmationurl', 'registrantkey', 'userid',
            'gotoid', 'timecreated', 'timemodified'));
        $gotowebinar->add_child($gotowebinarregistrants);
        $gotowebinar->set_source_table('gotowebinar', array('id' => backup::VAR_ACTIVITYID));
        $gotowebinarregistrants->set_source_sql('SELECT * FROM {gotowebinar_registrant}  WHERE gotowebinarid = ?',
                array(backup::VAR_PARENTID));
        return $this->prepare_activity_structure($gotowebinar);
    }

}
