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
require_once("$CFG->libdir/rsslib.php");
require_once("$CFG->libdir/form/filemanager.php");

$id    = optional_param('id', 0, PARAM_INT);    // course module id
$d     = optional_param('d', 0, PARAM_INT);    // ideabase id
$rid   = optional_param('rid', 0, PARAM_INT);    //record id
$cancel   = optional_param('cancel', '', PARAM_RAW);    // cancel an add
$mode ='addtemplate';    //define the mode for this page, only 1 mode available



$url = new moodle_url('/mod/idea/edit.php');
if ($rid !== 0) {
    $record = $DB->get_record('idea_records', array(
            'id' => $rid,
            'ideaid' => $d,
        ), '*', MUST_EXIST);
    $url->param('rid', $rid);
}
if ($cancel !== '') {
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
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('idea', $idea->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, false, $cm);

if (isguestuser()) {
    redirect('view.php?d='.$idea->id);
}

$context = context_module::instance($cm->id);

/// If it's hidden then it doesn't show anything.  :)
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    $strideabases = get_string("modulenameplural", "idea");

    $PAGE->set_title($idea->name);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    notice(get_string("activityiscurrentlyhidden"));
}

/// Can't use this if there are no fields
if (has_capability('mod/idea:managetemplates', $context)) {
    if (!$DB->record_exists('idea_fields', array('ideaid'=>$idea->id))) {      // Brand new ideabase!
        redirect($CFG->wwwroot.'/mod/idea/field.php?d='.$idea->id);  // Redirect to field entry
    }
}

if ($rid) {
    // When editing an existing record, we require the session key
    require_sesskey();
}

// Get Group information for permission testing and record creation
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

if (!has_capability('mod/idea:manageentries', $context)) {
    if ($rid) {
        // User is editing an existing record
        if (!idea_user_can_manage_entry($record, $idea, $context)) {
            print_error('noaccess','idea');
        }
    } else if (!idea_user_can_add_entry($idea, $currentgroup, $groupmode, $context)) {
        // User is trying to create a new record
        print_error('noaccess','idea');
    }
}

if ($cancel) {
    redirect('view.php?d='.$idea->id);
}


/// RSS and CSS and JS meta
if (!empty($CFG->enablerssfeeds) && !empty($CFG->idea_enablerssfeeds) && $idea->rssarticles > 0) {
    $courseshortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));
    $rsstitle = $courseshortname . ': ' . format_string($idea->name);
    rss_add_http_header($context, 'mod_idea', $idea, $rsstitle);
}
if ($idea->csstemplate) {
    $PAGE->requires->css('/mod/idea/css.php?d='.$idea->id);
}
if ($idea->jstemplate) {
    $PAGE->requires->js('/mod/idea/js.php?d='.$idea->id, true);
}

$possiblefields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id), 'id');

foreach ($possiblefields as $field) {
    if ($field->type == 'file' || $field->type == 'picture') {
        require_once($CFG->dirroot.'/repository/lib.php');
        break;
    }
}

/// Define page variables
$stridea = get_string('modulenameplural','idea');

if ($rid) {
    $PAGE->navbar->add(get_string('editentry', 'idea'));
}

$PAGE->set_title($idea->name);
$PAGE->set_heading($course->fullname);

// Process incoming idea for adding/updating records.

// Keep track of any notifications.
$generalnotifications = array();
$fieldnotifications = array();

// Process the submitted form.
if ($idearecord = data_submitted() and confirm_sesskey()) {
    if ($rid) {
        // Updating an existing record.

        // Retrieve the format for the fields.
        $fields = $DB->get_records('idea_fields', array('ideaid' => $idearecord->d));

        // Validate the form to ensure that enough idea was submitted.
        $processedidea = idea_process_submission($idea, $fields, $idearecord);

        // Add the new notification idea.
        $generalnotifications = array_merge($generalnotifications, $processedidea->generalnotifications);
        $fieldnotifications = array_merge($fieldnotifications, $processedidea->fieldnotifications);

        if ($processedidea->validated) {
            // Enough idea to update the record.

            // Obtain the record to be updated.

            // Reset the approved flag after edit if the user does not have permission to approve their own entries.
            if (!has_capability('mod/idea:approve', $context)) {
                $record->approved = 1;
            }

            // Update the parent record.
            $record->timemodified = time();
            $DB->update_record('idea_records', $record);

            // Update all content.
            foreach ($processedidea->fields as $fieldname => $field) {
                $field->update_content($rid, $idearecord->$fieldname, $fieldname);
            }

            // Trigger an event for updating this record.
            $event = \mod_idea\event\record_updated::create(array(
                'objectid' => $rid,
                'context' => $context,
                'courseid' => $course->id,
                'other' => array(
                    'ideaid' => $idea->id
                )
            ));
            $event->add_record_snapshot('idea', $idea);
            $event->trigger();

            $viewurl = new moodle_url('/mod/idea/view.php', array(
                'd' => $idea->id,
                'rid' => $rid,
            ));
            redirect($viewurl);
        }

    } else {
        // No recordid was specified - creating a new entry.

        // Retrieve the format for the fields.
        $fields = $DB->get_records('idea_fields', array('ideaid' => $idearecord->d));

        // Validate the form to ensure that enough idea was submitted.
        $processedidea = idea_process_submission($idea, $fields, $idearecord);

        // Add the new notification idea.
        $generalnotifications = array_merge($generalnotifications, $processedidea->generalnotifications);
        $fieldnotifications = array_merge($fieldnotifications, $processedidea->fieldnotifications);

        // Add instance to idea_record.
        if ($processedidea->validated && $recordid = idea_add_record($idea, $currentgroup)) {

            // Insert a whole lot of empty records to make sure we have them.
            $records = array();
            foreach ($fields as $field) {
                $content = new stdClass();
                $content->recordid = $recordid;
                $content->fieldid = $field->id;
                $records[] = $content;
            }

            // Bulk insert the records now. Some records may have no idea but all must exist.
            $DB->insert_records('idea_content', $records);

            // Add all provided content.
            foreach ($processedidea->fields as $fieldname => $field) {
                $field->update_content($recordid, $idearecord->$fieldname, $fieldname);
            }

            // Trigger an event for updating this record.
            $event = \mod_idea\event\record_created::create(array(
                'objectid' => $rid,
                'context' => $context,
                'courseid' => $course->id,
                'other' => array(
                    'ideaid' => $idea->id
                )
            ));
            $event->add_record_snapshot('idea', $idea);
            $event->trigger();

            if (!empty($idearecord->saveandview)) {
                $viewurl = new moodle_url('/mod/idea/view.php', array(
                    'd' => $idea->id,
                    'rid' => $recordid,
                ));
                redirect($viewurl);
            } else if (!empty($idearecord->saveandadd)) {
                // User has clicked "Save and add another". Reset all of the fields.
                $idearecord = null;
            }
        }
    }
}
// End of form processing.


