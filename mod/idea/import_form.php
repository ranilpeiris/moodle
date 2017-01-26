<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

class mod_idea_import_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $cmid = $this->_customidea['id'];

        $mform->addElement('filepicker', 'recordsfile', get_string('csvfile', 'idea'));

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'fielddelimiter', get_string('fielddelimiter', 'idea'), $delimiters);
        $mform->setDefault('fielddelimiter', 'comma');

        $mform->addElement('text', 'fieldenclosure', get_string('fieldenclosure', 'idea'));
        $mform->setType('fieldenclosure', PARAM_CLEANHTML);
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('fileencoding', 'mod_idea'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $submit_string = get_string('submit');
        // idea id
        $mform->addElement('hidden', 'd');
        $mform->setType('d', PARAM_INT);

        $this->add_action_buttons(false, $submit_string);
    }
}
