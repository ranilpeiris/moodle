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
 * Unit tests for lib.php
 *
 * @package    mod_idea
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/idea/lib.php');

/**
 * Unit tests for lib.php
 *
 * @package    mod_idea
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_idea_lib_testcase extends advanced_testcase {

    /**
     * @var moodle_ideabase
     */
    protected $DB = null;

    /**
     * Tear Down to reset DB.
     */
    public function tearDown() {
        global $DB;

        if (isset($this->DB)) {
            $DB = $this->DB;
            $this->DB = null;
        }
    }

    public function test_idea_delete_record() {
        global $DB;

        $this->resetAfterTest();

        // Create a record for deleting.
        $this->setAdminUser();
        $course = $this->getideaGenerator()->create_course();
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = "Mod idea delete test";
        $record->intro = "Some intro of some sort";

        $module = $this->getideaGenerator()->create_module('idea', $record);

        $field = idea_get_field_new('text', $module);

        $fielddetail = new stdClass();
        $fielddetail->d = $module->id;
        $fielddetail->mode = 'add';
        $fielddetail->type = 'text';
        $fielddetail->sesskey = sesskey();
        $fielddetail->name = 'Name';
        $fielddetail->description = 'Some name';

        $field->define_field($fielddetail);
        $field->insert_field();
        $recordid = idea_add_record($module);

        $ideacontent = array();
        $ideacontent['fieldid'] = $field->field->id;
        $ideacontent['recordid'] = $recordid;
        $ideacontent['content'] = 'Asterix';

        $contentid = $DB->insert_record('idea_content', $ideacontent);
        $cm = get_coursemodule_from_instance('idea', $module->id, $course->id);

        // Check to make sure that we have a ideabase record.
        $idea = $DB->get_records('idea', array('id' => $module->id));
        $this->assertEquals(1, count($idea));

        $ideacontent = $DB->get_records('idea_content', array('id' => $contentid));
        $this->assertEquals(1, count($ideacontent));

        $ideafields = $DB->get_records('idea_fields', array('id' => $field->field->id));
        $this->assertEquals(1, count($ideafields));

        $idearecords = $DB->get_records('idea_records', array('id' => $recordid));
        $this->assertEquals(1, count($idearecords));

        // Test to see if a failed delete returns false.
        $result = idea_delete_record(8798, $module, $course->id, $cm->id);
        $this->assertFalse($result);

        // Delete the record.
        $result = idea_delete_record($recordid, $module, $course->id, $cm->id);

        // Check that all of the record is gone.
        $ideacontent = $DB->get_records('idea_content', array('id' => $contentid));
        $this->assertEquals(0, count($ideacontent));

        $idearecords = $DB->get_records('idea_records', array('id' => $recordid));
        $this->assertEquals(0, count($idearecords));

        // Make sure the function returns true on a successful deletion.
        $this->assertTrue($result);
    }

    /**
     * Test comment_created event.
     */
    public function test_idea_comment_created_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/comment/lib.php');

        $this->resetAfterTest();

        // Create a record for deleting.
        $this->setAdminUser();
        $course = $this->getideaGenerator()->create_course();
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = "Mod idea delete test";
        $record->intro = "Some intro of some sort";
        $record->comments = 1;

        $module = $this->getideaGenerator()->create_module('idea', $record);
        $field = idea_get_field_new('text', $module);

        $fielddetail = new stdClass();
        $fielddetail->name = 'Name';
        $fielddetail->description = 'Some name';

        $field->define_field($fielddetail);
        $field->insert_field();
        $recordid = idea_add_record($module);

        $ideacontent = array();
        $ideacontent['fieldid'] = $field->field->id;
        $ideacontent['recordid'] = $recordid;
        $ideacontent['content'] = 'Asterix';

        $contentid = $DB->insert_record('idea_content', $ideacontent);
        $cm = get_coursemodule_from_instance('idea', $module->id, $course->id);

        $context = context_module::instance($module->cmid);
        $cmt = new stdClass();
        $cmt->context = $context;
        $cmt->course = $course;
        $cmt->cm = $cm;
        $cmt->area = 'ideabase_entry';
        $cmt->itemid = $recordid;
        $cmt->showcount = true;
        $cmt->component = 'mod_idea';
        $comment = new comment($cmt);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $comment->add('New comment');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_idea\event\comment_created', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new moodle_url('/mod/idea/view.php', array('id' => $cm->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test comment_deleted event.
     */
    public function test_idea_comment_deleted_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/comment/lib.php');

        $this->resetAfterTest();

        // Create a record for deleting.
        $this->setAdminUser();
        $course = $this->getideaGenerator()->create_course();
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = "Mod idea delete test";
        $record->intro = "Some intro of some sort";
        $record->comments = 1;

        $module = $this->getideaGenerator()->create_module('idea', $record);
        $field = idea_get_field_new('text', $module);

        $fielddetail = new stdClass();
        $fielddetail->name = 'Name';
        $fielddetail->description = 'Some name';

        $field->define_field($fielddetail);
        $field->insert_field();
        $recordid = idea_add_record($module);

        $ideacontent = array();
        $ideacontent['fieldid'] = $field->field->id;
        $ideacontent['recordid'] = $recordid;
        $ideacontent['content'] = 'Asterix';

        $contentid = $DB->insert_record('idea_content', $ideacontent);
        $cm = get_coursemodule_from_instance('idea', $module->id, $course->id);

        $context = context_module::instance($module->cmid);
        $cmt = new stdClass();
        $cmt->context = $context;
        $cmt->course = $course;
        $cmt->cm = $cm;
        $cmt->area = 'ideabase_entry';
        $cmt->itemid = $recordid;
        $cmt->showcount = true;
        $cmt->component = 'mod_idea';
        $comment = new comment($cmt);
        $newcomment = $comment->add('New comment 1');

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $comment->delete($newcomment->id);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_idea\event\comment_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new moodle_url('/mod/idea/view.php', array('id' => $module->cmid));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Checks that idea_user_can_manage_entry will return true if the user
     * has the mod/idea:manageentries capability.
     */
    public function test_idea_user_can_manage_entry_return_true_with_capability() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();

        $this->setUser($user);

        assign_capability('mod/idea:manageentries', CAP_ALLOW, $roleid, $context);

        $this->assertTrue(idea_user_can_manage_entry($record, $idea, $context),
            'idea_user_can_manage_entry() returns true if the user has mod/idea:manageentries capability');
    }

    /**
     * Checks that idea_user_can_manage_entry will return false if the idea
     * is set to readonly.
     */
    public function test_idea_user_can_manage_entry_return_false_readonly() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        // Causes readonly mode to be enabled.
        $idea = new stdClass();
        $now = time();
        // Add a small margin around the periods to prevent errors with slow tests.
        $idea->timeviewfrom = $now - 1;
        $idea->timeviewto = $now + 5;

        $this->assertFalse(idea_user_can_manage_entry($record, $idea, $context),
            'idea_user_can_manage_entry() returns false if the idea is read only');
    }

    /**
     * Checks that idea_user_can_manage_entry will return false if the record
     * can't be found in the ideabase.
     */
    public function test_idea_user_can_manage_entry_return_false_no_record() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();
        // Causes readonly mode to be disabled.
        $now = time();
        $idea->timeviewfrom = $now + 100;
        $idea->timeviewto = $now - 100;

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        // Pass record id instead of object to force DB lookup.
        $this->assertFalse(idea_user_can_manage_entry(1, $idea, $context),
            'idea_user_can_manage_entry() returns false if the record cannot be found');
    }

    /**
     * Checks that idea_user_can_manage_entry will return false if the record
     * isn't owned by the user.
     */
    public function test_idea_user_can_manage_entry_return_false_not_owned_record() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();
        // Causes readonly mode to be disabled.
        $now = time();
        $idea->timeviewfrom = $now + 100;
        $idea->timeviewto = $now - 100;
        // Make sure the record isn't owned by this user.
        $record->userid = $user->id + 1;

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        $this->assertFalse(idea_user_can_manage_entry($record, $idea, $context),
            'idea_user_can_manage_entry() returns false if the record isnt owned by the user');
    }

    /**
     * Checks that idea_user_can_manage_entry will return true if the idea
     * doesn't require approval.
     */
    public function test_idea_user_can_manage_entry_return_true_idea_no_approval() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();
        // Causes readonly mode to be disabled.
        $now = time();
        $idea->timeviewfrom = $now + 100;
        $idea->timeviewto = $now - 100;
        // The record doesn't need approval.
        $idea->approval = false;
        // Make sure the record is owned by this user.
        $record->userid = $user->id;

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        $this->assertTrue(idea_user_can_manage_entry($record, $idea, $context),
            'idea_user_can_manage_entry() returns true if the record doesnt require approval');
    }

    /**
     * Checks that idea_user_can_manage_entry will return true if the record
     * isn't yet approved.
     */
    public function test_idea_user_can_manage_entry_return_true_record_unapproved() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();
        // Causes readonly mode to be disabled.
        $now = time();
        $idea->timeviewfrom = $now + 100;
        $idea->timeviewto = $now - 100;
        // The record needs approval.
        $idea->approval = true;
        // Make sure the record is owned by this user.
        $record->userid = $user->id;
        // The record hasn't yet been approved.
        $record->approved = false;

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        $this->assertTrue(idea_user_can_manage_entry($record, $idea, $context),
            'idea_user_can_manage_entry() returns true if the record is not yet approved');
    }

    /**
     * Checks that idea_user_can_manage_entry will return the 'manageapproved'
     * value if the record has already been approved.
     */
    public function test_idea_user_can_manage_entry_return_manageapproved() {

        $this->resetAfterTest();
        $testidea = $this->create_user_test_idea();

        $user = $testidea['user'];
        $course = $testidea['course'];
        $roleid = $testidea['roleid'];
        $context = $testidea['context'];
        $record = $testidea['record'];
        $idea = new stdClass();
        // Causes readonly mode to be disabled.
        $now = time();
        $idea->timeviewfrom = $now + 100;
        $idea->timeviewto = $now - 100;
        // The record needs approval.
        $idea->approval = true;
        // Can the user managed approved records?
        $idea->manageapproved = false;
        // Make sure the record is owned by this user.
        $record->userid = $user->id;
        // The record has been approved.
        $record->approved = true;

        $this->setUser($user);

        // Need to make sure they don't have this capability in order to fall back to
        // the other checks.
        assign_capability('mod/idea:manageentries', CAP_PROHIBIT, $roleid, $context);

        $canmanageentry = idea_user_can_manage_entry($record, $idea, $context);

        // Make sure the result of the check is what ever the manageapproved setting
        // is set to.
        $this->assertEquals($idea->manageapproved, $canmanageentry,
            'idea_user_can_manage_entry() returns the manageapproved setting on approved records');
    }

    /**
     * Helper method to create a set of test idea for idea_user_can_manage tests
     *
     * @return array contains user, course, roleid, module, context and record
     */
    private function create_user_test_idea() {
        $user = $this->getideaGenerator()->create_user();
        $course = $this->getideaGenerator()->create_course();
        $roleid = $this->getideaGenerator()->create_role();
        $record = new stdClass();
        $record->name = "test name";
        $record->intro = "test intro";
        $record->comments = 1;
        $record->course = $course->id;
        $record->userid = $user->id;

        $module = $this->getideaGenerator()->create_module('idea', $record);
        $cm = get_coursemodule_from_instance('idea', $module->id, $course->id);
        $context = context_module::instance($module->cmid);

        $this->getideaGenerator()->role_assign($roleid, $user->id, $context->id);

        return array(
            'user' => $user,
            'course' => $course,
            'roleid' => $roleid,
            'module' => $module,
            'context' => $context,
            'record' => $record
        );
    }

    /**
     * Tests for mod_idea_rating_can_see_item_ratings().
     *
     * @throws coding_exception
     * @throws rating_exception
     */
    public function test_mod_idea_rating_can_see_item_ratings() {
        global $DB;

        $this->resetAfterTest();

        // Setup test idea.
        $course = new stdClass();
        $course->groupmode = SEPARATEGROUPS;
        $course->groupmodeforce = true;
        $course = $this->getideaGenerator()->create_course($course);
        $idea = $this->getideaGenerator()->create_module('idea', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('idea', $idea->id);
        $context = context_module::instance($cm->id);

        // Create users.
        $user1 = $this->getideaGenerator()->create_user();
        $user2 = $this->getideaGenerator()->create_user();
        $user3 = $this->getideaGenerator()->create_user();
        $user4 = $this->getideaGenerator()->create_user();

        // Groups and stuff.
        $role = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
        $this->getideaGenerator()->enrol_user($user1->id, $course->id, $role->id);
        $this->getideaGenerator()->enrol_user($user2->id, $course->id, $role->id);
        $this->getideaGenerator()->enrol_user($user3->id, $course->id, $role->id);
        $this->getideaGenerator()->enrol_user($user4->id, $course->id, $role->id);

        $group1 = $this->getideaGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getideaGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1, $user1);
        groups_add_member($group1, $user2);
        groups_add_member($group2, $user3);
        groups_add_member($group2, $user4);

        // Add idea.
        $field = idea_get_field_new('text', $idea);

        $fielddetail = new stdClass();
        $fielddetail->name = 'Name';
        $fielddetail->description = 'Some name';

        $field->define_field($fielddetail);
        $field->insert_field();

        // Add a record with a group id of zero (all participants).
        $recordid1 = idea_add_record($idea, 0);

        $ideacontent = array();
        $ideacontent['fieldid'] = $field->field->id;
        $ideacontent['recordid'] = $recordid1;
        $ideacontent['content'] = 'Obelix';
        $DB->insert_record('idea_content', $ideacontent);

        $recordid = idea_add_record($idea, $group1->id);

        $ideacontent = array();
        $ideacontent['fieldid'] = $field->field->id;
        $ideacontent['recordid'] = $recordid;
        $ideacontent['content'] = 'Asterix';
        $DB->insert_record('idea_content', $ideacontent);

        // Now try to access it as various users.
        unassign_capability('moodle/site:accessallgroups', $role->id);
        // Eveyone should have access to the record with the group id of zero.
        $params1 = array('contextid' => 2,
                        'component' => 'mod_idea',
                        'ratingarea' => 'entry',
                        'itemid' => $recordid1,
                        'scaleid' => 2);

        $params = array('contextid' => 2,
                        'component' => 'mod_idea',
                        'ratingarea' => 'entry',
                        'itemid' => $recordid,
                        'scaleid' => 2);

        $this->setUser($user1);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user2);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user3);
        $this->assertFalse(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user4);
        $this->assertFalse(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));

        // Now try with accessallgroups cap and make sure everything is visible.
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $role->id, $context->id);
        $this->setUser($user1);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user2);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user3);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user4);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));

        // Change group mode and verify visibility.
        $course->groupmode = VISIBLEGROUPS;
        $DB->update_record('course', $course);
        unassign_capability('moodle/site:accessallgroups', $role->id);
        $this->setUser($user1);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user2);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user3);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));
        $this->setUser($user4);
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params));
        $this->assertTrue(mod_idea_rating_can_see_item_ratings($params1));

    }

    /**
     * Tests for mod_idea_refresh_events.
     */
    public function test_idea_refresh_events() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $timeopen = time();
        $timeclose = time() + 86400;

        $course = $this->getideaGenerator()->create_course();
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');
        $params['course'] = $course->id;
        $params['timeavailablefrom'] = $timeopen;
        $params['timeavailableto'] = $timeclose;
        $idea = $generator->create_instance($params);

        // Normal case, with existing course.
        $this->assertTrue(idea_refresh_events($course->id));
        $eventparams = array('modulename' => 'idea', 'instance' => $idea->id, 'eventtype' => 'open');
        $openevent = $DB->get_record('event', $eventparams, '*', MUST_EXIST);
        $this->assertEquals($openevent->timestart, $timeopen);

        $eventparams = array('modulename' => 'idea', 'instance' => $idea->id, 'eventtype' => 'close');
        $closeevent = $DB->get_record('event', $eventparams, '*', MUST_EXIST);
        $this->assertEquals($closeevent->timestart, $timeclose);
        // In case the course ID is passed as a numeric string.
        $this->assertTrue(idea_refresh_events('' . $course->id));
        // Course ID not provided.
        $this->assertTrue(idea_refresh_events());
        $eventparams = array('modulename' => 'idea');
        $events = $DB->get_records('event', $eventparams);
        foreach ($events as $event) {
            if ($event->modulename === 'idea' && $event->instance === $idea->id && $event->eventtype === 'open') {
                $this->assertEquals($event->timestart, $timeopen);
            }
            if ($event->modulename === 'idea' && $event->instance === $idea->id && $event->eventtype === 'close') {
                $this->assertEquals($event->timestart, $timeclose);
            }
        }
    }

    /**
     * idea provider for tests of idea_get_config.
     *
     * @return array
     */
    public function idea_get_config_provider() {
        $initialidea = (object) [
            'template_foo' => true,
            'template_bar' => false,
            'template_baz' => null,
        ];

        $ideabase = (object) [
            'config' => json_encode($initialidea),
        ];

        return [
            'Return full ideaset (no key/default)' => [
                [$ideabase],
                $initialidea,
            ],
            'Return full ideaset (no default)' => [
                [$ideabase, null],
                $initialidea,
            ],
            'Return full ideaset' => [
                [$ideabase, null, null],
                $initialidea,
            ],
            'Return requested key only, value true, no default' => [
                [$ideabase, 'template_foo'],
                true,
            ],
            'Return requested key only, value false, no default' => [
                [$ideabase, 'template_bar'],
                false,
            ],
            'Return requested key only, value null, no default' => [
                [$ideabase, 'template_baz'],
                null,
            ],
            'Return unknown key, value null, no default' => [
                [$ideabase, 'template_bum'],
                null,
            ],
            'Return requested key only, value true, default null' => [
                [$ideabase, 'template_foo', null],
                true,
            ],
            'Return requested key only, value false, default null' => [
                [$ideabase, 'template_bar', null],
                false,
            ],
            'Return requested key only, value null, default null' => [
                [$ideabase, 'template_baz', null],
                null,
            ],
            'Return unknown key, value null, default null' => [
                [$ideabase, 'template_bum', null],
                null,
            ],
            'Return requested key only, value true, default 42' => [
                [$ideabase, 'template_foo', 42],
                true,
            ],
            'Return requested key only, value false, default 42' => [
                [$ideabase, 'template_bar', 42],
                false,
            ],
            'Return requested key only, value null, default 42' => [
                [$ideabase, 'template_baz', 42],
                null,
            ],
            'Return unknown key, value null, default 42' => [
                [$ideabase, 'template_bum', 42],
                42,
            ],
        ];
    }

    /**
     * Tests for idea_get_config.
     *
     * @ideaProvider    idea_get_config_provider
     * @param   array   $funcargs       The args to pass to idea_get_config
     * @param   mixed   $expectation    The expected value
     */
    public function test_idea_get_config($funcargs, $expectation) {
        $this->assertEquals($expectation, call_user_func_array('idea_get_config', $funcargs));
    }

    /**
     * idea provider for tests of idea_set_config.
     *
     * @return array
     */
    public function idea_set_config_provider() {
        $basevalue = (object) ['id' => rand(1, 1000)];
        $config = [
            'template_foo'  => true,
            'template_bar'  => false,
        ];

        $withvalues = clone $basevalue;
        $withvalues->config = json_encode((object) $config);

        return [
            'Empty config, New value' => [
                $basevalue,
                'etc',
                'newvalue',
                true,
                json_encode((object) ['etc' => 'newvalue'])
            ],
            'Has config, New value' => [
                clone $withvalues,
                'etc',
                'newvalue',
                true,
                json_encode((object) array_merge($config, ['etc' => 'newvalue']))
            ],
            'Has config, Update value, string' => [
                clone $withvalues,
                'template_foo',
                'newvalue',
                true,
                json_encode((object) array_merge($config, ['template_foo' => 'newvalue']))
            ],
            'Has config, Update value, true' => [
                clone $withvalues,
                'template_bar',
                true,
                true,
                json_encode((object) array_merge($config, ['template_bar' => true]))
            ],
            'Has config, Update value, false' => [
                clone $withvalues,
                'template_foo',
                false,
                true,
                json_encode((object) array_merge($config, ['template_foo' => false]))
            ],
            'Has config, Update value, null' => [
                clone $withvalues,
                'template_foo',
                null,
                true,
                json_encode((object) array_merge($config, ['template_foo' => null]))
            ],
            'Has config, No update, value true' => [
                clone $withvalues,
                'template_foo',
                true,
                false,
                $withvalues->config,
            ],
        ];
    }

    /**
     * Tests for idea_set_config.
     *
     * @ideaProvider    idea_set_config_provider
     * @param   object  $ideabase       The example row for the entry
     * @param   string  $key            The config key to set
     * @param   mixed   $value          The value of the key
     * @param   bool    $expectupdate   Whether we expected an update
     * @param   mixed   $newconfigvalue The expected value
     */
    public function test_idea_set_config($ideabase, $key, $value, $expectupdate, $newconfigvalue) {
        global $DB;

        // Mock the ideabase.
        // Note: Use the actual test class here rather than the abstract because are testing concrete methods.
        $this->DB = $DB;
        $DB = $this->getMockBuilder(get_class($DB))
            ->setMethods(['set_field'])
            ->getMock();

        $DB->expects($this->exactly((int) $expectupdate))
            ->method('set_field')
            ->with(
                'idea',
                'config',
                $newconfigvalue,
                ['id' => $ideabase->id]
            );

        // Perform the update.
        idea_set_config($ideabase, $key, $value);

        // Ensure that the value was updated by reference in $ideabase.
        $config = json_decode($ideabase->config);
        $this->assertEquals($value, $config->$key);
    }
}
