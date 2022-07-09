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
 * GoToWebinar module form
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/gotowebinar/locallib.php');

class mod_gotowebinar_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;
        $licences = $this->get_gotowebinar_licence();
        if (!$licences) {
            $link = new moodle_url('/admin/settings.php?section=modsettinggotowebinar');
            throw new moodle_exception('incompletesetup', 'gotowebinar', $link);
        }
        $gotowebinarconfig = get_config('gotowebinar');
        $mform->addElement('header', 'general', get_string('generalsetting', 'gotowebinar'));
        // Adding a text element
        $mform->addElement('text', 'name', get_string('meetingname', 'gotowebinar'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('meetingnamerequired', 'gotowebinar'), 'required', '', 'server');
        $mform->addElement('select', 'gotowebinar_licence', get_string('licence', 'gotowebinar'), $licences);
        if(isset($this->get_current()->update)){
           $mform->disabledIf('gotowebinar_licence',null);
        }else{
            $mform->addRule('gotowebinar_licence', get_string('licencerequired', 'gotowebinar'), 'required', '', 'client');
            
        }
        // $this->standard_intro_elements(get_string('gotowebinarintro', 'gotowebinar'));
        // Adding a new text editor
        // $this->add_intro_editor(true, get_string('gotowebinarintro', 'gotowebinar')); deprecated
        $this->standard_intro_elements();

        $mform->addElement('header', 'meetingheader', get_string('meetingheader', 'gotowebinar'));

          
        $mform->addElement('date_time_selector', 'startdatetime', get_string('startdatetime', 'gotowebinar'));
        $mform->setDefault('startdatetime', time() + 300);
        $mform->addRule('startdatetime', 'Occurs required', 'required', 'client');

        $mform->addElement('date_time_selector', 'enddatetime', get_string('enddatetime', 'gotowebinar'));

        $mform->setDefault('enddatetime', time() + 3900);
        $mform->addRule('enddatetime', 'Occurs required', 'required', 'client');

        $mform->addElement('selectyesno', 'confirmationemail', get_string('confirmationemail', 'gotowebinar'));
        $mform->addElement('selectyesno', 'reminderemail', get_string('reminderemail', 'gotowebinar'));
        $mform->addElement('selectyesno', 'absenteefollowupemail', get_string('absenteefollowupemail', 'gotowebinar'));
        $mform->addElement('selectyesno', 'attendeefollowupemail', get_string('attendeefollowupemail', 'gotowebinar'));
        $mform->addElement('selectyesno', 'sendcancellationemails', get_string('sendcancellationemails', 'gotowebinar'));

        // Adding hidden items
        $mform->addElement('hidden', 'meetingpublic', 1);
        $mform->setType('meetingpublic', PARAM_INT);

        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true, false, null);
    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
    }

    /**
     * Add elements for setting the custom completion rules.
     *  
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    function add_completion_rules() {
        $mform = &$this->_form;

        $group = array();
        $group[] = & $mform->createElement('checkbox', 'completionparticipationenabled', '', get_string('completiongotowebinar', 'gotowebinar'));
        $group[] = & $mform->createElement('text', 'completionparticipation', '', array('size' => 3, 'value' => 50));
        $mform->setType('completionparticipation', PARAM_INT);
        $mform->addGroup($group, 'completiongotowebinargroup', get_string('completiongotowebinargroup', 'gotowebinar'), array(' '), false);
        $mform->addHelpButton('completiongotowebinargroup', 'completiongotowebinargroup', 'gotowebinar');
        $mform->disabledIf('completiongotowebinargroup', 'completionparticipationenabled', 'notchecked');
        return array('completiongotowebinargroup');
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionparticipationenabled']) && $data['completionparticipation'] != 0);
    }

    function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['startdatetime'] < time()) {
            $errors['startdatetime'] = 'Start date time must be a future time';
        }
        if ($data['enddatetime'] < time()) {
            $errors['enddatetime'] = 'End date time must be future time';
        }
        if ($data['startdatetime'] >= $data['enddatetime']) {
            $errors['enddatetime'] = 'End date time should be more that Start date time';
        }

        $course = get_course($data['course']);


        if ($course->format == 'weeks') {

            $dates = course_get_format($course)->get_section_dates($this->current->section);

            if (($data['startdatetime'] < $dates->start) || ($data['startdatetime'] > $dates->end)) {
                $errors['startdatetime'] = "Start date must be in the range of the course week";
            }

            if (($data['enddatetime'] < $dates->start) && ($data['enddatetime'] < $dates->end)) {
                $errors['enddatetime'] = "Start date must be in the range of the course week";
            }
        }
        //

        if (!empty($data['completionunlocked']) && (!empty($data['completionparticipationenabled']))) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data['completion']) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC;
            if ($autocompletion && ($data['completionparticipation'] > 100 || $data['completionparticipation'] <= 0)) {
                $errors['completiongotowebinargroup'] = 'Please enter a valid percentage value between 1 and 100';
            }
        }
        return $errors;
    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionparticipationenabled) || !$autocompletion) {
                $data->completionparticipation = 0;
            }
        }
        return $data;
    }

    private function get_gotowebinar_licence() {
        global $DB;
        $licences = array();
        $gotomeeting_licences = $DB->get_records('gotowebinar_licence', array('active'=>1), 'email');
        foreach ($gotomeeting_licences as $gotomeeting_licences) {

            $licences[$gotomeeting_licences->id] = $gotomeeting_licences->email;
        }
        return $licences;
    }

}
