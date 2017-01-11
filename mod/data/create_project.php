<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/data/create_project.php', array('id'=>$id));

$ideaTitle = required_param( 'ideaTitle', PARAM_TEXT);
$categoryId = required_param('categoryId', PARAM_TEXT);// course
$recordId = required_param('recordId', PARAM_TEXT);// course


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);


$data = array(
		'shortname' => $ideaTitle,
		'fullname' => $ideaTitle,
		'category' => $categoryId);

$course = create_course((object) $data );

$recordobj = new stdclass;
$recordobj->id = $recordId;
$recordobj->approved = 3;

$DB->update_record(data_records, $recordobj, $bulk=false);


if ($this->has_errors()) {
	throw new moodle_exception('Cannot proceed, errors were detected.');
}

if ($course) {
	echo "http://localhost/moodle/course/view.php?id={$course->id}";
}




echo $OUTPUT->footer();



