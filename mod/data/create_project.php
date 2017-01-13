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

$coursecontext = context_course::instance($maincourseid);
$recorduserid = $DB->get_field('data_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);
$approved= $DB->get_field('data_records', 'approved', array('id'=> $recordid), $strictness=IGNORE_MISSING);





if ($roles = get_user_roles($coursecontext, $USER->id)) {
	foreach ($roles as $role) {
		$userRoleId = $role->roleid;
	}
}

if ($roles = get_user_roles($coursecontext, $recorduserid)) {
	foreach ($roles as $role) {
		$recordUserRoleId = $role->roleid;
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

$enrollement1 = enrol_try_internal_enrol($course->id, $recorduserid, $recordUserRoleId, 0, 0);
$enrollement2 = enrol_try_internal_enrol($course->id, $USER->id, $userRoleId, 0, 0);


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

if (is_siteadmin()){

	echo '<form><tr><td>hello</td></tr> </form>';

}



	echo "http://localhost/moodle/course/view.php?id={$course->id}";

echo $OUTPUT->footer();



