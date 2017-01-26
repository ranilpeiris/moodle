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
 * Preset Menu
 *
 * This is the page that is the menu item in the config ideabase
 * pages.
 *
 * This file is part of the ideabase module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_idea
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/idea/lib.php');
require_once($CFG->dirroot.'/mod/idea/preset_form.php');
require_once($CFG->libdir.'/xmlize.php');

$id     = optional_param('id', 0, PARAM_INT);           // course module id
if ($id) {
    $cm = get_coursemodule_from_id('idea', $id, null, null, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $idea = $DB->get_record('idea', array('id'=>$cm->instance), '*', MUST_EXIST);
} else {
    $d = required_param('d', PARAM_INT);     // ideabase activity id
    $idea = $DB->get_record('idea', array('id'=>$d), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$idea->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('idea', $idea->id, $course->id, null, MUST_EXIST);
}

$context = context_module::instance($cm->id, MUST_EXIST);
require_login($course, false, $cm);
require_capability('mod/idea:managetemplates', $context);
$PAGE->set_url(new moodle_url('/mod/idea/preset.php', array('d'=>$idea->id)));
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

// fill in missing properties needed for updating of instance
$idea->course     = $cm->course;
$idea->cmidnumber = $cm->idnumber;
$idea->instance   = $cm->instance;

$presets = idea_get_available_presets($context);
$strdelete = get_string('deleted', 'idea');
foreach ($presets as &$preset) {
    if (!empty($preset->userid)) {
        $namefields = get_all_user_name_fields(true);
        $presetuser = $DB->get_record('user', array('id' => $preset->userid), 'id, ' . $namefields, MUST_EXIST);
        $preset->description = $preset->name.' ('.fullname($presetuser, true).')';
    } else {
        $preset->userid = 0;
        $preset->description = $preset->name;
        if (idea_user_can_delete_preset($context, $preset) && $preset->name != 'Image gallery') {
            $delurl = new moodle_url('/mod/idea/preset.php', array('d'=> $idea->id, 'action'=>'confirmdelete', 'fullname'=>$preset->userid.'/'.$preset->shortname, 'sesskey'=>sesskey()));
            $delicon = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'class'=>'iconsmall', 'alt'=>$strdelete.' '.$preset->description));
            $preset->description .= html_writer::link($delurl, $delicon);
        }
    }
    if ($preset->userid > 0 && idea_user_can_delete_preset($context, $preset)) {
        $delurl = new moodle_url('/mod/idea/preset.php', array('d'=> $idea->id, 'action'=>'confirmdelete', 'fullname'=>$preset->userid.'/'.$preset->shortname, 'sesskey'=>sesskey()));
        $delicon = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'class'=>'iconsmall', 'alt'=>$strdelete.' '.$preset->description));
        $preset->description .= html_writer::link($delurl, $delicon);
    }
}
// This is required because its currently bound to the last element in the array.
// If someone were to inadvently use it again and this call were not here
unset($preset);

$form_importexisting = new idea_existing_preset_form(null, array('presets'=>$presets));
$form_importexisting->set_data(array('d' => $idea->id));

$form_importzip = new idea_import_preset_zip_form();
$form_importzip->set_data(array('d' => $idea->id));

$form_export = new idea_export_form();
$form_export->set_data(array('d' => $idea->id));

$form_save = new idea_save_preset_form();
$form_save->set_data(array('d' => $idea->id, 'name'=>$idea->name));

/* Output */
if (!$form_export->is_submitted()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($idea->name), 2);

    // Needed for tabs.php
    $currenttab = 'presets';
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    echo $OUTPUT->box(format_module_intro('idea', $idea, $cm->id), 'generalbox', 'intro');

    include('tabs.php');
}

