<?php
require_once("$CFG->libdir/formslib.php");

class changeuser_form extends moodleform {
	//Add elements to form
	public function definition() {
		
		$mform = $this->_form; // Don't forget the underscore!
		/////
		$mform->addElement('checkbox', 'approvedidea', 'Show matched ideas');
		$mform->addElement('checkbox', 'notapprovedidea', 'Show matched ideas');
		$mform->addElement('checkbox', 'supervisoridea', 'Show matched ideas');
		$mform->addElement('checkbox', 'studentidea', 'Show matched ideas');
		
		$mform->createElement('select', 'otagsadd', 'Select a user name ',$usernames);
		$otagsgrp[] =& $mform->createElement('submit', 'addotags', 'Select');
		$mform->addGroup($otagsgrp, 'otagsgrp', 'New user for project ', array(' '), false);
		$mform->setType('otagsadd', PARAM_NOTAGS);
			
		//add parameters to pass
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
	
	//to enable button press to submit
	
	function get_submit_value($elementname) {
		$mform = & $this->_form;
		return $mform->getSubmitValue($elementname);
		
	}
	
	//Custom validation should be added here
	function validation($data, $files) {
		return array();
	}
}



