<?php

require_once("../../config.php");
require_once("lib.php");
require_once("changeuser_form.php");
global $USER;
global $DB;

$changeusername="";
$toform="";

$PAGE->set_url('/mod/data/prepare_create_project.php');


$username = $USER->username;
$userid = $USER->id;
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('dataid', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_TEXT);
$recordtitle=required_param('recordtitle', PARAM_TEXT);

echo "</br> userid is xxx $userid ";
echo "</br> course $courseid data id $dataid  rid $recordid rt  $recordtitle ";

$coursecontext = context_course::instance($courseid);
//$recordtitle = $DB->get_field('data_content', 'content', array('recordid'=>$recordid), $strictness=IGNORE_MISSING);
$recorduserid = $DB->get_field('data_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
$recordusername= $DB->get_field('user', 'username', array('id'=> $recorduserid), $strictness=IGNORE_MISSING);
//$approved= $DB->get_field(data_records, approved, array('id'=> $recordid), $strictness=IGNORE_MISSING);

$projectstudent="";
$projectsupervisor="";


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);


$userroleid = getuserroleid($userid, $coursecontext);
	
if (is_siteadmin() || $userroleid==1){

	//form for change user
	//Instantiate the form
	// ?$userlist = $DB->get_records_sql('SELECT DISTINCT {user}.username FROM {user_enrolments} , {enrol} , {user} where {enrol}.courseid= ? and {user}.id = {user_enrolments}.userid' , array(2));	
    // ?$usernames = array_map(create_function('$o', 'return $o->username;'), $userlist);
	
$mform = new changeuser_form( null, array('recordid'=>$recordid, 'dataid'=>$dataid ,'courseid'=>$courseid,'recordtitle'=>$recordtitle ));//name of the form you defined in file above.
	//default 'action' for form is strip_querystring(qualified_me())

$mform->set_data($toform);
	
	if ($mform->no_submit_button_pressed()) {
		$changeusername=$mform->get_submit_value('otagsadd'); //get value from no submit button through the function
		//you need this section if you have a 'submit' button on your form
		//which performs some kind of subaction on the form and not a full
		//form submission. This \
		//*******
	}	
	$mform->display();
}

// end change user form




//ranil check user roles
// get new user id from the changed username if site admin or manager
if (is_siteadmin() || $userroleid==1 ){
	$newuserid = $DB->get_field('user', 'id', array('username'=>$changeusername), $strictness=IGNORE_MISSING);
	echo 'ciste admin called xxxxxx';
} else {
	$newuserid = $USER->id;
}

if ($roles = get_user_roles($coursecontext, $newuserid)) {
	foreach ($roles as $role) {
		$userRoleId = $role->roleid;
		}
	}

if ($roles = get_user_roles($coursecontext, $recorduserid)) {
	foreach ($roles as $role) {
		$recordUserRoleId = $role->roleid;
	}
}


$newusername= $DB->get_field('user', 'username', array('id'=> $newuserid), $strictness=IGNORE_MISSING);

//set user roles for new userid and recorduserid
if ($recordUserRoleId==5){
	$projectstudent = $recordusername;
}
if ($recordUserRoleId==4 || $recordUserRoleId==3 ||$recordUserRoleId==1){
	$projectsupervisor = $recordusername;
}

if ($userRoleId==5){
	$projectstudent = $newusername;
}
if ($userRoleId==4 || $userRoleId==3 || $userRoleId==1){
	$projectsupervisor = $newusername;
}



//ranil check wether the user has selected


if ($newuserid==""  ){
	echo " cannot proceed! You have not selected a user to change";
} elseif ($recordUserRoleId==$userRoleId) {
	echo " cannot proceed! You have selected same type of users";;
}else{
	
	// User details
	$recoruserdetails = $DB->get_record_sql('SELECT firstname, Lastname FROM {user} WHERE username = ?', array($recordusername));
	$newuserdetails = $DB->get_record_sql('SELECT firstname, Lastname FROM {user} WHERE username = ?', array($newusername));
		 
	echo "<h5> <span style='color:black'> Title for the new project:</span> $recordtitle </H5>  ";
	echo "The idea proposed by: $recordusername  </br> ";
	echo "The idea will selected by: $newusername  </br> ";
	echo " <h5> <span style='color:black' > The project supervisor:</span> $projectsupervisor : $recoruserdetails->firstname  $recoruserdetails->lastname <h5> ";
	echo " <h5> <span style='color:black' > The project student   :</span> $projectstudent : $newuserdetails->firstname  $newuserdetails->lastname</H5> </br>";
	//edit by Ranil
	echo '<form action="http://localhost/moodle/mod/data/create_project.php" method="post">
            			<input type="hidden" name="ideatitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="categoryid" value="'.$coursecategory.'" />
            			<input type="hidden" name="maincourseid" value="'.$courseid.'" />
            			<input type="hidden" name="dataid" value="'.$dataid.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />
            			<input type="hidden" name="recordUserRoleId" value="'.$recordUserRoleId.'" />
            			<input type="hidden" name="userRoleId" value="'.$userRoleId.'" />		
            			<input type="hidden" name="newuserid" value="'.$newuserid.'" />
            			
            			<input type="hidden" name="recorduserid" value="'.$recorduserid.'" />
         
            			<input type="submit" value="Confirm Create project">
            			</form>';
}


echo '<form action="http://localhost/moodle/mod/data/view.php" method="post">
            			<input type="hidden" name="d" value="'.$dataid.'" />
            			<input type="hidden" name="rid" value="'.$recordid.'" />
            			<input type="submit" value="Cancel Create project">
            			</form>';
 

echo $OUTPUT->footer();

//Rnil new function section
//return the role of the user on course context







