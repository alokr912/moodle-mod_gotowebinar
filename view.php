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
require_once($CFG->dirroot . '/mod/gotowebinar/lib/OSD.php');
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
$gotomeeting = get_gotowebinar($gotowebinar);
$meeturl = $gotomeeting;

$meetinginfo = json_decode($gotowebinar->meetinfo);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/gotowebinar:view', $context);


$PAGE->set_url('/mod/gotowebinar/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname . ': ' . $gotowebinar->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($gotowebinar);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course') . ':  ' . $course->fullname);

$renderer = $PAGE->get_renderer('mod_gotowebinar');
$obj = new stdClass();
$obj->meetingtitle = $gotowebinar->name;
$obj->meetingdescription = strip_tags($gotowebinar->intro);
$obj->meetingstartenddate = userdate($gotowebinar->startdatetime);
$obj->meetingenddateandtime = userdate($gotowebinar->enddatetime);
$obj->joinmeeting = $meeturl;
echo $renderer->render_from_template('mod_gotowebinar/start_view', $obj);

echo $OUTPUT->footer();
