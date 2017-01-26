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
 * PHPUnit idea generator tests.
 *
 * @package    mod_idea
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit idea generator testcase.
 *
 * @package    mod_idea
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_idea_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('idea'));

        $course = $this->getideaGenerator()->create_course();

        /** @var mod_idea_generator $generator */
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');
        $this->assertInstanceOf('mod_idea_generator', $generator);
        $this->assertEquals('idea', $generator->get_modulename());

        $generator->create_instance(array('course' => $course->id));
        $generator->create_instance(array('course' => $course->id));
        $idea = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(3, $DB->count_records('idea'));

        $cm = get_coursemodule_from_instance('idea', $idea->id);
        $this->assertEquals($idea->id, $cm->instance);
        $this->assertEquals('idea', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($idea->cmid, $context->instanceid);

        // test gradebook integration using low level DB access - DO NOT USE IN PLUGIN CODE!
        $idea = $generator->create_instance(array('course' => $course->id, 'assessed' => 1, 'scale' => 100));
        $gitem = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemtype' => 'mod',
                'itemmodule' => 'idea', 'iteminstance' => $idea->id));
        $this->assertNotEmpty($gitem);
        $this->assertEquals(100, $gitem->grademax);
        $this->assertEquals(0, $gitem->grademin);
        $this->assertEquals(GRADE_TYPE_VALUE, $gitem->gradetype);
    }

    public function test_create_field() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $this->assertEquals(0, $DB->count_records('idea'));

        $course = $this->getideaGenerator()->create_course();

        /** @var mod_idea_generator $generator */
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');
        $this->assertInstanceOf('mod_idea_generator', $generator);
        $this->assertEquals('idea', $generator->get_modulename());

        $idea = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('idea'));

        $cm = get_coursemodule_from_instance('idea', $idea->id);
        $this->assertEquals($idea->id, $cm->instance);
        $this->assertEquals('idea', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($idea->cmid, $context->instanceid);

        $fieldtypes = array('checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url');

        $count = 1;

        // Creating test Fields with default parameter values.
        foreach ($fieldtypes as $fieldtype) {

            // Creating variables dynamically.
            $fieldname = 'field-' . $count;
            $record = new StdClass();
            $record->name = $fieldname;
            $record->type = $fieldtype;

            ${$fieldname} = $this->getideaGenerator()->get_plugin_generator('mod_idea')->create_field($record, $idea);

            $this->assertInstanceOf('idea_field_' . $fieldtype, ${$fieldname});
            $count++;
        }

        $this->assertEquals(count($fieldtypes), $DB->count_records('idea_fields', array('ideaid' => $idea->id)));

        $addtemplate = $DB->get_record('idea', array('id' => $idea->id), 'addtemplate');
        $addtemplate = $addtemplate->addtemplate;

        for ($i = 1; $i < $count; $i++) {
            $fieldname = 'field-' . $i;
            $this->assertTrue(strpos($addtemplate, '[[' . $fieldname . ']]') >= 0);
        }
    }

    public function test_create_entry() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $this->assertEquals(0, $DB->count_records('idea'));

        $user1 = $this->getideaGenerator()->create_user();
        $course = $this->getideaGenerator()->create_course();
        $this->getideaGenerator()->enrol_user($user1->id, $course->id, 'student');

        $groupa = $this->getideaGenerator()->create_group(array('courseid' => $course->id, 'name' => 'groupA'));
        $this->getideaGenerator()->create_group_member(array('userid' => $user1->id, 'groupid' => $groupa->id));

        /** @var mod_idea_generator $generator */
        $generator = $this->getideaGenerator()->get_plugin_generator('mod_idea');
        $this->assertInstanceOf('mod_idea_generator', $generator);
        $this->assertEquals('idea', $generator->get_modulename());

        $idea = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('idea'));

        $cm = get_coursemodule_from_instance('idea', $idea->id);
        $this->assertEquals($idea->id, $cm->instance);
        $this->assertEquals('idea', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($idea->cmid, $context->instanceid);

        $fieldtypes = array('checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url');

        $count = 1;

        // Creating test Fields with default parameter values.
        foreach ($fieldtypes as $fieldtype) {

            // Creating variables dynamically.
            $fieldname = 'field-' . $count;
            $record = new StdClass();
            $record->name = $fieldname;
            $record->type = $fieldtype;
            $record->required = 1;

            ${$fieldname} = $this->getideaGenerator()->get_plugin_generator('mod_idea')->create_field($record, $idea);
            $this->assertInstanceOf('idea_field_' . $fieldtype, ${$fieldname});
            $count++;
        }

        $this->assertEquals(count($fieldtypes), $DB->count_records('idea_fields', array('ideaid' => $idea->id)));

        $fields = $DB->get_records('idea_fields', array('ideaid' => $idea->id), 'id');

        $contents = array();
        $contents[] = array('opt1', 'opt2', 'opt3', 'opt4');
        $contents[] = '01-01-2037'; // It should be lower than 2038, to avoid failing on 32-bit windows.
        $contents[] = 'menu1';
        $contents[] = array('multimenu1', 'multimenu2', 'multimenu3', 'multimenu4');
        $contents[] = '12345';
        $contents[] = 'radioopt1';
        $contents[] = 'text for testing';
        $contents[] = '<p>text area testing<br /></p>';
        $contents[] = array('example.url', 'sampleurl');
        $count = 0;
        $fieldcontents = array();
        foreach ($fields as $fieldrecord) {
            $fieldcontents[$fieldrecord->id] = $contents[$count++];
        }

        $idearecordid = $this->getideaGenerator()->get_plugin_generator('mod_idea')->create_entry($idea, $fieldcontents,
                                                                                                    $groupa->id);

        $this->assertEquals(1, $DB->count_records('idea_records', array('ideaid' => $idea->id)));
        $this->assertEquals(count($contents), $DB->count_records('idea_content', array('recordid' => $idearecordid)));

        $entry = $DB->get_record('idea_records', array('id' => $idearecordid));
        $this->assertEquals($entry->groupid, $groupa->id);

        $contents = $DB->get_records('idea_content', array('recordid' => $idearecordid), 'id');

        $contentstartid = 0;
        $flag = 0;
        foreach ($contents as $key => $content) {
            if (!$flag++) {
                $contentstartid = $key;
            }
            $this->assertFalse($content->content == null);
        }

        $this->assertEquals($contents[$contentstartid]->content, 'opt1##opt2##opt3##opt4');
        $this->assertEquals($contents[++$contentstartid]->content, '2114380800');
        $this->assertEquals($contents[++$contentstartid]->content, 'menu1');
        $this->assertEquals($contents[++$contentstartid]->content, 'multimenu1##multimenu2##multimenu3##multimenu4');
        $this->assertEquals($contents[++$contentstartid]->content, '12345');
        $this->assertEquals($contents[++$contentstartid]->content, 'radioopt1');
        $this->assertEquals($contents[++$contentstartid]->content, 'text for testing');
        $this->assertEquals($contents[++$contentstartid]->content, '<p>text area testing<br /></p>');
        $this->assertEquals($contents[$contentstartid]->content1, '1');
        $this->assertEquals($contents[++$contentstartid]->content, 'http://example.url');
        $this->assertEquals($contents[$contentstartid]->content1, 'sampleurl');
    }
}