/// Print the page header

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($idea->name), 2);
echo $OUTPUT->box(format_module_intro('idea', $idea, $cm->id), 'generalbox', 'intro');
groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/idea/edit.php?d='.$idea->id);

/// Print the tabs

$currenttab = 'add';
if ($rid) {
    $editentry = true;  //used in tabs
}
include('tabs.php');


/// Print the browsing interface

$patterns = array();    //tags to replace
$replacement = array();    //html to replace those yucky tags

//form goes here first in case add template is empty
echo '<form enctype="multipart/form-idea" action="edit.php" method="post">';
echo '<div>';
echo '<input name="d" value="'.$idea->id.'" type="hidden" />';
echo '<input name="rid" value="'.$rid.'" type="hidden" />';
echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />';
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

if (!$rid){
    echo $OUTPUT->heading(get_string('newentry','idea'), 3);
}

/******************************************
 * Regular expression replacement section *
 ******************************************/
if ($idea->addtemplate){
    $possiblefields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id), 'id');
    $patterns = array();
    $replacements = array();

    ///then we generate strings to replace
    foreach ($possiblefields as $eachfield){
        $field = idea_get_field($eachfield, $idea);

        // To skip unnecessary calls to display_add_field().
        if (strpos($idea->addtemplate, "[[".$field->field->name."]]") !== false) {
            // Replace the field tag.
            $patterns[] = "[[".$field->field->name."]]";
            $errors = '';
            if (!empty($fieldnotifications[$field->field->name])) {
                foreach ($fieldnotifications[$field->field->name] as $notification) {
                    $errors .= $OUTPUT->notification($notification);
                }
            }
            $replacements[] = $errors . $field->display_add_field($rid, $idearecord);
        }

        // Replace the field id tag.
        $patterns[] = "[[".$field->field->name."#id]]";
        $replacements[] = 'field_'.$field->field->id;
    }
    $newtext = str_ireplace($patterns, $replacements, $idea->{$mode});

} else {    //if the add template is not yet defined, print the default form!
    echo idea_generate_default_template($idea, 'addtemplate', $rid, true, false);
    $newtext = '';
}

foreach ($generalnotifications as $notification) {
    echo $OUTPUT->notification($notification);
}
echo $newtext;

echo '<div class="mdl-align m-t-1"><input type="submit" class="btn btn-primary" name="saveandview" ' .
     'value="' . get_string('saveandview', 'idea') . '" />';
if ($rid) {
    echo '&nbsp;<input type="submit" class="btn btn-primary" name="cancel" ' .
         'value="' . get_string('cancel') . '" onclick="javascript:history.go(-1)" />';
} else {
    if ((!$idea->maxentries) ||
            has_capability('mod/idea:manageentries', $context) ||
            (idea_numentries($idea) < ($idea->maxentries - 1))) {
        echo '&nbsp;<input type="submit" class="btn btn-primary" name="saveandadd" ' .
             'value="' . get_string('saveandadd', 'idea') . '" />';
    }
}
echo '</div>';
echo $OUTPUT->box_end();
echo '</div></form>';


/// Finish the page

// Print the stuff that need to come after the form fields.
if (!$fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id))) {
    print_error('nofieldinideabase', 'idea');
}
foreach ($fields as $eachfield) {
    $field = idea_get_field($eachfield, $idea);
    $field->print_after_form();
}

echo $OUTPUT->footer();
