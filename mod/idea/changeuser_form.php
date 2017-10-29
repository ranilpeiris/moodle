<?php
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/coursecatlib.php");


class changeuser_form extends moodleform {
	//Add elements to form
	public function definition() {
		global $CFG;
		global $DB;
		global $USER;

		$recordid = $this->_customdata['recordid'];
		$ideaid = $this->_customdata['ideaid'];
		$courseid = $this->_customdata['courseid'];
		$recordtitle = $this->_customdata['recordtitle'];
		$recordusername= $this->_customdata['recordusername'];
		$convertuserrole= $this->_customdata['convertuserrole'];
		$maincoursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
		
		echo "<h5> <span style='color:black'> The selected idea : </span> $recordtitle <span style='color:black'> Author of this idea :</span> $recordusername  </h5>  ";
				
		//get list of users in this course
		if($convertuserrole=="Student"){
			$studentuserlist = $DB->get_records_sql("SELECT {user}.id, {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE {role}.id = 5 AND {course}.id = ?" , array($courseid));
					
		$usernames = get_students_no_projects( $maincoursecategory ,  $studentuserlist );
		
		}elseif($convertuserrole=="Supervisor")
		
				
		{
		$userlist = $DB->get_records_sql('SELECT {user}.username, {user}.firstname ,{user}.lastname FROM {course}
		JOIN {context}  ON {context}.instanceid = {course}.id JOIN {role_assignments}  ON {context}.id = {role_assignments}.contextid AND
		{context}.contextlevel = 50 JOIN {role}  ON {role_assignments}.roleid = {role}.id JOIN {user}  ON {user}.id = {role_assignments}.userid WHERE ({role}.id = 4 OR {role}.id = 3 OR {role}.id = 1 ) AND {course}.id = ?' , array($courseid));

		}

		// create and call to a function that accept array of recors and combine columns
		
		if($convertuserrole=="Supervisor"){
			$usernames = array_map(create_function('$o','return $o->username;' ), $userlist);
		} 

		
		//<>for full name $usernames = array_map(create_function('$o', '$userdetail= "$o->username : $o->firstname $o->lastname";   return $userdetail;'), $userlist);

		$mform = $this->_form; // Don't forget the underscore

		/////
		$mform->registerNoSubmitButton('addotags');
		$otagsgrp = array();
		$otagsgrp[] =& $mform->createElement('select', 'otagsadd', 'Select a user name ',$usernames);
		$otagsgrp[] =& $mform->createElement('submit', 'addotags', 'please Select');
		$mform->addGroup($otagsgrp, 'otagsgrp', 'New user for project ', array(' '), false);
		$mform->setType('otagsadd', PARAM_TEXT);
			
		//add parameters to pass
		$mform->addElement('hidden', 'recordid', $recordid);
		$mform->setType('recordid', PARAM_TEXT);
		$mform->setDefault('recordid',$recordid);
		$mform->addElement('hidden', 'ideaid', $ideaid);
		$mform->setType('ideaid', PARAM_TEXT);
		$mform->setDefault('ideaid',$ideaid);
		$mform->addElement('hidden', 'courseid', $courseid);
		$mform->setType('courseid', PARAM_TEXT);
		$mform->setDefault('courseid',$courseid);
		$mform->addElement('hidden', 'recordtitle', $recordtitle);
		$mform->setType('recordtitle', PARAM_TEXT);
		$mform->setDefault('recordtitle',$recordtitle);
		////


	}

	//to enable button press to submit

	function get_submit_value($elementname) {
		$mform = & $this->_form;
		return $mform->getSubmitValue($elementname);

	}

	//Custom validation should be added here
	function validation($idea, $files) {
		return array();
	}
	
	//Function return students those have <=1 projects(courses)
	
	
}

/** function get_students_no_projects( $category ,  $userlist ){

	$catcourses = coursecat::get($category)->get_courses();


	$usernames=array();

	foreach($userlist as $author){ //for each studnts in the list

		$usercount = 0;
		foreach($catcourses as $acourse) { //for all cources in the category
			$cContext = context_course::instance($acourse->id);
				
			if ($roles = get_user_roles($cContext,$author->id)) {
				foreach ($roles as $role) {
					$authorroleid = $role->roleid; //role id of the user on the course
				}
				if($authorroleid==5){
					$usercount++;
				}
			}
		}
		if ($usercount>=1) {
			$usernames[ (string) $author->username] = (string) $author->username;
				
		}
	}
	return $usernames;
}
*/
function get_students_no_projects( $category ,  $userlist ){
	global $DB;
	$catcourses = coursecat::get($category)->get_courses();
	
	
	$usernames=array();
	
	foreach($userlist as $author){ //for each studnts in the list
		$matchedprojectcount=0;
		$matchedprojectcount = $DB->count_records('idea_records', array('studentid'=>$author->id));
		$numberof = 0;
		if ($matchedprojectcount<1) {
			$usernames[ (string) $author->username] = (string) $author->username;		
		}
	}
	return $usernames;
}




