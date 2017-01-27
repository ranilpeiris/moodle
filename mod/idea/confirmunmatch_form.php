<?php
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/coursecatlib.php");


class confirmunmatch_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        
        $maincourseid = $this->_customdata['maincourseid'];
        $studentid = $this->_customdata['studentid'];
        $supervisorid = $this->_customdata['supervisorid'];
        $studentid = $this->_customdata['studentid'];
        $ideatitle = $this->_customdata['ideatitle'];
        
        $supervisordetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($supervisorid));
        $studentdetails = $DB->get_record_sql('SELECT username, firstname, Lastname FROM {user} WHERE id = ?', array($studentid));
        $coursedetails = $DB->get_record_sql('SELECT fullname FROM {user} WHERE id = ?', array($maincourseid));
        
 
        $mform = $this->_form; // Don't forget the underscore! 
       
        echo "<h5> The Program Name: $coursedetails </h5> ";
        
        echo "<h5> The title of the matched project to be unmatched: $coursedetails ";
        echo " <h5> <span style='color:black' > The supervisor of the match::</span> $supervisordetails->username : $supervisordetails->firstname  $supervisordetails->lastname <h5> ";
        echo " <h5> <span style='color:black' > The student of the match  :</span> $studentdetails->username : $studentdetails->firstname  $studentdetails->lastname</H5> </br>";
       
        $mform->registerNoSubmitButton('addotags');
        $mform->createElement('submit', 'addotags', 'Confirm unmatch');
        //$mform->setType('otagsadd', PARAM_NOTAGS);
        
        
        
        add_action_buttons($cancel = true, $submitlabel='Confirm unmatch');
       
           
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}