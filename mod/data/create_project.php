<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/data/create_project.php');

// for course creation
$ideatitle = required_param( 'ideatitle', PARAM_TEXT); // Short name and long name of the new course
$categoryid = required_param('categoryid', PARAM_TEXT); // category of new course
$maincourseid = required_param('maincourseid', PARAM_TEXT);// category of new course

//for udate record as selected
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('dataid', PARAM_TEXT);

//for enrollement
$studentuserid = required_param('studentid', PARAM_TEXT);
$supervisoruserid = required_param('supervisorid', PARAM_TEXT);


//$coursecontext = context_course::instance($maincourseid);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);


//$recorduserid = required_param('recorduserid', PARAM_TEXT);

//$recorduserid = $DB->get_field('data_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
//$approved= $DB->get_field('data_records', 'approved', array('id'=> $recordid), $strictness=IGNORE_MISSING);



echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);



$data = array(
		'shortname' => $ideatitle,
		'fullname' => $ideatitle,
		'category' => $categoryid,
);


$course = create_course((object) $data );

//project creation information
if ($course) {
	echo '<a href="http://localhost/moodle/course/view.php?id='.$course->id.'"> Go to your new project page </a>';
} else {
	echo '<h4>Project didnt created please retry or contact coordinator</h4>';
}
//if (has_errors()) {
	//throw new moodle_exception('Cannot proceed, errors were detected.');
//}

$enrollement1 = enrol_try_internal_enrol($course->id, $supervisoruserid, 3, 0, 0);
$enrollement2 = enrol_try_internal_enrol($course->id, $studentuserid , 5, 0, 0);

//create object to pass project creation function
$recordobj = new stdclass;
$recordobj->id = $recordid;
$recordobj->approved = 1;

$DB->update_record('data_records', $recordobj, $bulk=false);

//if (has_errors()) {
	//throw new moodle_exception('Couldt assign users to the new project, please retry or contact coordinator.');
//}


echo $OUTPUT->footer();



