<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/data/create_project.php');

$ideatitle = required_param( 'ideatitle', PARAM_TEXT);
$categoryid = required_param('categoryid', PARAM_TEXT);// course
$recordid = required_param('recordid', PARAM_TEXT);// course
$maincourseid = required_param('maincourseid', PARAM_TEXT);
$dataid = required_param('dataid', PARAM_TEXT);// course
$recordUserRoleId = required_param('recordUserRoleId', PARAM_TEXT);
$newuserid = required_param('newuserid', PARAM_TEXT);// course
$recorduserid = required_param('recorduserid', PARAM_TEXT);// course

$coursecontext = context_course::instance($maincourseid);
//$recorduserid = $DB->get_field('data_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);
$approved= $DB->get_field('data_records', 'approved', array('id'=> $recordid), $strictness=IGNORE_MISSING);





if ($roles = get_user_roles($coursecontext, $newuserid)) {
	foreach ($roles as $role) {
		$userRoleId = $role->roleid;
	}
	if ($userRoleId = 4 || $userRoleId = 3   ) {
		$userRoleId = 3;
	}
}

if ($roles = get_user_roles($coursecontext, $recorduserid)) {
	foreach ($roles as $role) {
		$recordUserRoleId = $role->roleid;
	}
	if ($userRoleId = 4 || $userRoleId = 3   ) {
		$userRoleId = 3;
	}
}



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

$enrollement1 = enrol_try_internal_enrol($course->id, $recorduserid, $recordUserRoleId, 0, 0);
$enrollement2 = enrol_try_internal_enrol($course->id, $newuserid , $userRoleId, 0, 0);

//create object to pass project creation function
$recordobj = new stdclass;
$recordobj->id = $recordid;
$recordobj->approved = 1;

$DB->update_record('data_records', $recordobj, $bulk=false);

//if (has_errors()) {
	//throw new moodle_exception('Couldt assign users to the new project, please retry or contact coordinator.');
//}


echo $OUTPUT->footer();



