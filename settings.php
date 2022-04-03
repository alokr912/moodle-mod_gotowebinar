<?php

/**
 * Global Settings file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php');

    //----------------  Consumer Key Settings -----------------------------------------//
    $name = 'gotowebinar/consumer_key';
    $visiblename = get_string('gtw_consumer_key', 'gotowebinar');
    $description = get_string('gtw_consumer_key_desc', 'gotowebinar');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    //-----------------Consumer secret settings ----------------------------------------
    $name = 'gotowebinar/consumer_secret';
    $visiblename = get_string('gtw_consumer_secret', 'gotowebinar');
    $description = get_string('gtw_consumer_secret_desc', 'gotowebinar');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    $licences = $DB->get_records('gotowebinar_licence');
    $actionshtml = html_writer::start_div('container');
    foreach ($licences as $licence) {
        if ($licence->active) {

            $class = "btn-outline-danger";
            $url = new moodle_url('/mod/gotowebinar/license.php', array('id' => $licence->id, 'action' => 'disable', 'sesskey' => sesskey()));

            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Disable ' . $licence->email, array('class' => 'btn btn-outline-danger'));
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        } else {
            $class = "btn-secondary";
            $url = new moodle_url('/mod/gotowebinar/license.php', array('id' => $licence->id, 'action' => 'enable', 'sesskey' => sesskey()));
            $actionshtml .= html_writer::start_div('row');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url, 'Enable ' . $licence->email, array('class' => 'btn btn-secondary'));
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
        }
    }
    
     $class = "btn-primary";
            $url = new moodle_url('/mod/gotowebinar/setup.php', array('sesskey' => sesskey()));
            $actionshtml .= html_writer::start_div('row mt-5 mb-5');
            $actionshtml .= html_writer::start_div('col-md-12');
            $actionshtml .= html_writer::link($url,  get_string('setup','mod_gotowebinar'), array('class' => 'btn btn-secondary'));
            $actionshtml .= html_writer::end_div();
            $actionshtml .= html_writer::end_div();
    $actionshtml .= html_writer::end_div();
    $settings->add(new admin_setting_heading('gotowebinar_license', '', $actionshtml));

    $goto = new mod_gotowebinar\GoToOAuth();

    $status = $goto->getSetupStatus();
    
    if ($status) {
        $status_html = html_writer::div('GoToWebinar setup status is complete', 'alert alert-success');
        $a = '';
        $settings->add(new admin_setting_heading('gotowebinar_setup_status', '', $a));
    }
    $url = $CFG->wwwroot . '/mod/gotowebinar/setup.php';
    $url = htmlentities($url, ENT_COMPAT, 'UTF-8');
    $options = 'toolbar = 0, scrollbars = 1, location = 0, statusbar = 0, menubar = 0, resizable = 0, width = 700, height = 300';
    $str = '<center><input type = "button" onclick = "window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value = "' .
            get_string('setup', 'gotowebinar') . '" /></center>';
   // $settings->add(new admin_setting_heading('gotowebinar_setup', '', $str));
}

