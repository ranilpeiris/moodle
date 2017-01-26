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
 * ideabase module external functions tests
 *
 * @package    mod_idea
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * ideabase module external functions tests
 *
 * @package    mod_idea
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */
class mod_idea_external_testcase extends externallib_advanced_testcase {

    /**
     * Test get ideabases by courses
     */
    public function test_mod_idea_get_databases_by_courses() {
        global $DB;

        $this->resetAfterTest(true);

        // Create users.
        $student = self::getideaGenerator()->create_user();
        $teacher = self::getideaGenerator()->create_user();

        // Set to the student user.
        self::setUser($student);

        // Create courses to add the modules.
        $course1 = self::getideaGenerator()->create_course();
        $course2 = self::getideaGenerator()->create_course();

        // First ideabase.
        $record = new stdClass();
        $record->introformat = FORMAT_HTML;
        $record->course = $course1->id;
        $ideabase1 = self::getideaGenerator()->create_module('idea', $record);

        // Second ideabase.
        $record = new stdClass();
        $record->introformat = FORMAT_HTML;
        $record->course = $course2->id;
        $ideabase2 = self::getideaGenerator()->create_module('idea', $record);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        // Users enrolments.
        $this->getideaGenerator()->enrol_user($student->id, $course1->id, $studentrole->id, 'manual');
        $this->getideaGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id, 'manual');

        // Execute real Moodle enrolment as we'll call unenrol() method on the instance later.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course2->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance2 = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance2, $student->id, $studentrole->id);

        // Create what we expect to be returned when querying the two courses.
        // First for the student user.
        $expectedfields = array('id', 'coursemodule', 'course', 'name', 'comments', 'timeavailablefrom',
                            'timeavailableto', 'timeviewfrom', 'timeviewto', 'requiredentries', 'requiredentriestoview',
                            'intro', 'introformat', 'introfiles');

        // Add expected coursemodule.
        $ideabase1->coursemodule = $ideabase1->cmid;
        $ideabase1->introfiles = [];
        $ideabase2->coursemodule = $ideabase2->cmid;
        $ideabase2->introfiles = [];

        $expected1 = array();
        $expected2 = array();
        foreach ($expectedfields as $field) {
            $expected1[$field] = $ideabase1->{$field};
            $expected2[$field] = $ideabase2->{$field};
        }
        $expected1['comments'] = (bool) $expected1['comments'];
        $expected2['comments'] = (bool) $expected2['comments'];

        $expectedideabases = array();
        $expectedideabases[] = $expected2;
        $expectedideabases[] = $expected1;

        // Call the external function passing course ids.
        $result = mod_idea_external::get_databases_by_courses(array($course2->id, $course1->id));
        $result = external_api::clean_returnvalue(mod_idea_external::get_databases_by_courses_returns(), $result);
        $this->assertEquals($expectedideabases, $result['ideabases']);

        // Call the external function without passing course id.
        $result = mod_idea_external::get_databases_by_courses();
        $result = external_api::clean_returnvalue(mod_idea_external::get_databases_by_courses_returns(), $result);
        $this->assertEquals($expectedideabases, $result['ideabases']);

        // Unenrol user from second course and alter expected ideabases.
        $enrol->unenrol_user($instance2, $student->id);
        array_shift($expectedideabases);

        // Call the external function without passing course id.
        $result = mod_idea_external::get_databases_by_courses();
        $result = external_api::clean_returnvalue(mod_idea_external::get_databases_by_courses_returns(), $result);
        $this->assertEquals($expectedideabases, $result['ideabases']);

        // Call for the second course we unenrolled the user from, expected warning.
        $result = mod_idea_external::get_databases_by_courses(array($course2->id));
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('1', $result['warnings'][0]['warningcode']);
        $this->assertEquals($course2->id, $result['warnings'][0]['itemid']);

        // Now, try as a teacher for getting all the additional fields.
        self::setUser($teacher);

        $additionalfields = array('maxentries', 'rssarticles', 'singletemplate', 'listtemplate', 'timemodified',
                                'listtemplateheader', 'listtemplatefooter', 'addtemplate', 'rsstemplate', 'rsstitletemplate',
                                'csstemplate', 'jstemplate', 'asearchtemplate', 'approval', 'scale', 'assessed', 'assesstimestart',
                                'assesstimefinish', 'defaultsort', 'defaultsortdir', 'editany', 'notification', 'manageapproved');

        foreach ($additionalfields as $field) {
            if ($field == 'approval' or $field == 'editany') {
                $expectedideabases[0][$field] = (bool) $ideabase1->{$field};
            } else {
                $expectedideabases[0][$field] = $ideabase1->{$field};
            }
        }
        $result = mod_idea_external::get_databases_by_courses();
        $result = external_api::clean_returnvalue(mod_idea_external::get_databases_by_courses_returns(), $result);
        $this->assertEquals($expectedideabases, $result['ideabases']);

        // Admin should get all the information.
        self::setAdminUser();

        $result = mod_idea_external::get_databases_by_courses(array($course1->id));
        $result = external_api::clean_returnvalue(mod_idea_external::get_databases_by_courses_returns(), $result);
        $this->assertEquals($expectedideabases, $result['ideabases']);
    }
}
