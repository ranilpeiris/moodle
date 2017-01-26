<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden!');
}
require_once($CFG->libdir . '/formslib.php');


class idea_existing_preset_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'presets', get_string('usestandard', 'idea'));
        $this->_form->addHelpButton('presets', 'usestandard', 'idea');

        $this->_form->addElement('hidden', 'd');
        $this->_form->setType('d', PARAM_INT);
        $this->_form->addElement('hidden', 'action', 'confirmdelete');
        $this->_form->setType('action', PARAM_ALPHANUM);
        $delete = get_string('delete');
        foreach ($this->_customdata['presets'] as $preset) {
            $this->_form->addElement('radio', 'fullname', null, ' '.$preset->description, $preset->userid.'/'.$preset->shortname);
        }
        $this->_form->addElement('submit', 'importexisting', get_string('choose'));
    }
}

class idea_import_preset_zip_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'uploadpreset', get_string('fromfile', 'idea'));
        $this->_form->addHelpButton('uploadpreset', 'fromfile', 'idea');

        $this->_form->addElement('hidden', 'd');
        $this->_form->setType('d', PARAM_INT);
        $this->_form->addElement('hidden', 'action', 'importzip');
        $this->_form->setType('action', PARAM_ALPHANUM);
        $this->_form->addElement('filepicker', 'importfile', get_string('chooseorupload', 'idea'));
        $this->_form->addRule('importfile', null, 'required');
        $this->_form->addElement('submit', 'uploadzip', get_string('import'));
    }
}

class idea_export_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'exportheading', get_string('exportaszip', 'idea'));
        $this->_form->addElement('hidden', 'd');
        $this->_form->setType('d', PARAM_INT);
        $this->_form->addElement('hidden', 'action', 'export');
        $this->_form->setType('action', PARAM_ALPHANUM);
        $this->_form->addElement('submit', 'export', get_string('export', 'idea'));
    }
}

class idea_save_preset_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'exportheading', get_string('saveaspreset', 'idea'));
        $this->_form->addElement('hidden', 'd');
        $this->_form->setType('d', PARAM_INT);
        $this->_form->addElement('hidden', 'action', 'save2');
        $this->_form->setType('action', PARAM_ALPHANUM);
        $this->_form->addElement('text', 'name', get_string('name'));
        $this->_form->setType('name', PARAM_FILE);
        $this->_form->addRule('name', null, 'required');
        $this->_form->addElement('checkbox', 'overwrite', get_string('overwrite', 'idea'), get_string('overrwritedesc', 'idea'));
        $this->_form->addElement('submit', 'saveaspreset', get_string('continue'));
    }
}
