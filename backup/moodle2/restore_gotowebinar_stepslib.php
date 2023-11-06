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
class restore_gotowebinar_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure.
     * @return mixed
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = false;

        $paths[] = new restore_path_element('gotowebinar', '/activity/gotowebinar');
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process restore.
     * @param mixed $data
     */
    protected function process_gotowebinar($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        // Insert the gotowebinar record.
        $newitemid = $DB->insert_record('gotowebinar', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process restore.
     * @param mixed $data
     */
    protected function process_gotowebinar_registrant($data) {
        global $DB;

        $data = (object) $data;
        $data->instanceid = $this->get_new_parentid('gotowebinar');
        $data->groupid = $this->get_mappingid('instanceid', $data->instanceid);

        $newitemid = $DB->insert_record('gotowebinar_registrant', $data);

        // No need to save this mapping as far as nothing depend on it.
        // (child paths, file areas nor links decoder).
    }

    /**
     * After execute hook.
     */
    protected function after_execute() {
        // Add survey related files, no need to match by itemname (just internally handled context).
    }

}
