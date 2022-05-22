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
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../config.php');
require_once('./classes/gotooauth.class.php');
$code = required_param('code', PARAM_RAW);
require_login();
if(!is_siteadmin()){
     print_error('Acess denied'); 
}
$goToOAuth= new mod_gotowebinar\GoToOAuth();
global $CFG;
$result = $goToOAuth->getAccessTokenWithCode($code);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading('GoTo config test report');
$PAGE->set_title('GoTo config test report');
echo $OUTPUT->header();
if($result){
     echo html_writer::div('GoToWebinar setup status ', 'alert alert-info');
}else{
     echo html_writer::div('GoToWebinar setup status ', 'alert alert-error');
}
echo $OUTPUT->footer();

