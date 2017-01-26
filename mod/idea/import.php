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
require_once($CFG->libdir.'/csvlib.class.php');
require_once('import_form.php');

$id              = optional_param('id', 0, PARAM_INT);  // course module id
$d               = optional_param('d', 0, PARAM_INT);   // ideabase id
$rid             = optional_param('rid', 0, PARAM_INT); // record id
$fielddelimiter  = optional_param('fielddelimiter', ',', PARAM_CLEANHTML); // characters used as field delimiters for csv file import
$fieldenclosure = optional_param('fieldenclosure', '', PARAM_CLEANHTML);   // characters used as record delimiters for csv file import

$url = new moodle_url('/mod/idea/import.php');
if ($rid !== 0) {
    $url->param('rid', $rid);
}
if ($fielddelimiter !== '') {
    $url->param('fielddelimiter', $fielddelimiter);
}
if ($fieldenclosure !== '') {
    $url->param('fieldenclosure', $fieldenclosure);
}

if ($id) {
    $url->param('id', $id);
    $PAGE->set_url($url);
    $cm     = get_coursemodule_from_id('idea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $idea   = $DB->get_record('idea', array('id'=>$cm->instance), '*', MUST_EXIST);

} else {
    $url->param('d', $d);
    $PAGE->set_url($url);
    $idea   = $DB->get_record('idea', array('id'=>$d), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$idea->course), '*', MUST_EXIST);
    $cm     = get_coursemodule_from_instance('idea', $idea->id, $course->id, false, MUST_EXIST);
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/idea:manageentries', $context);
$form = new mod_idea_import_form(new moodle_url('/mod/idea/import.php'));

/// Print the page header
$PAGE->navbar->add(get_string('add', 'idea'));
$PAGE->set_title($idea->name);
$PAGE->set_heading($course->fullname);
navigation_node::override_active_url(new moodle_url('/mod/idea/import.php', array('d' => $idea->id)));
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('uploadrecords', 'mod_idea'), 'uploadrecords', 'mod_idea');

/// Groups needed for Add entry tab
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

if (!$formidea = $form->get_data()) {
    /// Upload records section. Only for teachers and the admin.
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    require_once('import_form.php');
    $form = new mod_idea_import_form(new moodle_url('/mod/idea/import.php'));
    $formidea = new stdClass();
    $formidea->d = $idea->id;
    $form->set_data($formidea);
    $form->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die;
} else {
    // Large files are likely to take their time and memory. Let PHP know
    // that we'll take longer, and that the process should be recycled soon
    // to free up memory.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);

    $iid = csv_import_reader::get_new_iid('modidea');
    $cir = new csv_import_reader($iid, 'modidea');

    $filecontent = $form->get_file_content('recordsfile');
    $readcount = $cir->load_csv_content($filecontent, $formidea->encoding, $formidea->fielddelimiter);
    unset($filecontent);
    if (empty($readcount)) {
        print_error('csvfailed','idea',"{$CFG->wwwroot}/mod/idea/edit.php?d={$idea->id}");
    } else {
        if (!$fieldnames = $cir->get_columns()) {
            print_error('cannotreadtmpfile', 'error');
        }
        $fieldnames = array_flip($fieldnames);
        // check the fieldnames are valid
        $rawfields = $DB->get_records('idea_fields', array('ideaid' => $idea->id), '', 'name, id, type');
        $fields = array();
        $errorfield = '';
        foreach ($fieldnames as $name => $id) {
            if (!isset($rawfields[$name])) {
                $errorfield .= "'$name' ";
            } else {
                $field = $rawfields[$name];
                require_once("$CFG->dirroot/mod/idea/field/$field->type/field.class.php");
                $classname = 'idea_field_' . $field->type;
                $fields[$name] = new $classname($field, $idea, $cm);
            }
        }

        if (!empty($errorfield)) {
            print_error('fieldnotmatched','idea',"{$CFG->wwwroot}/mod/idea/edit.php?d={$idea->id}",$errorfield);
        }

        $cir->init();
        $recordsadded = 0;
        while ($record = $cir->next()) {
            if ($recordid = idea_add_record($idea, 0)) {  // add instance to idea_record
                foreach ($fields as $field) {
                    $fieldid = $fieldnames[$field->field->name];
                    if (isset($record[$fieldid])) {
                        $value = $record[$fieldid];
                    } else {
                        $value = '';
                    }

                    if (method_exists($field, 'update_content_import')) {
                        $field->update_content_import($recordid, $value, 'field_' . $field->field->id);
                    } else {
                        $content = new stdClass();
                        $content->fieldid = $field->field->id;
                        $content->content = $value;
                        $content->recordid = $recordid;
                        $DB->insert_record('idea_content', $content);
                    }
                }
                $recordsadded++;
                print get_string('added', 'moodle', $recordsadded) . ". " . get_string('entry', 'idea') . " (ID $recordid)<br />\n";
            }
        }
        $cir->close();
        $cir->cleanup(true);
    }
}

if ($recordsadded > 0) {
    echo $OUTPUT->notification($recordsadded. ' '. get_string('recordssaved', 'idea'), '');
} else {
    echo $OUTPUT->notification(get_string('recordsnotsaved', 'idea'), 'notifysuccess');
}

echo $OUTPUT->continue_button('import.php?d='.$idea->id);

/// Finish the page
echo $OUTPUT->footer();
