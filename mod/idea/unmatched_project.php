<?php

require_once("../../config.php");
require_once("lib.php");
//require_once("changeuser_form.php");
global $USER;
global $DB;

$PAGE->set_url('/mod/idea/unmatched_project.php');

$username = $USER->username;
$userid = $USER->id;
$recordid = required_param('recordid', PARAM_TEXT);
$ideaid = required_param('ideaid', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_TEXT);
$recordtitle=required_param('recordtitle', PARAM_TEXT);
$userid=optional_param($parname, '', PARAM_TEXT);

$coursecontext = context_course::instance($courseid);
//$recordtitle = $DB->get_field('idea_content', 'content', array('recordid'=>$recordid), $strictness=IGNORE_MISSING);
$recorduserid = $DB->get_field('idea_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
$recordusername= $DB->get_field('user', 'username', array('id'=> $recorduserid), $strictness=IGNORE_MISSING);
//$approved= $DB->get_field(idea_records, approved, array('id'=> $recordid), $strictness=IGNORE_MISSING);


echo $OUTPUT->header();
//echo $OUTPUT->heading($strideaplural, 2);

$userrole = getuserrole($coursecontext,$userid);
$recorduserrole = getrecorduserrole($coursecontext,$recorduserid);

if ($userrole == 5 ) {
	$studentid = $userid;
}elseif ($userrole == 4 || $userrole == 3 || $userrole == 1){
	$supervisorid = $userid;
}

if ($recorduserrole == 5 ) {
	$studentid = $recorduserid;
} elseif ($recorduserrole == 4 || $recorduserrole == 3 || $recorduserrole == 1){
	$supervisorid = $recorduserid;
}

$supervisordetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($supervisorid));
$studentdetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($studentid));


echo "<h5> <span style='color:black'> Title for the new project:</span> $recordtitle </H5>  ";
echo "The idea proposed by: $recordusername  </br> ";
echo "The idea will selected by: $username  </br> ";
echo " <h5> <span style='color:black' > The project supervisor:</span> $supervisordetails->username : $supervisordetails->firstname  $supervisordetails->lastname <h5> ";
echo " <h5> <span style='color:black' > The project student   :</span> $studentdetails->username : $studentdetails->firstname  $studentdetails->lastname</H5> </br>";
//edit by Ranil
echo '<form action="http://localhost/moodle/mod/idea/delete_project.php" method="post">
            			<input type="hidden" name="ideatitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="maincourseid" value="'.$courseid.'" />
     
            			<input type="hidden" name="ideaid" value="'.$ideaid.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />

						<input type="hidden" name="studentid" value="'.$studentid.'" />
            			<input type="hidden" name="supervisorid" value="'.$supervisorid.'" />
   
            			<input type="submit" value="Confirm Create project">
            			</form>';


echo '<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$ideaid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Cancel Create project">
            			</form>';


echo $OUTPUT->footer();

// check is the new user id is who


function getuserrole($coursecontext, $userid) {
	if ($roles = get_user_roles($coursecontext, $userid)) {
		foreach ($roles as $role) {
			$uroleId = $role->roleid;
		}
		return $uroleId;
	}
}


function getrecorduserrole($coursecontext, $recorduserid)
{
	if ($roles = get_user_roles($coursecontext, $recorduserid)) {
		foreach ($roles as $role) {
			$uroleId = $role->roleid;
		}
		return $uroleId;
	}
}

