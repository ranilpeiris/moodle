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

//for enrollement
echo "xxx recordtitle $recordtitle";
$courseidtodelete = $DB->get_field('course', 'id', array('shortname'=> $recordtitle), $strictness=IGNORE_MISSING);

$studentuserlist = $DB->get_records_sql("SELECT {user}.id, {user}.username, {user}.firstname ,{user}.lastname , {role}.id FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE {role}.id = 5 AND {course}.id = ?" , array($courseidtodelete));
	
$supervisoruserlist = $DB->get_records_sql('SELECT {user}.id, {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE ({role}.id = 4 OR {role}.id = 3 OR {role}.id = 1 ) AND {course}.id = ?' , array($courseidtodelete));

foreach ($studentuserlist as $student){
	$studentid = $student->id;
}
foreach ($supervisoruserlist as $supervisor){
	$supervisorid = $supervisor->id;
}

$maincoursecategory= $DB->get_field('course', 'category', array('id'=> $maincourseid), $strictness=IGNORE_MISSING);
$supervisordetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($supervisorid));
$studentdetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($studentid));
$coursedetails = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id = ?', array($maincourseid));


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);

echo "<h5> The Program Name: $coursedetails->fullname </h5> ";
echo "<h5> The title of the matched project to be unmatched: $recordtitle ";
echo " <h5> <span style='color:black' > The supervisor of the match::</span> $supervisordetails->username : $supervisordetails->firstname  $supervisordetails->lastname <h5> ";
echo " <h5> <span style='color:black' > The student of the match  :</span> $studentdetails->username : $studentdetails->firstname  $studentdetails->lastname</H5> </br>";
 

echo '<table> <tr><td>
		<form action="http://localhost/moodle/mod/idea/delete_project.php" method="post">
            			<input type="hidden" name="recordtitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="courseid" value="'.$maincourseid.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />
            			<input type="hidden" name="ideaid" value="'.$ideaid.'" />
            			<input type="submit" value="Confirm Unmatch">
            			</form>
		</td><td>

		<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$ideaid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value=" &nbsp;&nbsp;Back to ideas &nbsp;&nbsp;">
            			</form>
		</td><tr></table>';

echo $OUTPUT->footer();

