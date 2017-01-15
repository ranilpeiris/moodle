<?php
require_once("$CFG->libdir/formslib.php");

class changeuser_form extends moodleform {
	//Add elements to form
	public function definition() {
		global $CFG;
		global $DB;
		global $USER;
		
		$recordid = $this->_customdata['recordid'];
		$dataid = $this->_customdata['dataid'];
		$courseid = $this->_customdata['courseid'];
		$recordtitle = $this->_customdata['recordtitle'];
		
		
		echo "The selected idea : <h3> $recordtitle </h3></br>";
		
		//get list of users in this course
		$userlist = $DB->get_records_sql('SELECT {user}.username, {user}.firstname, {user}.lastname FROM {course} 
										JOIN {context} ON {course}.id = {context}.instanceid
										JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
										JOIN {user} ON {user}.id = {role_assignments}.userid
										JOIN {role} ON {role}.id = {role_assignments}.roleid
										where {course}.id = ? ' , array($courseid));
												
	
		// create and call to a function that accept array of recors and combine columns 
		$usernames = array_map(create_function('$o', '$userdetail= "$o->username : $o->firstname $o->lastname";   return $userdetail;'), $userlist);
			
		$mform = $this->_form; // Don't forget the underscore!
		/////
		$mform->registerNoSubmitButton('addotags');
		$otagsgrp = array();
		$otagsgrp[] =& $mform->createElement('select', 'otagsadd', 'Select a user name ',$usernames);
		$otagsgrp[] =& $mform->createElement('submit', 'addotags', 'Select');
		$mform->addGroup($otagsgrp, 'otagsgrp', 'New user for project ', array(' '), false);
		$mform->setType('otagsadd', PARAM_NOTAGS);
			
		
		$mform->addElement('hidden', 'recordid', $recordid);
		$mform->setType('recordid', PARAM_TEXT);
		$mform->setDefault('recordid',$recordid);
		$mform->addElement('hidden', 'dataid', $dataid);
		$mform->setType('dataid', PARAM_TEXT);
		$mform->setDefault('dataid',$dataid);
		$mform->addElement('hidden', 'courseid', $courseid);
		$mform->setType('courseid', PARAM_TEXT);
		$mform->setDefault('courseid',$courseid);
		$mform->addElement('hidden', 'recordtitle', $recordtitle);
		$mform->setType('recordtitle', PARAM_TEXT);
		$mform->setDefault('recordtitle',$recordtitle);
		////
	
		
	}
	
	function get_submit_value($elementname) {
		$mform = & $this->_form;
		return $mform->getSubmitValue($elementname);
		
	}
	
	//Custom validation should be added here
	function validation($data, $files) {
		return array();
	}
}



