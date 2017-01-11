<?php 
require_once("$CFG->libdir/formslib.php");
class createproject_form extends moodleform
{
		public function definition() {
			global $CFG;
		
			$mform = $this->_form; // Don't forget the underscore!
		
			$mform->addElement('text', 'Message', 'Pleaes carefully check the idea deatils'); // Add elements to your form
			$mform->setType('email', PARAM_NOTAGS);                   //Set type of element
			$mform->setDefault('email', 'Please enter email');        //Default value
		}
		//Custom validation should be added here
		function validation($data, $files) {
			return array();
		}
	
	
}