<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is part of the ideabase module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_idea
 */

require_once('../../config.php');
require_once('lib.php');

$id             = optional_param('id', 0, PARAM_INT);            // course module id
$d              = optional_param('d', 0, PARAM_INT);             // ideabase id
$fid            = optional_param('fid', 0 , PARAM_INT);          // update field id
$newtype        = optional_param('newtype','',PARAM_ALPHA);      // type of the new field
$mode           = optional_param('mode','',PARAM_ALPHA);
$defaultsort    = optional_param('defaultsort', 0, PARAM_INT);
$defaultsortdir = optional_param('defaultsortdir', 0, PARAM_INT);
$cancel         = optional_param('cancel', 0, PARAM_BOOL);

if ($cancel) {
    $mode = 'list';
}

$url = new moodle_url('/mod/idea/field.php');
if ($fid !== 0) {
    $url->param('fid', $fid);
}
if ($newtype !== '') {
    $url->param('newtype', $newtype);
}
if ($mode !== '') {
    $url->param('mode', $mode);
}
if ($defaultsort !== 0) {
    $url->param('defaultsort', $defaultsort);
}
if ($defaultsortdir !== 0) {
    $url->param('defaultsortdir', $defaultsortdir);
}
if ($cancel !== 0) {
    $url->param('cancel', $cancel);
}

