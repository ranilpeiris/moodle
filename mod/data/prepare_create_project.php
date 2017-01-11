<?php

require_once("../../config.php");
require_once("lib.php");
global $USER;
global $DB;


$PAGE->set_url('/mod/data/prepare_create_project.php');


$username = $USER->username;
$userrole = "";
$recordid = required_param('recordid', PARAM_TEXT);
$dataid = required_param('dataid', PARAM_TEXT);// course
$courseid = required_param('courseid', PARAM_TEXT);// course
$recordtitle=required_param('recordtitle', PARAM_TEXT);;

//$coursecontext = context_course::instance($courseid);
//$recordtitle = $DB->get_field('data_content', 'content', array('recordid'=>$recordid), $strictness=IGNORE_MISSING);
//$recorduserid = $DB->get_field(data_records, userid, array('id'=>$recordid), $strictness=IGNORE_MISSING);
$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
//$approved= $DB->get_field(data_records, approved, array('id'=> $recordid), $strictness=IGNORE_MISSING);



echo $OUTPUT->header();
//echo $OUTPUT->heading($strdataplural, 2);


//edit by Ranil
echo '<form action="http://localhost/moodle/mod/data/create_project.php" method="post">
            			<input type="hidden" name="ideatitle" value="'.$recordtitle.'" />
            			<input type="hidden" name="categoryid" value="'.$coursecategory.'" />
            			<input type="hidden" name="recordid" value="'.$recordid.'" />
            			<input type="submit" value="Confirm Create project">
            			</form>';



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
