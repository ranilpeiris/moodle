<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

// This file to be included so we can assume config.php has already been included.
// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($idea) or empty($course)) {
        print_error('cannotcallscript');
    }

    $context = context_module::instance($cm->id);

    $row = array();

    $row[] = new tabobject('list', new moodle_url('/mod/idea/view.php', array('d' => $idea->id)), get_string('list','idea'));

    if (isset($record)) {
        $row[] = new tabobject('single', new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'rid' => $record->id)), get_string('single','idea'));
    } else {
        $row[] = new tabobject('single', new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'mode' => 'single')), get_string('single','idea'));
    }

    // Add an advanced search tab.
    $row[] = new tabobject('asearch', new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'mode' => 'asearch')), get_string('search', 'idea'));

    if (isloggedin()) { // just a perf shortcut
        if (idea_user_can_add_entry($idea, $currentgroup, $groupmode, $context)) { // took out participation list here!
            $addstring = empty($editentry) ? get_string('add', 'idea') : get_string('editentry', 'idea');
            $row[] = new tabobject('add', new moodle_url('/mod/idea/edit.php', array('d' => $idea->id)), $addstring);
        }
        if (has_capability(idea_CAP_EXPORT, $context)) {
            // The capability required to Export ideabase records is centrally defined in 'lib.php'
            // and should be weaker than those required to edit Templates, Fields and Presets.
            $row[] = new tabobject('export', new moodle_url('/mod/idea/export.php', array('d' => $idea->id)),
                         get_string('export', 'idea'));
        }
        if (has_capability('mod/idea:managetemplates', $context)) {
            if ($currenttab == 'list') {
                $defaultemplate = 'listtemplate';
            } else if ($currenttab == 'add') {
                $defaultemplate = 'addtemplate';
            } else if ($currenttab == 'asearch') {
                $defaultemplate = 'asearchtemplate';
            } else {
                $defaultemplate = 'singletemplate';
            }

            $templatestab = new tabobject('templates', new moodle_url('/mod/idea/templates.php', array('d' => $idea->id, 'mode' => $defaultemplate)),
                         get_string('templates','idea'));
            $row[] = $templatestab;
            $row[] = new tabobject('fields', new moodle_url('/mod/idea/field.php', array('d' => $idea->id)),
                         get_string('fields','idea'));
            $row[] = new tabobject('presets', new moodle_url('/mod/idea/preset.php', array('d' => $idea->id)),
                         get_string('presets', 'idea'));
        }
    }

    if ($currenttab == 'templates' and isset($mode) && isset($templatestab)) {
        $templatestab->inactive = true;
        $templatelist = array ('listtemplate', 'singletemplate', 'asearchtemplate', 'addtemplate', 'rsstemplate', 'csstemplate', 'jstemplate');

        $currenttab ='';
        foreach ($templatelist as $template) {
            $templatestab->subtree[] = new tabobject($template, new moodle_url('/mod/idea/templates.php', array('d' => $idea->id, 'mode' => $template)), get_string($template, 'idea'));
            if ($template == $mode) {
                $currenttab = $template;
            }
        }
        if ($currenttab == '') {
            $currenttab = $mode = 'singletemplate';
        }
    }

// Print out the tabs and continue!
    echo $OUTPUT->tabtree($row, $currenttab);