if ($id) {
    $url->param('id', $id);
    $PAGE->set_url($url);
    if (! $cm = get_coursemodule_from_id('idea', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
        print_error('coursemisconf');
    }
    if (! $idea = $DB->get_record('idea', array('id'=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    $url->param('d', $d);
    $PAGE->set_url($url);
    if (! $idea = $DB->get_record('idea', array('id'=>$d))) {
        print_error('invalidid', 'idea');
    }
    if (! $course = $DB->get_record('course', array('id'=>$idea->course))) {
        print_error('invalidcoursemodule');
    }
    if (! $cm = get_coursemodule_from_instance('idea', $idea->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/idea:managetemplates', $context);

/************************************
 *        idea Processing           *
 ***********************************/
switch ($mode) {

    case 'add':    ///add a new field
        if (confirm_sesskey() and $fieldinput = data_submitted()){

            //$fieldinput->name = idea_clean_field_name($fieldinput->name);

        /// Only store this new field if it doesn't already exist.
            if (($fieldinput->name == '') or idea_fieldname_exists($fieldinput->name, $idea->id)) {

                $displaynoticebad = get_string('invalidfieldname','idea');

            } else {

            /// Check for arrays and convert to a comma-delimited string
                idea_convert_arrays_to_strings($fieldinput);

            /// Create a field object to collect and store the idea safely
                $type = required_param('type', PARAM_FILE);
                $field = idea_get_field_new($type, $idea);

                $field->define_field($fieldinput);
                $field->insert_field();

            /// Update some templates
                idea_append_new_field_to_templates($idea, $fieldinput->name);

                $displaynoticegood = get_string('fieldadded','idea');
            }
        }
        break;


    case 'update':    ///update a field
        if (confirm_sesskey() and $fieldinput = data_submitted()){

            //$fieldinput->name = idea_clean_field_name($fieldinput->name);

            if (($fieldinput->name == '') or idea_fieldname_exists($fieldinput->name, $idea->id, $fieldinput->fid)) {

                $displaynoticebad = get_string('invalidfieldname','idea');

            } else {
            /// Check for arrays and convert to a comma-delimited string
                idea_convert_arrays_to_strings($fieldinput);

            /// Create a field object to collect and store the idea safely
                $field = idea_get_field_from_id($fid, $idea);
                $oldfieldname = $field->field->name;

                $field->field->name = $fieldinput->name;
                $field->field->description = $fieldinput->description;
                $field->field->required = !empty($fieldinput->required) ? 1 : 0;

                for ($i=1; $i<=10; $i++) {
                    if (isset($fieldinput->{'param'.$i})) {
                        $field->field->{'param'.$i} = $fieldinput->{'param'.$i};
                    } else {
                        $field->field->{'param'.$i} = '';
                    }
                }

                $field->update_field();

            /// Update the templates.
                idea_replace_field_in_templates($idea, $oldfieldname, $field->field->name);

                $displaynoticegood = get_string('fieldupdated','idea');
            }
        }
        break;


    case 'delete':    // Delete a field
        if (confirm_sesskey()){

            if ($confirm = optional_param('confirm', 0, PARAM_INT)) {


                // Delete the field completely
                if ($field = idea_get_field_from_id($fid, $idea)) {
                    $field->delete_field();

                    // Update the templates.
                    idea_replace_field_in_templates($idea, $field->field->name, '');

                    // Update the default sort field
                    if ($fid == $idea->defaultsort) {
                        $rec = new stdClass();
                        $rec->id = $idea->id;
                        $rec->defaultsort = 0;
                        $rec->defaultsortdir = 0;
                        $DB->update_record('idea', $rec);
                    }

                    $displaynoticegood = get_string('fielddeleted', 'idea');
                }

            } else {

                idea_print_header($course,$cm,$idea, false);

                // Print confirmation message.
                $field = idea_get_field_from_id($fid, $idea);

                echo $OUTPUT->confirm('<strong>'.$field->name().': '.$field->field->name.'</strong><br /><br />'. get_string('confirmdeletefield','idea'),
                             'field.php?d='.$idea->id.'&mode=delete&fid='.$fid.'&confirm=1',
                             'field.php?d='.$idea->id);

                echo $OUTPUT->footer();
                exit;
            }
        }
        break;


    case 'sort':    // Set the default sort parameters
        if (confirm_sesskey()) {
            $rec = new stdClass();
            $rec->id = $idea->id;
            $rec->defaultsort = $defaultsort;
            $rec->defaultsortdir = $defaultsortdir;

            $DB->update_record('idea', $rec);
            redirect($CFG->wwwroot.'/mod/idea/field.php?d='.$idea->id, get_string('changessaved'), 2);
            exit;
        }
        break;

    default:
        break;
}



/// Print the browsing interface

///get the list of possible fields (plugins)
$plugins = core_component::get_plugin_list('ideafield');
$menufield = array();

foreach ($plugins as $plugin=>$fulldir){
    $menufield[$plugin] = get_string('pluginname', 'ideafield_'.$plugin);    //get from language files
}
asort($menufield);    //sort in alphabetical order
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagetype('mod-idea-field-' . $newtype);
if (($mode == 'new') && (!empty($newtype)) && confirm_sesskey()) {          ///  Adding a new field
    idea_print_header($course, $cm, $idea,'fields');

    $field = idea_get_field_new($newtype, $idea);
    $field->display_edit_field();

} else if ($mode == 'display' && confirm_sesskey()) { /// Display/edit existing field
    idea_print_header($course, $cm, $idea,'fields');

    $field = idea_get_field_from_id($fid, $idea);
    $field->display_edit_field();

} else {                                              /// Display the main listing of all fields
    idea_print_header($course, $cm, $idea,'fields');

    if (!$DB->record_exists('idea_fields', array('ideaid'=>$idea->id))) {
        echo $OUTPUT->notification(get_string('nofieldinideabase','idea'));  // nothing in ideabase
        echo $OUTPUT->notification(get_string('pleaseaddsome','idea', 'preset.php?id='.$cm->id));      // link to presets

    } else {    //else print quiz style list of fields

        $table = new html_table();
        $table->head = array(
            get_string('fieldname', 'idea'),
            get_string('type', 'idea'),
            get_string('required', 'idea'),
            get_string('fielddescription', 'idea'),
            get_string('action', 'idea'),
        );
        $table->align = array('left','left','left', 'center');
        $table->wrap = array(false,false,false,false);

        if ($fff = $DB->get_records('idea_fields', array('ideaid'=>$idea->id),'id')){
            foreach ($fff as $ff) {

                $field = idea_get_field($ff, $idea);

                $baseurl = new moodle_url('/mod/idea/field.php', array(
                    'd'         => $idea->id,
                    'fid'       => $field->field->id,
                    'sesskey'   => sesskey(),
                ));

                $displayurl = new moodle_url($baseurl, array(
                    'mode'      => 'display',
                ));

                $deleteurl = new moodle_url($baseurl, array(
                    'mode'      => 'delete',
                ));

                $table->data[] = array(
                    html_writer::link($displayurl, $field->field->name),
                    $field->image() . '&nbsp;' . $field->name(),
                    $field->field->required ? get_string('yes') : get_string('no'),
                    shorten_text($field->field->description, 30),
                    html_writer::link($displayurl, $OUTPUT->pix_icon('t/edit', get_string('edit'))) .
                        '&nbsp;' .
                        html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete'))),
                );
            }
        }
        echo html_writer::table($table);
    }


    echo '<div class="fieldadd">';
    $popupurl = $CFG->wwwroot.'/mod/idea/field.php?d='.$idea->id.'&mode=new&sesskey='.  sesskey();
    echo $OUTPUT->single_select(new moodle_url($popupurl), 'newtype', $menufield, null, array('' => 'choosedots'),
        'fieldform', array('label' => get_string('newfield', 'idea')));
    echo $OUTPUT->help_icon('newfield', 'idea');
    echo '</div>';

    echo '<div class="sortdefault">';
    echo '<form id="sortdefault" action="'.$CFG->wwwroot.'/mod/idea/field.php" method="get">';
    echo '<div>';
    echo '<input type="hidden" name="d" value="'.$idea->id.'" />';
    echo '<input type="hidden" name="mode" value="sort" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<label for="defaultsort">'.get_string('defaultsortfield','idea').'</label>';
    echo '<select id="defaultsort" name="defaultsort" class="custom-select">';
    if ($fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id))) {
        echo '<optgroup label="'.get_string('fields', 'idea').'">';
        foreach ($fields as $field) {
            if ($idea->defaultsort == $field->id) {
                echo '<option value="'.$field->id.'" selected="selected">'.$field->name.'</option>';
            } else {
                echo '<option value="'.$field->id.'">'.$field->name.'</option>';
            }
        }
        echo '</optgroup>';
    }
    $options = array();
    $options[idea_TIMEADDED]    = get_string('timeadded', 'idea');
// TODO: we will need to change defaultsort db to unsinged to make these work in 2.0
/*        $options[idea_TIMEMODIFIED] = get_string('timemodified', 'idea');
    $options[idea_FIRSTNAME]    = get_string('authorfirstname', 'idea');
    $options[idea_LASTNAME]     = get_string('authorlastname', 'idea');
    if ($idea->approval and has_capability('mod/idea:approve', $context)) {
        $options[idea_APPROVED] = get_string('approved', 'idea');
    }*/
    echo '<optgroup label="'.get_string('other', 'idea').'">';
    foreach ($options as $key => $name) {
        if ($idea->defaultsort == $key) {
            echo '<option value="'.$key.'" selected="selected">'.$name.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$name.'</option>';
        }
    }
    echo '</optgroup>';
    echo '</select>';

    $options = array(0 => get_string('ascending', 'idea'),
                     1 => get_string('descending', 'idea'));
    echo html_writer::label(get_string('sortby'), 'menudefaultsortdir', false, array('class' => 'accesshide'));
    echo html_writer::select($options, 'defaultsortdir', $idea->defaultsortdir, false, array('class' => 'custom-select'));
    echo '<input type="submit" class="btn btn-secondary m-l-1" value="'.get_string('save', 'idea').'" />';
    echo '</div>';
    echo '</form>';
    echo '</div>';

}

/// Finish the page
echo $OUTPUT->footer();

