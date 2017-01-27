<?php

require_once("../../config.php");
require_once("lib.php");
require_once("changeuser_form.php");
global $USER;
global $DB;

$PAGE->set_url('/mod/idea/manage_create_project.php');

$recordid = required_param('recordid', PARAM_TEXT);
$ideaid = required_param('ideaid', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_TEXT);
$recordtitle=required_param('recordtitle', PARAM_TEXT);


$coursecontext = context_course::instance($courseid);
$recorduserid = $DB->get_field('idea_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
$recordusername= $DB->get_field('user', 'username', array('id'=> $recorduserid), $strictness=IGNORE_MISSING);


echo $OUTPUT->header();
//echo $OUTPUT->heading($strideaplural, 2);

$recorduserrolename = getcustomrolenamefromid($coursecontext,$recorduserid);
$convertuserrole = convertuserrole($coursecontext, $recorduserid);

echo "<h5> <span style='color:black'> Title for the new project: </span> $recordtitle </h5>";
echo "<h5> <span style='color:black'> The idea proposed by: </span>  $recordusername <span style='color:black'> and this user will be assign as the :</span> $recorduserrolename  </h5>  ";

$newusername ="";

/**$userlist = $DB->get_records_sql('SELECT {user}.username, {user}.firstname, {user}.lastname FROM {course}
 JOIN {context} ON {course}.id = {context}.instanceid
 JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
 JOIN {user} ON {user}.id = {role_assignments}.userid
 JOIN {role} ON {role}.id = {role_assignments}.roleid
 where {course}.id = ?' , array($courseid));
*/

// create and call to a function that accept array of recors and combine columns
//$usernames = array_map(create_function('$o','return $o->username;' ), $userlist);

$mform = new changeuser_form( null, array( 'convertuserrole'=>$convertuserrole,'recordusername'=>$recordusername, 'recoruserdid'=>$recorduserid, 'recordid'=>$recordid, 'ideaid'=>$ideaid ,'courseid'=>$courseid,'recordtitle'=>$recordtitle ));//name of the form you defined in file above.

//$mform->set_idea($toform);


if ($mform->no_submit_button_pressed()) {
	$newusername=$mform->get_submit_value('otagsadd'); //get value from no submit button through the function
}

$mform->display();

echo "<h5> <span style='color:black'> The idea will be assign to : </span> $newusername </h5>  ";


if ($newusername==""){
	echo "<h4> Cannot proceed!: Select an user to assign ; The selected user will be assign as: $convertuserrole </h4>";
} else {

	$newuserid = $DB->get_field('user', 'id', array('username'=> $newusername), $strictness=IGNORE_MISSING);
	
	
	$newuserrole = getuserroleinmanager($coursecontext, $newuserid);
	
	$recorduserrole = getuserroleinmanager($coursecontext, $recorduserid);

	if ($newuserrole == 5 ) {
		$studentid = $newuserid;
	}elseif ($newuserrole == 4 || $newuserrole == 3 || $newuserrole == 1){
		$supervisorid = $newuserid;
	}

	if ($recorduserrole == 5 ) {
		$studentid = $recorduserid;
	} elseif ($recorduserrole == 4 || $recorduserrole == 3 || $recorduserrole == 1){
		$supervisorid = $recorduserid;
	}

	
	
	$supervisordetails = $DB->get_record_sql('SELECT id, username, firstname, lastname FROM {user} WHERE id = ?', array($supervisorid));
	$studentdetails = $DB->get_record_sql('SELECT id, username, firstname, lastname FROM {user} WHERE id = ?', array($studentid));

	echo " <h5> <span style='color:black' > The project title   :</span> $recordtitle </H5> </br>";
	echo " <h5> <span style='color:black' > The project supervisor:</span> $supervisordetails->username : $supervisordetails->firstname  $supervisordetails->lastname <h5> ";
	echo " <h5> <span style='color:black' > The project student   :</span> $studentdetails->username : $studentdetails->firstname  $studentdetails->lastname</H5> </br>";
	//edit by Ranil
	echo '<form action="http://localhost/moodle/mod/idea/create_project.php" method="post">
            			<input type="hidden" name="ideatitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="maincourseid" value="'.$courseid.'" />
  
            			<input type="hidden" name="ideaid" value="'.$ideaid.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />

            			<input type="hidden" name="studentid" value="'. $studentdetails->id .'" />
            			<input type="hidden" name="supervisorid" value="'. $supervisordetails->id .'" />

            			<input type="submit" value="Confirm Create project">
            			</form>';
	
	echo '<form action="http://localhost/moodle/mod/idea/delete_project.php" method="post">
            			<input type="hidden" name="ideatitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="maincourseid" value="'.$courseid.'" />
	
            			<input type="hidden" name="ideaid" value="'.$ideaid.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />
	
            			<input type="hidden" name="studentid" value="'. $studentdetails->id .'" />
            			<input type="hidden" name="supervisorid" value="'. $supervisordetails->id .'" />
	
            			<input type="submit" value="Confirm Delete project">
            			</form>';
	
	
}

echo '<form action="http://localhost/moodle/mod/idea/view.php" method="post">
            			<input type="hidden" name="d" value="'.$ideaid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Cancel Create project">
            			</form>';


echo $OUTPUT->footer();


// get user role id
function getuserroleinmanager($coursecontext, $userid) {
	if ($roles = get_user_roles($coursecontext, $userid)) {
		foreach ($roles as $role) {
			$uroleId = $role->roleid;
		}
		return $uroleId;
	}
}


// return the opposite user type to the receved user id
function convertuserrole($coursecontext, $recorduserid)
{
	if ($roles = get_user_roles($coursecontext, $recorduserid)) {
		foreach ($roles as $role) {
			$uroleId = $role->roleid;
		}
		if($uroleId==4 || $uroleId==3 || $uroleId==1 ){
			return "Student";
		}elseif ($uroleId==5){
			return "Supervisor";
		}
	}
}

// return the role name of the user id

function getcustomrolenamefromid($coursecontext,$anyuserid) {
	if ($roles = get_user_roles($coursecontext, $anyuserid)) {
		foreach ($roles as $role) {
			$anyuserroleid = $role->roleid;
		}
		if ($anyuserroleid==4 || $anyuserroleid==3 || $anyuserroleid==1 ) {
			return "Supervisor";
		}elseif ($anyuserroleid==5){
			return "Student";
		}
	}
}

