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
 * @package    mod_idea
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_idea_activity_task
 */

/**
 * Structure step to restore one idea activity
 */
class restore_idea_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('idea', '/activity/idea');
        $paths[] = new restore_path_element('idea_field', '/activity/idea/fields/field');
        if ($userinfo) {
            $paths[] = new restore_path_element('idea_record', '/activity/idea/records/record');
            $paths[] = new restore_path_element('idea_content', '/activity/idea/records/record/contents/content');
            $paths[] = new restore_path_element('idea_rating', '/activity/idea/records/record/ratings/rating');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_idea($idea) {
        global $DB;

        $idea = (object)$idea;
        $oldid = $idea->id;
        $idea->course = $this->get_courseid();

        $idea->timeavailablefrom = $this->apply_date_offset($idea->timeavailablefrom);
        $idea->timeavailableto = $this->apply_date_offset($idea->timeavailableto);
        $idea->timeviewfrom = $this->apply_date_offset($idea->timeviewfrom);
        $idea->timeviewto = $this->apply_date_offset($idea->timeviewto);
        $idea->assesstimestart = $this->apply_date_offset($idea->assesstimestart);
        $idea->assesstimefinish = $this->apply_date_offset($idea->assesstimefinish);
        // Added in 3.1, hence conditional.
        $idea->timemodified = isset($idea->timemodified) ? $this->apply_date_offset($idea->timemodified) : time();

        if ($idea->scale < 0) { // scale found, get mapping
            $idea->scale = -($this->get_mappingid('scale', abs($idea->scale)));
        }

        // Some old backups can arrive with idea->notification = null (MDL-24470)
        // convert them to proper column default (zero)
        if (is_null($idea->notification)) {
            $idea->notification = 0;
        }

        // insert the idea record
        $newitemid = $DB->insert_record('idea', $idea);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_idea_field($idea) {
        global $DB;

        $idea = (object)$idea;
        $oldid = $idea->id;

        $idea->ideaid = $this->get_new_parentid('idea');

        // insert the idea_fields record
        $newitemid = $DB->insert_record('idea_fields', $idea);
        $this->set_mapping('idea_field', $oldid, $newitemid, false); // no files associated
    }

    protected function process_idea_record($idea) {
        global $DB;

        $idea = (object)$idea;
        $oldid = $idea->id;

        $idea->timecreated = $this->apply_date_offset($idea->timecreated);
        $idea->timemodified = $this->apply_date_offset($idea->timemodified);

        $idea->userid = $this->get_mappingid('user', $idea->userid);
        $idea->groupid = $this->get_mappingid('group', $idea->groupid);
        $idea->ideaid = $this->get_new_parentid('idea');

        // insert the idea_records record
        $newitemid = $DB->insert_record('idea_records', $idea);
        $this->set_mapping('idea_record', $oldid, $newitemid, false); // no files associated
    }

    protected function process_idea_content($idea) {
        global $DB;

        $idea = (object)$idea;
        $oldid = $idea->id;

        $idea->fieldid = $this->get_mappingid('idea_field', $idea->fieldid);
        $idea->recordid = $this->get_new_parentid('idea_record');

        // insert the idea_content record
        $newitemid = $DB->insert_record('idea_content', $idea);
        $this->set_mapping('idea_content', $oldid, $newitemid, true); // files by this itemname
    }

    protected function process_idea_rating($idea) {
        global $DB;

        $idea = (object)$idea;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $idea->contextid = $this->task->get_contextid();
        $idea->itemid    = $this->get_new_parentid('idea_record');
        if ($idea->scaleid < 0) { // scale found, get mapping
            $idea->scaleid = -($this->get_mappingid('scale', abs($idea->scaleid)));
        }
        $idea->rating = $idea->value;
        $idea->userid = $this->get_mappingid('user', $idea->userid);
        $idea->timecreated = $this->apply_date_offset($idea->timecreated);
        $idea->timemodified = $this->apply_date_offset($idea->timemodified);

        // We need to check that component and ratingarea are both set here.
        if (empty($idea->component)) {
            $idea->component = 'mod_idea';
        }
        if (empty($idea->ratingarea)) {
            $idea->ratingarea = 'entry';
        }

        $newitemid = $DB->insert_record('rating', $idea);
    }

    protected function after_execute() {
        global $DB;
        // Add idea related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_idea', 'intro', null);
        // Add content related files, matching by itemname (idea_content)
        $this->add_related_files('mod_idea', 'content', 'idea_content');
        // Adjust the idea->defaultsort field
        if ($defaultsort = $DB->get_field('idea', 'defaultsort', array('id' => $this->get_new_parentid('idea')))) {
            if ($defaultsort = $this->get_mappingid('idea_field', $defaultsort)) {
                $DB->set_field('idea', 'defaultsort', $defaultsort, array('id' => $this->get_new_parentid('idea')));
            }
        }
    }
}
