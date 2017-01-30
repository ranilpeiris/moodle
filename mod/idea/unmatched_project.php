<?php

require_once("../../config.php");
require_once("lib.php");
require_once("../../course/lib.php");
require_once("../../lib/enrollib.php");
require_once("confirmunmatch_form.php");

global $USER;
global $DB;


$PAGE->set_url('/mod/idea/unmatched_project.php');

// for course creation
$recordtitle = required_param( 'recordtitle', PARAM_TEXT); // Short name and long name of the new course
//$categoryid = required_param('categoryid', PARAM_TEXT); // category of new course
$maincourseid = required_param('courseid', PARAM_TEXT);// category of new course

//for udate record as selected
$recordid = required_param('recordid', PARAM_TEXT);
$ideaid = required_param('ideaid', PARAM_TEXT);

$courseidtodelete = $DB->get_field('course', 'id', array('shortname'=> $recordtitle), $strictness=IGNORE_MISSING);

$studentuserlist = $DB->get_records_sql("SELECT {user}.id AS userid, {user}.username, {user}.firstname ,{user}.lastname , {role}.id FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE {role}.id = 5 AND {course}.id = ?" , array($courseidtodelete));
	
$supervisoruserlist = $DB->get_records_sql('SELECT {user}.id AS userid , {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE ({role}.id = 4 OR {role}.id = 3 OR {role}.id = 1 ) AND {course}.id = ?' , array($courseidtodelete));


foreach ($studentuserlist as $student){
	$studentid = $student->userid;
	$studentusername = $student->username;
	$studentfirstname = $student->firstname;
	$studentlastname = $student->lastname;
}
foreach ($supervisoruserlist as $supervisor){
	$supervisorid = $supervisor->userid;
	$supervisorusername = $supervisor->username;
	$supervisorfirstname = $supervisor->firstname;
	$supervisorlastname = $supervisor->lastname;
}


$maincoursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);
$coursedetails = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id = ?', array($maincourseid));

echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);

echo "<h5> <span style='color:black' >The Program Name:</span> $coursedetails->fullname </h5> ";
echo "<h5> <span style='color:black' >The title of the matched project to be unmatched:</span> $recordtitle </h5>";
echo " <h5> <span style='color:black' > The supervisor of the match:</span> $supervisorusername : $supervisorfirstname &nbsp; $supervisorlastname <h5> ";
echo " <h5> <span style='color:black' > The student of the match: $studentusername :</span> $studentfirstname &nbsp; $studentlastname</H5> </br>";
 
$confirmurl =new moodle_url('/mod/idea/delete_project.php', array('maincoursecategory'=>$maincoursecategory , 'courseidtodelete'=>$courseidtodelete, 'recordid'=>$recordid, 'ideaid'=>$ideaid ));
$ideaviewurl = new moodle_url('/mod/idea/view.php', array('d' => $ideaid , 'rid' =>$recordid ));
$confirmbutton= new single_button($confirmurl, 'Confrim', 'post');
$viewideabutton  = new single_button($ideaviewurl, 'Back to ideas', 'post');

echo '<table> <tr><td>';
echo html_writer::tag('div', '' . $OUTPUT->render($confirmbutton),array('class' => 'mdl-align'));
echo '</td><td>';
echo html_writer::tag('div', '' . $OUTPUT->render($viewideabutton),array('class' => 'mdl-align'));
echo '</td><tr></table>';

echo $OUTPUT->footer();

