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
 * @copyright 1990 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_idea
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/idea/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$context = context_course::instance($course->id);

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_idea\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strname = get_string('name');
$stridea = get_string('modulename','idea');
$strideaplural  = get_string('modulenameplural','idea');

$PAGE->navbar->add($stridea, new moodle_url('/mod/idea/index.php', array('id'=>$course->id)));
$PAGE->set_title($stridea);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strideaplural, 2);

if (! $ideas = get_all_instances_in_course("idea", $course)) {
    notice(get_string('thereareno', 'moodle',$strideaplural) , "$CFG->wwwroot/course/view.php?id=$course->id");
}

$usesections = course_format_uses_sections($course->format);

$timenow  = time();
$strname  = get_string('name');
$strdescription = get_string("description");
$strentries = get_string('entries', 'idea');
$strnumnotapproved = get_string('numnotapproved', 'idea');

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strdescription, $strentries, $strnumnotapproved);
    $table->align = array ('center', 'center', 'center', 'center', 'center');
} else {
    $table->head  = array ($strname, $strdescription, $strentries, $strnumnotapproved);
    $table->align = array ('center', 'center', 'center', 'center');
}

$rss = (!empty($CFG->enablerssfeeds) && !empty($CFG->idea_enablerssfeeds));

if ($rss) {
    require_once($CFG->libdir."/rsslib.php");
    array_push($table->head, 'RSS');
    array_push($table->align, 'center');
}

$options = new stdClass();
$options->noclean = true;

$currentsection = "";

foreach ($ideas as $idea) {

    $printsection = "";

    //Calculate the href
    if (!$idea->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$idea->coursemodule\">".format_string($idea->name,true)."</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$idea->coursemodule\">".format_string($idea->name,true)."</a>";
    }

    // TODO: add group restricted counts here, and limit unapproved to ppl with approve cap only + link to approval page

    $numrecords = $DB->count_records_sql('SELECT COUNT(r.id) FROM {idea_records} r WHERE r.ideaid =?', array($idea->id));

    if ($idea->approval == 1) {
        $numunapprovedrecords = $DB->count_records_sql('SELECT COUNT(r.id) FROM {idea_records} r WHERE r.ideaid =? AND r.approved <> 1', array($idea->id));
    } else {
        $numunapprovedrecords = '-';
    }

    $rsslink = '';
    if ($rss && $idea->rssarticles > 0) {
        $rsslink = rss_get_link($context->id, $USER->id, 'mod_idea', $idea->id, 'RSS');
    }

    if ($usesections) {
        if ($idea->section !== $currentsection) {
            if ($idea->section) {
                $printsection = get_section_name($course, $idea->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $idea->section;
        }
        $row = array ($printsection, $link, format_text($idea->intro, $idea->introformat, $options), $numrecords, $numunapprovedrecords);

    } else {
        $row = array ($link, format_text($idea->intro, $idea->introformat, $options), $numrecords, $numunapprovedrecords);
    }

    if ($rss) {
        array_push($row, $rsslink);
    }

    $table->data[] = $row;
}

echo "<br />";
echo html_writer::tag('div', html_writer::table($table), array('class'=>'no-overflow'));
echo $OUTPUT->footer();

