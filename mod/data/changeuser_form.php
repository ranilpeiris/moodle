<?php
require_once("$CFG->libdir/formslib.php");

class changeuser_form extends moodleform {
	//Add elements to form
	public function definition() {
		global $CFG;
		global $DB;
		
		$recordid = $this->_customdata['recordid'];
		$dataid = $this->_customdata['dataid'];
		$courseid = $this->_customdata['courseid'];
		$recordtitle = $this->_customdata['recordtitle'];
		
				
		$userlist = $DB->get_records_sql('SELECT {user}.username FROM {user_enrolments} , {enrol} , {user} where {enrol}.courseid= ? and {user}.id = {user_enrolments}.userid' , array(2));
		
		$usernames = array_map(create_function('$o', 'return $o->username;'), $userlist);
		
		
		//		$userlist = $DB->get_records_sql('SELECT DISTINCT {user_enrolments}.userid, {user}.username , {enrol}.courseid FROM {user_enrolments} , {enrol} , {user} where {enrol}.courseid= ? and {user}.id = {user_enrolments}.userid' , array(2));
		
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
		echo " recordid is $recordid";
		
		
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



