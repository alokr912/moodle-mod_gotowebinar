<?php


/**
 * GoToWebinar module view file
 *
 * @package mod_gotowebinar
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/gotowebinar/locallib.php');
require_once $CFG->dirroot . '/mod/gotowebinar/classes/gotooauth.class.php';
require_once($CFG->libdir . '/completionlib.php');
global $DB, $USER;
$id = required_param('id', PARAM_INT); // Course Module ID

if ($id) {
    if (!$cm = get_coursemodule_from_id('gotowebinar', $id)) {
        print_error('invalidcoursemodule');
    }
    $gotowebinar = $DB->get_record('gotowebinar', array('id' => $cm->instance), '*', MUST_EXIST);
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$meeturl = '';
$gototrainingdownloads = array();
$meeturl = get_gotowebinar($gotowebinar);

$audio_info = get_gotowebinar_audio_info($gotowebinar->webinarkey);

$meetinginfo = json_decode($gotowebinar->meetinfo);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/gotowebinar:view', $context);

$access_code = '';
if(has_capability('mod/gotowebinar:organiser', $context)){
    $access_code = $audio_info['organizer_accesscode'];
}else if( has_capability('mod/gotowebinar:presenter', $context)){
  $access_code = $audio_info['panelist_accesscode'];  
}else {
    
  $access_code = $audio_info['attendee_accesscode'];     
}

$PAGE->set_url('/mod/gotowebinar/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname . ': ' . $gotowebinar->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($gotowebinar);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course') . ':  ' . $course->fullname);
$table = new html_table();
$table->head = array(get_string('pluginname', 'mod_gotowebinar'));
$table->headspan = array(2);
$table->size = array('30%', '70%');

$cell1 = new html_table_cell(get_string('meetingtitle', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . $gotowebinar->name . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';
$table->data[] = array($cell1, $cell2);

$cell1 = new html_table_cell(get_string('meetingdescription', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . strip_tags($gotowebinar->intro) . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);


$cell1 = new html_table_cell(get_string('meetingstartenddate', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . userdate($gotowebinar->startdatetime) . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);


$cell1 = new html_table_cell(get_string('meetingenddateandtime', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . userdate($gotowebinar->enddatetime) . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);


$cell1 = new html_table_cell(get_string('webinarkey', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . $gotowebinar->webinarkey . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);


$cell1 = new html_table_cell(get_string('toll', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . $audio_info['toll'] . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);

$cell1 = new html_table_cell(get_string('accesscode', 'mod_gotowebinar'));
$cell1->colspan = 1;
$cell1->style = 'text-align:left;';

$cell2 = new html_table_cell("<b>" . $access_code . "</b>");
$cell2->colspan = 1;
$cell2->style = 'text-align:left;';

$table->data[] = array($cell1, $cell2);

$cell2 = new html_table_cell(html_writer::link(trim($meeturl, '"'), get_string('joinmeeting', 'mod_gotowebinar'),
    array("target" => "_blank", 'class' => 'btn btn-primary')));
$cell2->colspan = 2;
$cell2->style = 'text-align:center;';

$table->data[] = array($cell2);

foreach ($gototrainingdownloads as $gototrainingdownload) {
    $cell1 = new html_table_cell(get_string('meetingrecording', 'mod_gotowebinar'));
    $cell1->colspan = 1;
    $cell1->style = 'text-align:left;';
    $downloadlink = html_writer::link($gototrainingdownload->downloadUrl, get_string('downloadurl', 'mod_gotowebinar') . ' ');
    $cell2 = new html_table_cell("<b>$downloadlink</b>");
    $cell2->colspan = 1;
    $cell2->style = 'text-align:left;';
    $table->data[] = array($cell1, $cell2);
}


echo html_writer::table($table);


echo $OUTPUT->footer();
