<?php
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

        $gotowebinar_registrants = new backup_nested_element('gotowebinar_registrants', array('id'), array('course', 'cmid', 'email', 'status', 'joinurl',
                                                               'confirmationurl', 'registrantkey', 'userid',
                                                              'gotoid', 'timecreated', 'timemodified'));
        $gotowebinar->add_child($gotowebinar_registrants);
        $gotowebinar->set_source_table('gotowebinar', array('id' => backup::VAR_ACTIVITYID));
        $gotowebinar_registrants->set_source_sql('SELECT * FROM {gotowebinar_registrant}  WHERE gotowebinarid = ?', array(backup::VAR_PARENTID));
         return $this->prepare_activity_structure($gotowebinar);
    }

}
