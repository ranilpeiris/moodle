<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_idea_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('intro', 'idea'));

        // ----------------------------------------------------------------------
        $mform->addElement('header', 'entrieshdr', get_string('entries', 'idea'));

        $mform->addElement('selectyesno', 'approval', get_string('requireapproval', 'idea'));
        $mform->addHelpButton('approval', 'requireapproval', 'idea');

        $mform->addElement('selectyesno', 'manageapproved', get_string('manageapproved', 'idea'));
        $mform->addHelpButton('manageapproved', 'manageapproved', 'idea');
        $mform->setDefault('manageapproved', 1);
        $mform->disabledIf('manageapproved', 'approval', 'eq', 0);

        $mform->addElement('selectyesno', 'comments', get_string('allowcomments', 'idea'));

        $countoptions = array(0=>get_string('none'))+
                        (array_combine(range(1, idea_MAX_ENTRIES), // Keys.
                                        range(1, idea_MAX_ENTRIES))); // Values.
        $mform->addElement('select', 'requiredentries', get_string('requiredentries', 'idea'), $countoptions);
        $mform->addHelpButton('requiredentries', 'requiredentries', 'idea');

        $mform->addElement('select', 'requiredentriestoview', get_string('requiredentriestoview', 'idea'), $countoptions);
        $mform->addHelpButton('requiredentriestoview', 'requiredentriestoview', 'idea');

        $mform->addElement('select', 'maxentries', get_string('maxentries', 'idea'), $countoptions);
        $mform->addHelpButton('maxentries', 'maxentries', 'idea');

        // ----------------------------------------------------------------------
        $mform->addElement('header', 'availibilityhdr', get_string('availability'));

        $mform->addElement('date_time_selector', 'timeavailablefrom', get_string('availablefromdate', 'idea'),
                           array('optional' => true));

        $mform->addElement('date_time_selector', 'timeavailableto', get_string('availabletodate', 'idea'),
                           array('optional' => true));

        $mform->addElement('date_time_selector', 'timeviewfrom', get_string('viewfromdate', 'idea'),
                           array('optional' => true));

        $mform->addElement('date_time_selector', 'timeviewto', get_string('viewtodate', 'idea'),
                           array('optional' => true));

        // ----------------------------------------------------------------------
        if ($CFG->enablerssfeeds && $CFG->idea_enablerssfeeds) {
            $mform->addElement('header', 'rsshdr', get_string('rss'));
            $mform->addElement('select', 'rssarticles', get_string('numberrssarticles', 'idea') , $countoptions);
        }

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    /**
     * Enforce validation rules here
     *
     * @param array $idea array of ("fieldname"=>value) of submitted idea
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    public function validation($idea, $files) {
        $errors = parent::validation($idea, $files);

        // Check open and close times are consistent.
        if ($idea['timeavailablefrom'] && $idea['timeavailableto'] &&
                $idea['timeavailableto'] < $idea['timeavailablefrom']) {
            $errors['timeavailableto'] = get_string('availabletodatevalidation', 'idea');
        }
        if ($idea['timeviewfrom'] && $idea['timeviewto'] &&
                $idea['timeviewto'] < $idea['timeviewfrom']) {
            $errors['timeviewto'] = get_string('viewtodatevalidation', 'idea');
        }

        return $errors;
    }

    function idea_preprocessing(&$default_values){
        parent::idea_preprocessing($default_values);
    }

}

