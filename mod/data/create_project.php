<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/data/create_project.php');

$ideatitle = required_param( 'ideatitle', PARAM_TEXT);
$categoryid = required_param('categoryid', PARAM_TEXT);// course
$recordid = required_param('recordid', PARAM_TEXT);// course


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);


$data = array(
		'shortname' => $ideatitle,
		'fullname' => $ideatitle,
		'category' => $categoryid);

$course = create_course((object) $data );

$recordobj = new stdclass;
$recordobj->id = $recordid;
$recordobj->approved = 1;

$DB->update_record('data_records', $recordobj, $bulk=false);
if ($course) {
echo '<a href="http://localhost/moodle/course/view.php?id='.$course->id.'"> Go to your new project page </a>';
}
//if (has_errors()) {
//	throw new moodle_exception('Cannot proceed, errors were detected.');
//}


	echo "http://localhost/moodle/course/view.php?id={$course->id}";

echo $OUTPUT->footer();



