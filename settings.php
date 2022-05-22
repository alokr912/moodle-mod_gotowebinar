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
    require_once($CFG->dirroot.'/mod/gotowebinar/classes/gotoOAuth.php');

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

    //---------------------Userid(License userid) General settings -----------------------------------------------------------------------------------
    $name = 'gotowebinar/userid';
    $visiblename = get_string('gtw_userid', 'gotowebinar');
    $description = get_string('gtw_userid_desc', 'gotowebinar');
    //$settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    //---------------------Password settings -----------------------------------------------------------------------------------
    $name = 'gotowebinar/password';
    $visiblename = get_string('gtw_password', 'gotowebinar');
    $description = get_string('gtw_password_desc', 'gotowebinar');
    //$settings->add(new admin_setting_configpasswordunmask($name, $visiblename, $description, '', PARAM_RAW, 50));
    
     $licences  = $DB->get_records('gotowebinar_licence');
    foreach($licences as $licence){
        $settings->add(new admin_setting_description($licence->id,$licence->email,'Active'));
        
    }
    $goto = new \mod_gotowebinar\GoToOAuth();
    
    $status =$goto->getSetupStatus();
    if($status){
       $status_html = html_writer::div('GoToWebinar setup status is complete', 'alert alert-success');
        $settings->add(new admin_setting_heading('gotowebinar_setup_status', '', $status_html));  
    }
       $url = $CFG->wwwroot . '/mod/gotowebinar/setup.php';
    $url = htmlentities($url, ENT_COMPAT, 'UTF-8');
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<center><input type="button" onclick="window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value="' .
        get_string('setup', 'gotowebinar') . '" /></center>';
    $settings->add(new admin_setting_heading('gotowebinar_setup', '', $str));  
    
   
}

