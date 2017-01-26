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
require_once('export_form.php');

// ideabase ID
$d = required_param('d', PARAM_INT);
$exportuser = optional_param('exportuser', false, PARAM_BOOL); // Flag for exporting user details
$exporttime = optional_param('exporttime', false, PARAM_BOOL); // Flag for exporting date/time information
$exportapproval = optional_param('exportapproval', false, PARAM_BOOL); // Flag for exporting user details

$PAGE->set_url('/mod/idea/export.php', array('d'=>$d));

if (! $idea = $DB->get_record('idea', array('id'=>$d))) {
    print_error('wrongideaid', 'idea');
}

if (! $cm = get_coursemodule_from_instance('idea', $idea->id, $idea->course)) {
    print_error('invalidcoursemodule');
}

if(! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('invalidcourseid');
}

// fill in missing properties needed for updating of instance
$idea->course     = $cm->course;
$idea->cmidnumber = $cm->idnumber;
$idea->instance   = $cm->instance;

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability(idea_CAP_EXPORT, $context);

// get fields for this ideabase
$fieldrecords = $DB->get_records('idea_fields', array('ideaid'=>$idea->id), 'id');

if(empty($fieldrecords)) {
    if (has_capability('mod/idea:managetemplates', $context)) {
        redirect($CFG->wwwroot.'/mod/idea/field.php?d='.$idea->id);
    } else {
        print_error('nofieldinideabase', 'idea');
    }
}

// populate objets for this ideabases fields
$fields = array();
foreach ($fieldrecords as $fieldrecord) {
    $fields[]= idea_get_field($fieldrecord, $idea);
}


$mform = new mod_idea_export_form('export.php?d='.$idea->id, $fields, $cm, $idea);

if($mform->is_cancelled()) {
    redirect('view.php?d='.$idea->id);
} elseif (!$formidea = (array) $mform->get_data()) {
    // build header to match the rest of the UI
    $PAGE->set_title($idea->name);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($idea->name), 2);
    echo $OUTPUT->box(format_module_intro('idea', $idea, $cm->id), 'generalbox', 'intro');

    $url = new moodle_url('/mod/idea/export.php', array('d' => $d));
    groups_print_activity_menu($cm, $url);

    // these are for the tab display
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    $currenttab = 'export';
    include('tabs.php');
    $mform->display();
    echo $OUTPUT->footer();
    die;
}

$selectedfields = array();
foreach ($formidea as $key => $value) {
    //field form elements are field_1 field_2 etc. 0 if not selected. 1 if selected.
    if (strpos($key, 'field_')===0 && !empty($value)) {
        $selectedfields[] = substr($key, 6);
    }
}

$currentgroup = groups_get_activity_group($cm);

$exportidea = idea_get_exportidea($idea->id, $fields, $selectedfields, $currentgroup, $context,
                                  $exportuser, $exporttime, $exportapproval);
$count = count($exportidea);
switch ($formidea['exporttype']) {
    case 'csv':
        idea_export_csv($exportidea, $formidea['delimiter_name'], $idea->name, $count);
        break;
    case 'xls':
        idea_export_xls($exportidea, $idea->name, $count);
        break;
    case 'ods':
        idea_export_ods($exportidea, $idea->name, $count);
        break;
}

die();
