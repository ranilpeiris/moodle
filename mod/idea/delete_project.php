<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/idea/delete_project.php');

// for course creation
$ideatitle = required_param( 'recordtitle', PARAM_TEXT); // Short name and long name of the new course
//$categoryid = required_param('categoryid', PARAM_TEXT); // category of new course
$maincourseid = required_param('courseid', PARAM_TEXT);// category of new course

//for udate record as selected
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('ideaid', PARAM_TEXT);

//for enrollement

$courseidtodelete = $DB->get_field('course', 'id', array('shortname'=> $ideatitle), $strictness=IGNORE_MISSING);


$studentuserlist = $DB->get_records_sql("SELECT {user}.id, {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE {role}.id = 5 AND {course}.id = ?" , array($courseidtodelete));
	
$supervisoruserlist = $DB->get_records_sql('SELECT {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE ({role}.id = 4 OR {role}.id = 3 OR {role}.id = 1 ) AND {course}.id = ?' , array($courseidtodelete));


$maincoursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);


///////$studentuserid = required_param('studentid', PARAM_TEXT);
///////$supervisoruserid = required_param('supervisorid', PARAM_TEXT);


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);



$idea = array(
		'shortname' => $ideatitle,
		'fullname' => $ideatitle,
		'category' => $maincoursecategory,
);


$coursedeleted = delete_course($courseidtodelete);


//project deleation information
if ($coursedeleted) {
	echo '<h5> Course unmatched! the related project also deleted </h5>';
} else {
	echo '<h5>Idea could not unmatched, please retry</h5>';
}
//if (has_errors()) {
//throw new moodle_exception('Cannot proceed, errors were detected.');
//}


foreach($studentuserlist as $author){ //for each studnts in the list

	$authorroleid = $author->id;
	//set all avilable to 0 for the  student record
	$updated =  $DB->execute('UPDATE {idea_records} SET notavilable = 0 WHERE userid = ? AND ideaid =?' , array($authorroleid , $dataid));
			
}

//create object to pass project creation function
$recordobj = new stdclass;
$recordobj->id = $recordid;
$recordobj->usermatched = 0;

$userrecordstatus = $DB->update_record('idea_records', $recordobj, $bulk=false);

$recordobj1 = new stdclass;
$recordobj1->id = $recordid;
$recordobj1->notavilable = 0;

$userrecordstatus = $DB->update_record('idea_records', $recordobj1, $bulk=false);


//if (has_errors()) {
//throw new moodle_exception('Couldt assign users to the new project, please retry or contact coordinator.');
//}
echo '<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$dataid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Back to ideas">
            			</form>';



echo $OUTPUT->footer();

