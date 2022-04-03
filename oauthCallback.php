<?php

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
$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/gotowebinar/oauthCallback.php',array('code'=>$code)));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading('GoTo config test report');
$PAGE->set_title('GoTo config test report');
echo $OUTPUT->header();
if($result){
     echo html_writer::div('GoToWebinar setup status ', 'alert alert-info');
      notice('b','');
}else{
     echo html_writer::div('GoToWebinar setup status ', 'alert alert-error');
     notice('a');
     
}

echo $OUTPUT->footer();

