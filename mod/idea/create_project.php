<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/idea/create_project.php');

// for course creation
$ideatitle = required_param( 'ideatitle', PARAM_TEXT); // Short name and long name of the new course
//$categoryid = required_param('categoryid', PARAM_TEXT); // category of new course
$maincourseid = required_param('maincourseid', PARAM_TEXT);// category of new course

//for udate record as selected
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('ideaid', PARAM_TEXT);

//for enrollement
$studentuserid = required_param('studentid', PARAM_TEXT);
$supervisoruserid = required_param('supervisorid', PARAM_TEXT);

$maincoursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);



echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);



$idea = array(
		'shortname' => $ideatitle,
		'fullname' => $ideatitle,
		'category' => $maincoursecategory,
);


$course = create_course((object) $idea );

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
$recordobj->usermatched = 1;

$userrecordstatus = $DB->update_record('idea_records', $recordobj, $bulk=false);

$recordobj1 = new stdclass;
$recordobj1->id = $recordid;
$recordobj1->notavilable = 1;

$userrecordstatus = $DB->update_record('idea_records', $recordobj1, $bulk=false);


$updated =  $DB->execute('UPDATE {idea_records} SET notavilable = 1 WHERE userid = ? AND ideaid =?' , array($studentuserid , $dataid));


//if (has_errors()) {
//throw new moodle_exception('Couldt assign users to the new project, please retry or contact coordinator.');
//}
echo '<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$dataid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Back to ideas">
            			</form>';

echo $OUTPUT->footer();

