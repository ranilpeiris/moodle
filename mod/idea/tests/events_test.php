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
 * Events tests.
 *
 * @package mod_idea
 * @category test
 * @copyright 2014 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class mod_idea_events_testcase extends advanced_testcase {

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test the field created event.
     */
    public function test_field_created() {
        $this->setAdminUser();

        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Now we want to create a field.
        $field = idea_get_field_new('text', $idea);
        $fieldidea = new stdClass();
        $fieldidea->name = 'Test';
        $fieldidea->description = 'Test description';
        $field->define_field($fieldidea);

        // Trigger and capture the event for creating a field.
        $sink = $this->redirectEvents();
        $field->insert_field();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\field_created', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'fields add', 'field.php?d=' . $idea->id . '&amp;mode=display&amp;fid=' .
            $field->field->id, $field->field->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/field.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the field updated event.
     */
    public function test_field_updated() {
        $this->setAdminUser();

        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Now we want to create a field.
        $field = idea_get_field_new('text', $idea);
        $fieldidea = new stdClass();
        $fieldidea->name = 'Test';
        $fieldidea->description = 'Test description';
        $field->define_field($fieldidea);
        $field->insert_field();

        // Trigger and capture the event for updating the field.
        $sink = $this->redirectEvents();
        $field->update_field();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\field_updated', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'fields update', 'field.php?d=' . $idea->id . '&amp;mode=display&amp;fid=' .
            $field->field->id, $field->field->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/field.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the field deleted event.
     */
    public function test_field_deleted() {
        $this->setAdminUser();

        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Now we want to create a field.
        $field = idea_get_field_new('text', $idea);
        $fieldidea = new stdClass();
        $fieldidea->name = 'Test';
        $fieldidea->description = 'Test description';
        $field->define_field($fieldidea);
        $field->insert_field();

        // Trigger and capture the event for deleting the field.
        $sink = $this->redirectEvents();
        $field->delete_field();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\field_deleted', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'fields delete', 'field.php?d=' . $idea->id, $field->field->name, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/field.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the record created event.
     */
    public function test_record_created() {
        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Trigger and capture the event for creating the record.
        $sink = $this->redirectEvents();
        $recordid = idea_add_record($idea);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\record_created', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'add', 'view.php?d=' . $idea->id . '&amp;rid=' . $recordid,
            $idea->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'rid' => $recordid));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the record updated event.
     *
     * There is no external API for updating a record, so the unit test will simply create
     * and trigger the event and ensure the legacy log idea is returned as expected.
     */
    public function test_record_updated() {
        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Trigger an event for updating this record.
        $event = \mod_idea\event\record_updated::create(array(
            'objectid' => 1,
            'context' => context_module::instance($idea->cmid),
            'courseid' => $course->id,
            'other' => array(
                'ideaid' => $idea->id
            )
        ));

        // Trigger and capture the event for updating the idea record.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\record_updated', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'update', 'view.php?d=' . $idea->id . '&amp;rid=1', $idea->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'rid' => $event->objectid));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the record deleted event.
     */
    public function test_record_deleted() {
        global $DB;

        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Now we want to create a field.
        $field = idea_get_field_new('text', $idea);
        $fieldidea = new stdClass();
        $fieldidea->name = 'Test';
        $fieldidea->description = 'Test description';
        $field->define_field($fieldidea);
        $field->insert_field();

        // Create idea record.
        $idearecords = new stdClass();
        $idearecords->userid = '2';
        $idearecords->ideaid = $idea->id;
        $idearecords->id = $DB->insert_record('idea_records', $idearecords);

        // Create idea content.
        $ideacontent = new stdClass();
        $ideacontent->fieldid = $field->field->id;
        $ideacontent->recordid = $idearecords->id;
        $ideacontent->id = $DB->insert_record('idea_content', $ideacontent);

        // Trigger and capture the event for deleting the idea record.
        $sink = $this->redirectEvents();
        idea_delete_record($idearecords->id, $idea, $course->id, $idea->cmid);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\record_deleted', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'record delete', 'view.php?id=' . $idea->cmid, $idea->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/view.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the template viewed event.
     *
     * There is no external API for viewing templates, so the unit test will simply create
     * and trigger the event and ensure the legacy log idea is returned as expected.
     */
    public function test_template_viewed() {
        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Trigger an event for updating this record.
        $event = \mod_idea\event\template_viewed::create(array(
            'context' => context_module::instance($idea->cmid),
            'courseid' => $course->id,
            'other' => array(
                'ideaid' => $idea->id
            )
        ));

        // Trigger and capture the event for updating the idea record.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\template_viewed', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'templates view', 'templates.php?id=' . $idea->cmid . '&amp;d=' .
            $idea->id, $idea->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/templates.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }

    /**
     * Test the template updated event.
     *
     * There is no external API for updating a template, so the unit test will simply create
     * and trigger the event and ensure the legacy log idea is returned as expected.
     */
    public function test_template_updated() {
        // Create a course we are going to add a idea module to.
        $course = $this->getideaGenerator()->create_course();

        // The generator used to create a idea module.
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');

        // Create a idea module.
        $idea = $generator->create_instance(array('course' => $course->id));

        // Trigger an event for updating this record.
        $event = \mod_idea\event\template_updated::create(array(
            'context' => context_module::instance($idea->cmid),
            'courseid' => $course->id,
            'other' => array(
                'ideaid' => $idea->id,
            )
        ));

        // Trigger and capture the event for updating the idea record.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event idea is valid.
        $this->assertInstanceOf('\mod_idea\event\template_updated', $event);
        $this->assertEquals(context_module::instance($idea->cmid), $event->get_context());
        $expected = array($course->id, 'idea', 'templates saved', 'templates.php?id=' . $idea->cmid . '&amp;d=' .
            $idea->id, $idea->id, $idea->cmid);
        $this->assertEventLegacyLogidea($expected, $event);
        $this->assertEventContextNotUsed($event);
        $url = new moodle_url('/mod/idea/templates.php', array('d' => $idea->id));
        $this->assertEquals($url, $event->get_url());
    }
}
