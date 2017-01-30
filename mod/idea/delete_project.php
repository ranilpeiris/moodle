<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/idea/delete_project.php');


$courseidtodelete = required_param('courseidtodelete', PARAM_TEXT);
$maincoursecategory = required_param('maincoursecategory', PARAM_TEXT);
$recordid = required_param('recordid', PARAM_TEXT);
$ideaid = required_param('ideaid', PARAM_TEXT);

$studentuserlist = $DB->get_records_sql("SELECT {user}.id AS userid, {user}.username, {user}.firstname ,{user}.lastname , {role}.id FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE {role}.id = 5 AND {course}.id = ?" , array($courseidtodelete));
	
$supervisoruserlist = $DB->get_records_sql('SELECT {user}.id As userid, {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE ({role}.id = 4 OR {role}.id = 3 OR {role}.id = 1 ) AND {course}.id = ?' , array($courseidtodelete));

foreach ($studentuserlist as $student){
	$studentid = $student->userid;
	$updated =  $DB->execute('UPDATE {idea_records} SET notavilable = 0 WHERE userid = ? AND ideaid =?' , array($studentid , $ideaid));
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

if ($userrecordstatus) {
	echo '<h5> Suoervisor unmatched </h5>';
} else {
	echo '<h5>Superviosr unmatched not successful</h5>';
}


foreach ($supervisoruserlist as $supervisor){
	$supervisorid = $supervisor->userid;
}

echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);
	
	$coursedeleted = delete_course($courseidtodelete);
	
	//project deleation information
	if ($coursedeleted) {
		echo '<h5> The created project deleted  </h5>';
	} else {
		echo '<h5>The created project deleted, please retry</h5>';
	}
	
	
	
	//if (has_errors()) {
	//throw new moodle_exception('Couldt assign users to the new project, please retry or contact coordinator.');
	//}

	//project deleation information
	if ($userrecordstatus) {
		echo '<h5> Course unmatched Completed ! Idea has released </h5>';
	} else {
		echo '<h5>Idea unmatched not fully successful, please retry</h5>';
	}

	$ideaviewurl = new moodle_url('/mod/idea/view.php', array('d' => $ideaid , 'rid' =>$recordid ));
	$viewideabutton  = new single_button($ideaviewurl, 'Back to ideas', 'post');
	echo html_writer::tag('div', '' . $OUTPUT->render($viewideabutton),array('class' => 'mdl-align'));
	
	/**
echo '<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$ideaid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Back to ideas">
            			</form>';
            			*/



echo $OUTPUT->footer();

