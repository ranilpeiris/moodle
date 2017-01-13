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
$userrole = "";
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('dataid', PARAM_TEXT);// course
$courseid = required_param('courseid', PARAM_TEXT);// course
$recordtitle=required_param('recordtitle', PARAM_TEXT);;

$coursecontext = context_course::instance($courseid);
//$recordtitle = $DB->get_field('data_content', 'content', array('recordid'=>$recordid), $strictness=IGNORE_MISSING);
$recorduserid = $DB->get_field(data_records, userid, array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
$recordusername= $DB->get_field('user', 'username', array('id'=> $recorduserid), $strictness=IGNORE_MISSING);
//$approved= $DB->get_field(data_records, approved, array('id'=> $recordid), $strictness=IGNORE_MISSING);

$projectstudent="";
$projectsupervisor="";


echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);

if (is_siteadmin()){

	//form for change user
	//Instantiate simplehtml_form
	$userlist = $DB->get_records_sql('SELECT DISTINCT {user}.username FROM {user_enrolments} , {enrol} , {user} where {enrol}.courseid= ? and {user}.id = {user_enrolments}.userid' , array(2));	
	$usernames = array_map(create_function('$o', 'return $o->username;'), $userlist);
	
$mform = new changeuser_form( null, array('recordid'=>$recordid, 'dataid'=>$dataid ,'courseid'=>$courseid,'recordtitle'=>$recordtitle ));//name of the form you defined in file above.
	//default 'action' for form is strip_querystring(qualified_me())

$mform->set_data($toform);
	
	if ($mform->no_submit_button_pressed()) {
		$changeusername=$mform->get_submit_value('otagsadd');
		//you need this section if you have a 'submit' button on your form
		//which performs some kind of subaction on the form and not a full
		//form submission.
		//*******
	}
	
}

// end change user form



$mform->display();

//ranil check user roles

if (is_siteadmin()){
	$newuserid = $DB->get_field('user', 'id', array('username'=>$changeusername), $strictness=IGNORE_MISSING);
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



if ($recordUserRoleId==5){
	$projectstudent = $recordusername;
}
if ($recordUserRoleId==3){
	$projectsupervisor = $recordusername;
}

if ($userRoleId==5){
	$projectstudent = $newusername;
}
if ($userRoleId==3){
	$projectsupervisor = $newusername;
}



//ranil check wether the user has selected
if ($changeusername==""  ){
	echo " cannot proceed! You have not selected a user to change";
} else 
{
	echo "Title for the new project: $recordtitle </br> ";
	echo "The idea proposed by: $recordusername  </br> ";
	echo "The idea will selected by: $changeusername  </br> ";
	echo " The project supervisor:$projectsupervisor </br>";
	echo " The project student   :$projectstudent </br>";
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
 

echo "Username id is $username <br />";
echo "Data id is $dataid <br />";
echo "Record id is $recordid <br />";
echo "Record title is $recordtitle <br />";
//echo "Record User is $recorduserid <br />";
//echo "Course User is $courseid <br />";
//echo "Course context is $coursecontext->id <br />";
echo "coursecategory is $coursecategory </br>";
//echo "User Role $userRoleId <br />";
//echo "Record Owner role $recordUserRoleId <br />";
//echo "Is admin $isadmin <br />";
//echo "approved : $approved <br/>";

echo $OUTPUT->footer();





