<?php
require_once("$CFG->libdir/formslib.php");


class confirmunmatch_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        global $DB;
      
                
        $mform = $this->_form; // Don't forget the underscore! 
      
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Confirm Unmatch');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
           
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}