if (optional_param('sesskey', false, PARAM_BOOL) && confirm_sesskey()) {

    $renderer = $PAGE->get_renderer('mod_idea');

    if ($formidea = $form_importexisting->get_data()) {
        $importer = new idea_preset_existing_importer($course, $cm, $idea, $formidea->fullname);
        echo $renderer->import_setting_mappings($idea, $importer);
        echo $OUTPUT->footer();
        exit(0);
    } else if ($formidea = $form_importzip->get_data()) {
        $file = new stdClass;
        $file->name = $form_importzip->get_new_filename('importfile');
        $file->path = $form_importzip->save_temp_file('importfile');
        $importer = new idea_preset_upload_importer($course, $cm, $idea, $file->path);
        echo $renderer->import_setting_mappings($idea, $importer);
        echo $OUTPUT->footer();
        exit(0);
    } else if ($formidea = $form_export->get_data()) {

        if (headers_sent()) {
            print_error('headersent');
        }

        $exportfile = idea_presets_export($course, $cm, $idea);
        $exportfilename = basename($exportfile);
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$exportfilename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');
        $exportfilehandler = fopen($exportfile, 'rb');
        print fread($exportfilehandler, filesize($exportfile));
        fclose($exportfilehandler);
        unlink($exportfile);
        exit(0);

    } else if ($formidea = $form_save->get_data()) {
        if (!empty($formidea->overwrite)) {
            $selectedpreset = new stdClass();
            foreach ($presets as $preset) {
                if ($preset->name == $formidea->name) {
                    $selectedpreset = $preset;
                    break;
                }
            }
            if (isset($selectedpreset->name)) {
                if (idea_user_can_delete_preset($context, $selectedpreset)) {
                    idea_delete_site_preset($formidea->name);
                } else {
                    print_error('cannotoverwritepreset', 'idea');
                }
            }
        }

        // If the preset exists now then we need to throw an error.
        $sitepresets = idea_get_available_site_presets($context);
        foreach ($sitepresets as $key=>$preset) {
            if ($formidea->name == $preset->name) {
                print_error('errorpresetexists', 'preset');
            }
        }

        // Save the preset now
        idea_presets_save($course, $cm, $idea, $formidea->name);

        echo $OUTPUT->notification(get_string('savesuccess', 'idea'), 'notifysuccess');
        echo $OUTPUT->continue_button($PAGE->url);
        echo $OUTPUT->footer();
        exit(0);
    } else {
        $action = optional_param('action', null, PARAM_ALPHANUM);
        $fullname = optional_param('fullname', '', PARAM_PATH); // directory the preset is in
        //
        // find out preset owner userid and shortname
        $parts = explode('/', $fullname, 2);
        $userid = empty($parts[0]) ? 0 : (int)$parts[0];
        $shortname = empty($parts[1]) ? '' : $parts[1];

        if ($userid && ($userid != $USER->id) && !has_capability('mod/idea:viewalluserpresets', $context)) {
            print_error('cannotaccesspresentsother', 'idea');
        }

        if ($action == 'confirmdelete') {
            $path = idea_preset_path($course, $userid, $shortname);
            $strwarning = get_string('deletewarning', 'idea').'<br />'.$shortname;
            $optionsyes = array('fullname' => $userid.'/'.$shortname,
                             'action' => 'delete',
                             'd' => $idea->id);
            $optionsno = array('d' => $idea->id);
            echo $OUTPUT->confirm($strwarning, new moodle_url('preset.php', $optionsyes), new moodle_url('preset.php', $optionsno));
            echo $OUTPUT->footer();
            exit(0);
        } else if ($action == 'delete') {
            $selectedpreset = new stdClass();
            foreach ($presets as $preset) {
                if ($preset->shortname == $shortname) {
                    $selectedpreset = $preset;
                }
            }
            if (!isset($selectedpreset->shortname) || !idea_user_can_delete_preset($context, $selectedpreset)) {
               print_error('invalidrequest');
            }

            idea_delete_site_preset($shortname);

            $strdeleted = get_string('deleted', 'idea');
            echo $OUTPUT->notification("$shortname $strdeleted", 'notifysuccess');
        } else if ($action == 'finishimport') {
            $overwritesettings = optional_param('overwritesettings', false, PARAM_BOOL);
            if (!$fullname) {
                $presetdir = $CFG->tempdir.'/forms/'.required_param('directory', PARAM_FILE);
                if (!file_exists($presetdir) || !is_dir($presetdir)) {
                    print_error('cannotimport');
                }
                $importer = new idea_preset_upload_importer($course, $cm, $idea, $presetdir);
            } else {
                $importer = new idea_preset_existing_importer($course, $cm, $idea, $fullname);
            }
            $importer->import($overwritesettings);
            $strimportsuccess = get_string('importsuccess', 'idea');
            $straddentries = get_string('addentries', 'idea');
            $strtoideabase = get_string('toideabase', 'idea');
            if (!$DB->get_records('idea_records', array('ideaid'=>$idea->id))) {
                echo $OUTPUT->notification("$strimportsuccess <a href='edit.php?d=$idea->id'>$straddentries</a> $strtoideabase", 'notifysuccess');
            } else {
                echo $OUTPUT->notification("$strimportsuccess", 'notifysuccess');
            }
        }
        echo $OUTPUT->continue_button($PAGE->url);
        echo $OUTPUT->footer();
        exit(0);
    }
}

// Export forms
echo $OUTPUT->heading(get_string('export', 'idea'), 3);
$form_export->display();
$form_save->display();

// Import forms
echo $OUTPUT->heading(get_string('import'), 3);
$form_importzip->display();
$form_importexisting->display();

echo $OUTPUT->footer();
