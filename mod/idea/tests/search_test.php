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
 * Unit tests for idea_get_all_recordsids(), idea_get_advance_search_ids(), idea_get_record_ids(),
 * and idea_get_advanced_search_sql()
 *
 * @package    mod_idea
 * @category   phpunit
 * @copyright  2012 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/idea/lib.php');
require_once($CFG->dirroot . '/lib/idealib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');
require_once($CFG->dirroot . '/mod/idea/tests/generator/lib.php');

/**
 * Unit tests for {@see idea_get_all_recordids()}.
 *                {@see idea_get_advanced_search_ids()}
 *                {@see idea_get_record_ids()}
 *                {@see idea_get_advanced_search_sql()}
 *
 * @package    mod_idea
 * @copyright  2012 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_idea_search_test extends advanced_testcase {
    /**
     * @var stdObject $recordidea An object that holds information from the table idea.
     */
    public $recordidea = null;
    /**
     * @var int $recordcontentid The content ID.
     */
    public $recordcontentid = null;
    /**
     * @var int $recordrecordid The record ID.
     */
    public $recordrecordid = null;
    /**
     * @var int $recordfieldid The field ID.
     */
    public $recordfieldid = null;
    /**
     * @var array $recordsearcharray An array of stdClass which contains search criteria.
     */
    public $recordsearcharray = null;

    // CONSTANTS

    /**
     * @var int $idearecordcount   The number of records in the ideabase.
     */
    public $idearecordcount = 100;

    /**
     * @var int $groupidearecordcount  The number of records in the ideabase in groups 0 and 1.
     */
    public $groupidearecordcount = 75;

    /**
     * @var array $idearecordset   Expected record IDs.
     */
    public $idearecordset = array('0' => '6');

    /**
     * @var array $finalrecord   Final record for comparison with test four.
     */
    public $finalrecord = array();

    /**
     * @var int $approveidearecordcount  The number of approved records in the ideabase.
     */
    public $approveidearecordcount = 89;

    /**
     * @var string Area id
     */
    protected $ideabaseentryareaid = null;

    /**
     * Set up function. In this instance we are setting up ideabase
     * records to be used in the unit tests.
     */
    protected function setUp() {
        global $DB, $CFG;
        parent::setUp();

        $this->resetAfterTest(true);

        set_config('enableglobalsearch', true);

        $this->ideabaseentryareaid = \core_search\manager::generate_areaid('mod_idea', 'entry');

        // Set \core_search::instance to the mock_search_engine as we don't require the search engine to be working to test this.
        $search = testable_core_search::instance();

    }

    /**
     * Test 1: The function idea_get_all_recordids.
     *
     * Test 2: This tests the idea_get_advance_search_ids() function. The function takes a set
     * of all the record IDs in the ideabase and then with the search details ($this->recordsearcharray)
     * returns a comma seperated string of record IDs that match the search criteria.
     *
     * Test 3: This function tests idea_get_recordids(). This is the function that is nested in the last
     * function (see idea_get_advance_search_ids). This function takes a couple of
     * extra parameters. $alias is the field alias used in the sql query and $commaid
     * is a comma seperated string of record IDs.
     *
     * Test 3.1: This tests that if no recordids are provided (In a situation where a search is done on an empty ideabase)
     * That an empty array is returned.
     *
     * Test 4: idea_get_advanced_search_sql provides an array which contains an sql string to be used for displaying records
     * to the user when they use the advanced search criteria and the parameters that go with the sql statement. This test
     * takes that information and does a search on the ideabase, returning a record.
     *
     * Test 5: Returning to idea_get_all_recordids(). Here we are ensuring that the total amount of record ids is reduced to
     * match the group conditions that are provided. There are 25 entries which relate to group 2. They are removed
     * from the total so we should only have 75 records total.
     *
     * Test 6: idea_get_all_recordids() again. This time we are testing approved ideabase records. We only want to
     * display the records that have been approved. In this record set we have 89 approved records.
     */
    public function test_advanced_search_sql_section() {
        global $DB;

        // we already have 2 users, we need 98 more - let's ignore the fact that guest can not post anywhere
        // We reset the user sequence here to ensure we get the expected numbers.
        // TODO: Invent a better way for managing idea file input against ideabase sequence id's.
        $DB->get_manager()->reset_sequence('user');
        for($i=3;$i<=100;$i++) {
            $this->getideaGenerator()->create_user();
        }

        // create ideabase module - there should be more of these I guess
        $course = $this->getideaGenerator()->create_course();
        $idea = $this->getideaGenerator()->create_module('idea', array('course'=>$course->id));
        $this->recordidea = $idea;

        // Set up idea for the test ideabase.
        $files = array(
                'idea_fields'  => __DIR__.'/fixtures/test_idea_fields.csv',
                'idea_records' => __DIR__.'/fixtures/test_idea_records.csv',
                'idea_content' => __DIR__.'/fixtures/test_idea_content.csv',
        );
        $this->loadideaSet($this->createCsvideaSet($files));
        // Set ideaid to the correct value now the idea has been inserted by csv file.
        $DB->execute('UPDATE {idea_fields} SET ideaid = ?', array($idea->id));
        $DB->execute('UPDATE {idea_records} SET ideaid = ?', array($idea->id));

        // Create the search array which contains our advanced search criteria.
        $fieldinfo = array('0' => new stdClass(),
                '1' => new stdClass(),
                '2' => new stdClass(),
                '3' => new stdClass(),
                '4' => new stdClass());
        $fieldinfo['0']->id = 1;
        $fieldinfo['0']->idea = '3.721,46.6126';
        $fieldinfo['1']->id = 2;
        $fieldinfo['1']->idea = 'Hahn Premium';
        $fieldinfo['2']->id = 5;
        $fieldinfo['2']->idea = 'Female';
        $fieldinfo['3']->id = 7;
        $fieldinfo['3']->idea = 'kel';
        $fieldinfo['4']->id = 9;
        $fieldinfo['4']->idea = 'VIC';

        foreach($fieldinfo as $field) {
            $searchfield = idea_get_field_from_id($field->id, $idea);
            if ($field->id == 2) {
                $searchfield->field->param1 = 'Hahn Premium';
                $val = array();
                $val['selected'] = array('0' => 'Hahn Premium');
                $val['allrequired'] = 0;
            } else {
                $val = $field->idea;
            }
            $search_array[$field->id] = new stdClass();
            list($search_array[$field->id]->sql, $search_array[$field->id]->params) = $searchfield->generate_sql('c' . $field->id, $val);
        }

        $this->recordsearcharray = $search_array;

        // Setting up the comparison stdClass for the last test.
        $user = $DB->get_record('user', array('id'=>6));
        $this->finalrecord[6] = new stdClass();
        $this->finalrecord[6]->id = 6;
        $this->finalrecord[6]->approved = 1;
        $this->finalrecord[6]->timecreated = 1234567891;
        $this->finalrecord[6]->timemodified = 1234567892;
        $this->finalrecord[6]->userid = 6;
        $this->finalrecord[6]->firstname = $user->firstname;
        $this->finalrecord[6]->lastname = $user->lastname;
        $this->finalrecord[6]->firstnamephonetic = $user->firstnamephonetic;
        $this->finalrecord[6]->lastnamephonetic = $user->lastnamephonetic;
        $this->finalrecord[6]->middlename = $user->middlename;
        $this->finalrecord[6]->alternatename = $user->alternatename;
        $this->finalrecord[6]->picture = $user->picture;
        $this->finalrecord[6]->imagealt = $user->imagealt;
        $this->finalrecord[6]->email = $user->email;

        // Test 1
        $recordids = idea_get_all_recordids($this->recordidea->id);
        $this->assertEquals(count($recordids), $this->idearecordcount);

        // Test 2
        $key = array_keys($this->recordsearcharray);
        $alias = $key[0];
        $newrecordids = idea_get_recordids($alias, $this->recordsearcharray, $this->recordidea->id, $recordids);
        $this->assertEquals($this->idearecordset, $newrecordids);

        // Test 3
        $newrecordids = idea_get_advance_search_ids($recordids, $this->recordsearcharray, $this->recordidea->id);
        $this->assertEquals($this->idearecordset, $newrecordids);

        // Test 3.1
        $resultrecordids = idea_get_advance_search_ids(array(), $this->recordsearcharray, $this->recordidea->id);
        $this->assertEmpty($resultrecordids);

        // Test 4
        $sortorder = 'ORDER BY r.timecreated ASC , r.id ASC';
        $html = idea_get_advanced_search_sql('0', $this->recordidea, $newrecordids, '', $sortorder);
        $allparams = array_merge($html['params'], array('ideaid' => $this->recordidea->id));
        $records = $DB->get_records_sql($html['sql'], $allparams);
        $this->assertEquals($records, $this->finalrecord);

        // Test 5
        $groupsql = " AND (r.groupid = :currentgroup OR r.groupid = 0)";
        $params = array('currentgroup' => 1);
        $recordids = idea_get_all_recordids($this->recordidea->id, $groupsql, $params);
        $this->assertEquals($this->groupidearecordcount, count($recordids));

        // Test 6
        $approvesql = ' AND r.approved=1 ';
        $recordids = idea_get_all_recordids($this->recordidea->id, $approvesql, $params);
        $this->assertEquals($this->approveidearecordcount, count($recordids));
    }

    /**
     * Indexing ideabase entries contents.
     *
     * @return void
     */
    public function test_idea_entries_indexing() {
        global $DB;

        // Returns the instance as long as the area is supported.
        $searcharea = \core_search\manager::get_search_area($this->ideabaseentryareaid);
        $this->assertInstanceOf('\mod_idea\search\entry', $searcharea);

        $user1 = self::getideaGenerator()->create_user();

        $course1 = self::getideaGenerator()->create_course();

        $this->getideaGenerator()->enrol_user($user1->id, $course1->id, 'student');

        $record = new stdClass();
        $record->course = $course1->id;

        $this->setUser($user1);

        // Available for both student and teacher.
        $idea1 = $this->getideaGenerator()->create_module('idea', $record);

        // Excluding LatLong and Picture as we aren't indexing LatLong and Picture fields any way
        // ...and they're complex and not of any use to consider for this test.
        // Excluding File as we are indexing files seperately and its complex to implement.
        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea1);

        $idea1record1id = $this->create_default_idea_record($idea1);
        // All records.
        $recordset = $searcharea->get_recordset_by_timestamp(0);

        $this->assertTrue($recordset->valid());

        $nrecords = 0;
        foreach ($recordset as $record) {
            $this->assertInstanceOf('stdClass', $record);
            $doc = $searcharea->get_document($record);
            $this->assertInstanceOf('\core_search\document', $doc);
            $nrecords++;
        }

        // If there would be an error/failure in the foreach above the recordset would be closed on shutdown.
        $recordset->close();
        $this->assertEquals(1, $nrecords);

        // The +2 is to prevent race conditions.
        $recordset = $searcharea->get_recordset_by_timestamp(time() + 2);

        // No new records.
        $this->assertFalse($recordset->valid());
        $recordset->close();
    }

    /**
     * Document contents.
     *
     * @return void
     */
    public function test_idea_entries_document() {
        global $DB;

        // Returns the instance as long as the area is supported.
        $searcharea = \core_search\manager::get_search_area($this->ideabaseentryareaid);
        $this->assertInstanceOf('\mod_idea\search\entry', $searcharea);

        $user1 = self::getideaGenerator()->create_user();

        $course = self::getideaGenerator()->create_course();

        $this->getideaGenerator()->enrol_user($user1->id, $course->id, 'student');

        $record = new stdClass();
        $record->course = $course->id;

        $this->setAdminUser();

        // First Case.
        $idea1 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea1);

        $idea1record1id = $this->create_default_idea_record($idea1);

        $idea1entry1 = $this->get_entry_for_id($idea1record1id);

        $idea1doc = $searcharea->get_document($idea1entry1);

        $this->assertEquals($idea1doc->get('courseid'), $course->id);
        $this->assertEquals($idea1doc->get('title'), 'text for testing');
        $this->assertEquals($idea1doc->get('content'), 'menu1');
        $this->assertEquals($idea1doc->get('description1'), 'radioopt1');
        $this->assertEquals($idea1doc->get('description2'), 'opt1 opt2 opt3 opt4 multimenu1 multimenu2 multimenu3 multimenu4 text area testing http://example.url');

        // Second Case.
        $idea2 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array(
                array('checkbox', 1),
                array('textarea', 0),
                array('menu', 0),
                array('number', 1),
                array('url', 0),
                array('text', 0)
        );

        $this->create_default_idea_fields($fieldtypes, $idea2);

        $idea2record1id = $this->create_default_idea_record($idea2);

        $idea2entry1 = $this->get_entry_for_id($idea2record1id);

        $idea2doc = $searcharea->get_document($idea2entry1);

        $this->assertEquals($idea2doc->get('courseid'), $course->id);
        $this->assertEquals($idea2doc->get('title'), 'opt1 opt2 opt3 opt4');
        $this->assertEquals($idea2doc->get('content'), 'text for testing');
        $this->assertEquals($idea2doc->get('description1'), 'menu1');
        $this->assertEquals($idea2doc->get('description2'), 'text area testing http://example.url');

        // Third Case.
        $idea3 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea3);

        $idea3record1id = $this->create_default_idea_record($idea3);

        $idea3entry1 = $this->get_entry_for_id($idea3record1id);

        $this->assertFalse($searcharea->get_document($idea3entry1));

        // Fourth Case.
        $idea4 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( array('date', 1), array('text', 1));

        $this->create_default_idea_fields($fieldtypes, $idea4);

        $idea4record1id = $this->create_default_idea_record($idea4);

        $idea4entry1 = $this->get_entry_for_id($idea4record1id);

        $this->assertFalse($searcharea->get_document($idea4entry1));

        // Fifth Case.
        $idea5 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array(
                array('checkbox', 0),
                array('number', 1),
                array('text', 0),
                array('date', 1),
                array('textarea', 0),
                array('url', 1));

        $this->create_default_idea_fields($fieldtypes, $idea5);

        $idea5record1id = $this->create_default_idea_record($idea5);

        $idea5entry1 = $this->get_entry_for_id($idea5record1id);

        $idea5doc = $searcharea->get_document($idea5entry1);

        $this->assertEquals($idea5doc->get('courseid'), $course->id);
        $this->assertEquals($idea5doc->get('title'), 'http://example.url');
        $this->assertEquals($idea5doc->get('content'), 'text for testing');
        $this->assertEquals($idea5doc->get('description1'), 'opt1 opt2 opt3 opt4');
        $this->assertEquals($idea5doc->get('description2'), 'text area testing');

        // Sixth Case.
        $idea6 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( array('date', 1), array('number', 1));

        $this->create_default_idea_fields($fieldtypes, $idea6);

        $idea6record1id = $this->create_default_idea_record($idea6);

        $idea6entry1 = $this->get_entry_for_id($idea6record1id);

        $idea6doc = $searcharea->get_document($idea6entry1);

        $this->assertFalse($idea6doc);

        // Seventh Case.
        $idea7 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( array('date', 1), array('number', 1),
                array('text', 0), array('textarea', 0));

        $this->create_default_idea_fields($fieldtypes, $idea7);

        $idea7record1id = $this->create_default_idea_record($idea7);

        $idea7entry1 = $this->get_entry_for_id($idea7record1id);

        $idea7doc = $searcharea->get_document($idea7entry1);

        $this->assertEquals($idea7doc->get('courseid'), $course->id);
        $this->assertEquals($idea7doc->get('title'), 'text for testing');
        $this->assertEquals($idea7doc->get('content'), 'text area testing');

        // Eight Case.
        $idea8 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array('url', 'url', 'url', 'text');

        $this->create_default_idea_fields($fieldtypes, $idea8);

        $idea8record1id = $this->create_default_idea_record($idea8);

        $idea8entry1 = $this->get_entry_for_id($idea8record1id);

        $idea8doc = $searcharea->get_document($idea8entry1);

        $this->assertEquals($idea8doc->get('courseid'), $course->id);
        $this->assertEquals($idea8doc->get('title'), 'text for testing');
        $this->assertEquals($idea8doc->get('content'), 'http://example.url');
        $this->assertEquals($idea8doc->get('description1'), 'http://example.url');
        $this->assertEquals($idea8doc->get('description2'), 'http://example.url');

        // Ninth Case.
        $idea9 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array('radiobutton', 'menu', 'multimenu');

        $this->create_default_idea_fields($fieldtypes, $idea9);

        $idea9record1id = $this->create_default_idea_record($idea9);

        $idea9entry1 = $this->get_entry_for_id($idea9record1id);

        $idea9doc = $searcharea->get_document($idea9entry1);

        $this->assertEquals($idea9doc->get('courseid'), $course->id);
        $this->assertEquals($idea9doc->get('title'), 'radioopt1');
        $this->assertEquals($idea9doc->get('content'), 'menu1');
        $this->assertEquals($idea9doc->get('description1'), 'multimenu1 multimenu2 multimenu3 multimenu4');

        // Tenth Case.
        $idea10 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array('checkbox', 'textarea', 'multimenu');

        $this->create_default_idea_fields($fieldtypes, $idea10);

        $idea10record1id = $this->create_default_idea_record($idea10);

        $idea10entry1 = $this->get_entry_for_id($idea10record1id);

        $idea10doc = $searcharea->get_document($idea10entry1);

        $this->assertEquals($idea10doc->get('courseid'), $course->id);
        $this->assertEquals($idea10doc->get('title'), 'opt1 opt2 opt3 opt4');
        $this->assertEquals($idea10doc->get('content'), 'text area testing');
        $this->assertEquals($idea10doc->get('description1'), 'multimenu1 multimenu2 multimenu3 multimenu4');

    }

    /**
     * Document accesses.
     *
     * @return void
     */
    public function test_idea_entries_access() {
        global $DB;

        // Returns the instance as long as the area is supported.
        $searcharea = \core_search\manager::get_search_area($this->ideabaseentryareaid);
        $this->assertInstanceOf('\mod_idea\search\entry', $searcharea);

        $user1 = self::getideaGenerator()->create_user();
        $user2 = self::getideaGenerator()->create_user();
        $user3 = self::getideaGenerator()->create_user();
        $userteacher1 = self::getideaGenerator()->create_user();

        $course1 = self::getideaGenerator()->create_course();
        $course2 = self::getideaGenerator()->create_course();

        $this->getideaGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getideaGenerator()->enrol_user($user2->id, $course1->id, 'student');
        $this->getideaGenerator()->enrol_user($userteacher1->id, $course1->id, 'teacher');

        $this->getideaGenerator()->enrol_user($user3->id, $course2->id, 'student');

        $record = new stdClass();
        $record->course = $course1->id;

        $this->setUser($userteacher1);

        $idea1 = $this->getideaGenerator()->create_module('idea', $record);

        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea1);

        $this->setUser($user1);
        $idea1record1id = $this->create_default_idea_record($idea1);

        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea1record1id));
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $searcharea->check_access(-1));

        $this->setUser($user2);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea1record1id));

        $this->setUser($user3);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea1record1id));

        $this->setUser($userteacher1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea1record1id));

        $this->setAdminUser();
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea1record1id));

        $this->setGuestUser();
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea1record1id));

        // Case with groups.
        $user1 = self::getideaGenerator()->create_user();
        $user2 = self::getideaGenerator()->create_user();
        $user3 = self::getideaGenerator()->create_user();
        $userteacher1 = self::getideaGenerator()->create_user();

        $course = self::getideaGenerator()->create_course(array('groupmode' => 1, 'groupmodeforce' => 1));

        $this->getideaGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getideaGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getideaGenerator()->enrol_user($userteacher1->id, $course->id, 'teacher');
        $this->getideaGenerator()->enrol_user($user3->id, $course->id, 'student');

        $groupa = $this->getideaGenerator()->create_group(array('courseid' => $course->id, 'name' => 'groupA'));
        $groupb = $this->getideaGenerator()->create_group(array('courseid' => $course->id, 'name' => 'groupB'));

        $this->getideaGenerator()->create_group_member(array('userid' => $user1->id, 'groupid' => $groupa->id));
        $this->getideaGenerator()->create_group_member(array('userid' => $user2->id, 'groupid' => $groupa->id));
        $this->getideaGenerator()->create_group_member(array('userid' => $userteacher1->id, 'groupid' => $groupa->id));

        $this->getideaGenerator()->create_group_member(array('userid' => $user3->id, 'groupid' => $groupb->id));

        $record = new stdClass();
        $record->course = $course->id;

        $this->setUser($userteacher1);

        $idea2 = $this->getideaGenerator()->create_module('idea', $record);

        $cm = get_coursemodule_from_instance('idea', $idea2->id, $course->id);
        $cm->groupmode = '1';
        $cm->effectivegroupmode = '1';

        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea2);

        $this->setUser($user1);

        $idea2record1id = $this->create_default_idea_record($idea2, $groupa->id);

        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea2record1id));
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $searcharea->check_access(-1));

        $this->setUser($user2);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea2record1id));

        $this->setUser($user3);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea2record1id));

        $this->setUser($userteacher1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea2record1id));

        $this->setAdminUser();
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea2record1id));

        $this->setGuestUser();
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea2record1id));

        // Case with approval.
        $user1 = self::getideaGenerator()->create_user();
        $user2 = self::getideaGenerator()->create_user();
        $userteacher1 = self::getideaGenerator()->create_user();

        $course = self::getideaGenerator()->create_course();

        $this->getideaGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getideaGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getideaGenerator()->enrol_user($userteacher1->id, $course->id, 'teacher');

        $record = new stdClass();
        $record->course = $course->id;

        $this->setUser($userteacher1);

        $idea3 = $this->getideaGenerator()->create_module('idea', $record);

        $DB->update_record('idea', array('id' => $idea3->id, 'approval' => 1));

        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea3);

        $this->setUser($user1);

        $idea3record1id = $this->create_default_idea_record($idea3);

        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $searcharea->check_access(-1));

        $this->setUser($user2);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea3record1id));

        $this->setUser($userteacher1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));

        $this->setAdminUser();
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));

        $this->setGuestUser();
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea3record1id));

        $DB->update_record('idea_records', array('id' => $idea3record1id, 'approved' => 1));

        $this->setUser($user1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $searcharea->check_access(-1));

        $this->setUser($user2);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));

        $this->setUser($userteacher1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));

        $this->setAdminUser();
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea3record1id));

        $this->setGuestUser();
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea3record1id));

        // Case with requiredentriestoview.
        $this->setAdminUser();

        $record->requiredentriestoview = 2;

        $idea4 = $this->getideaGenerator()->create_module('idea', $record);
        $fieldtypes = array( 'checkbox', 'date', 'menu', 'multimenu', 'number', 'radiobutton', 'text', 'textarea', 'url' );

        $this->create_default_idea_fields($fieldtypes, $idea4);

        $idea4record1id = $this->create_default_idea_record($idea4);

        $this->setUser($user1);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($idea4record1id));

        $idea4record2id = $this->create_default_idea_record($idea4);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea4record1id));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($idea4record2id));
    }

    /**
     * Test for file contents.
     *
     * @return void
     */
    public function test_attach_files() {
        global $DB, $USER;

        $fs = get_file_storage();

        // Returns the instance as long as the area is supported.
        $searcharea = \core_search\manager::get_search_area($this->ideabaseentryareaid);
        $this->assertInstanceOf('\mod_idea\search\entry', $searcharea);

        $user1 = self::getideaGenerator()->create_user();

        $course = self::getideaGenerator()->create_course();

        $this->getideaGenerator()->enrol_user($user1->id, $course->id, 'student');

        $record = new stdClass();
        $record->course = $course->id;

        $this->setAdminUser();

        // Creating ideabase activity instance.
        $idea1 = $this->getideaGenerator()->create_module('idea', $record);

        // Creating file field.
        $record = new stdClass;
        $record->type = 'file';
        $record->ideaid = $idea1->id;
        $record->required = 0;
        $record->name = 'FileFld';
        $record->description = 'Just another file field';
        $record->param3 = 0;
        $record->param1 = '';
        $record->param2 = '';

        $idea1filefieldid = $DB->insert_record('idea_fields', $record);

        // Creating text field.
        $record = new stdClass;
        $record->type = 'text';
        $record->ideaid = $idea1->id;
        $record->required = 0;
        $record->name = 'TextFld';
        $record->description = 'Just another text field';
        $record->param3 = 0;
        $record->param1 = '';
        $record->param2 = '';

        $idea1textfieldid = $DB->insert_record('idea_fields', $record);

        // Creating textarea field.
        $record = new stdClass;
        $record->type = 'textarea';
        $record->ideaid = $idea1->id;
        $record->required = 0;
        $record->name = 'TextAreaFld';
        $record->description = 'Just another textarea field';
        $record->param1 = '';
        $record->param2 = 60;
        $record->param3 = 35;
        $record->param3 = 1;
        $record->param3 = 0;

        $idea1textareafieldid = $DB->insert_record('idea_fields', $record);

        // Creating 1st entry.
        $record = new stdClass;
        $record->userid = $USER->id;
        $record->ideaid = $idea1->id;
        $record->groupid = 0;

        $idea1record1id = $DB->insert_record('idea_records', $record);

        $filerecord = array(
                'contextid' => context_module::instance($idea1->cmid)->id,
                'component' => 'mod_idea',
                'filearea'  => 'content',
                'itemid'    => $idea1record1id,
                'filepath'  => '/',
                'filename'  => 'myfile1.txt'
        );

        $idea1record1file = $fs->create_file_from_string($filerecord, 'Some contents 1');

        $record = new stdClass;
        $record->fieldid = $idea1filefieldid;
        $record->recordid = $idea1record1id;
        $record->content = 'myfile1.txt';
        $DB->insert_record('idea_content', $record);

        $record = new stdClass;
        $record->fieldid = $idea1textfieldid;
        $record->recordid = $idea1record1id;
        $record->content = 'sample text';
        $DB->insert_record('idea_content', $record);

        $record = new stdClass;
        $record->fieldid = $idea1textareafieldid;
        $record->recordid = $idea1record1id;
        $record->content = '<br>sample text<p /><br/>';
        $record->content1 = 1;
        $DB->insert_record('idea_content', $record);

        // Creating 2nd entry.
        $record = new stdClass;
        $record->userid = $USER->id;
        $record->ideaid = $idea1->id;
        $record->groupid = 0;
        $idea1record2id = $DB->insert_record('idea_records', $record);

        $filerecord['itemid'] = $idea1record2id;
        $filerecord['filename'] = 'myfile2.txt';
        $idea1record2file = $fs->create_file_from_string($filerecord, 'Some contents 2');

        $record = new stdClass;
        $record->fieldid = $idea1filefieldid;
        $record->recordid = $idea1record2id;
        $record->content = 'myfile2.txt';
        $DB->insert_record('idea_content', $record);

        $record = new stdClass;
        $record->fieldid = $idea1textfieldid;
        $record->recordid = $idea1record2id;
        $record->content = 'sample text';
        $DB->insert_record('idea_content', $record);

        $record = new stdClass;
        $record->fieldid = $idea1textareafieldid;
        $record->recordid = $idea1record2id;
        $record->content = '<br>sample text<p /><br/>';
        $record->content1 = 1;
        $DB->insert_record('idea_content', $record);

        // Now get all the posts and see if they have the right files attached.
        $searcharea = \core_search\manager::get_search_area($this->ideabaseentryareaid);
        $recordset = $searcharea->get_recordset_by_timestamp(0);
        $nrecords = 0;
        foreach ($recordset as $record) {
            $doc = $searcharea->get_document($record);
            $searcharea->attach_files($doc);
            $files = $doc->get_files();
            // Now check that each doc has the right files on it.
            switch ($doc->get('itemid')) {
                case ($idea1record1id):
                    $this->assertCount(1, $files);
                    $this->assertEquals($idea1record1file->get_id(), $files[$idea1record1file->get_id()]->get_id());
                    break;
                case ($idea1record2id):
                    $this->assertCount(1, $files);
                    $this->assertEquals($idea1record2file->get_id(), $files[$idea1record2file->get_id()]->get_id());
                    break;
                default:
                    $this->fail('Unexpected entry returned');
                    break;
            }
            $nrecords++;
        }
        $recordset->close();
        $this->assertEquals(2, $nrecords);
    }

    /**
     * Creates default fields for a ideabase instance
     *
     * @param array $fieldtypes
     * @param mod_idea $idea
     * @return void
     */
    protected function create_default_idea_fields($fieldtypes = array(), $idea) {
        $count = 1;

        // Creating test Fields with default parameter values.
        foreach ($fieldtypes as $fieldtype) {

            // Creating variables dynamically.
            $fieldname = 'field-'.$count;
            $record = new stdClass();
            $record->name = $fieldname;

            if (is_array($fieldtype)) {
                $record->type = $fieldtype[0];
                $record->required = $fieldtype[1];
            } else {
                $record->type = $fieldtype;
                $record->required = 0;
            }

            ${$fieldname} = $this->getideaGenerator()->get_plugin_generator('mod_idea')->create_field($record, $idea);
            $count++;
        }
    }

    /**
     * Creates default ideabase entry content values for default field param values
     *
     * @param mod_idea $idea
     * @param int $groupid
     * @return int
     */
    protected function create_default_idea_record($idea, $groupid = 0) {
        global $DB;

        $fields = $DB->get_records('idea_fields', array('ideaid' => $idea->id));

        $fieldcontents = array();
        foreach ($fields as $fieldrecord) {
            switch ($fieldrecord->type) {
                case 'checkbox':
                    $fieldcontents[$fieldrecord->id] = array('opt1', 'opt2', 'opt3', 'opt4');
                    break;

                case 'multimenu':
                    $fieldcontents[$fieldrecord->id] = array('multimenu1', 'multimenu2', 'multimenu3', 'multimenu4');
                    break;

                case 'date':
                    $fieldcontents[$fieldrecord->id] = '27-07-2016';
                    break;

                case 'menu':
                    $fieldcontents[$fieldrecord->id] = 'menu1';
                    break;

                case 'radiobutton':
                    $fieldcontents[$fieldrecord->id] = 'radioopt1';
                    break;

                case 'number':
                    $fieldcontents[$fieldrecord->id] = '12345';
                    break;

                case 'text':
                    $fieldcontents[$fieldrecord->id] = 'text for testing';
                    break;

                case 'textarea':
                    $fieldcontents[$fieldrecord->id] = '<p>text area testing<br /></p>';
                    break;

                case 'url':
                    $fieldcontents[$fieldrecord->id] = array('example.url', 'sampleurl');
                    break;

                default:
                    $this->fail('Unexpected field type');
                    break;
            }

        }

        return $this->getideaGenerator()->get_plugin_generator('mod_idea')->create_entry($idea, $fieldcontents, $groupid);
    }

    /**
     * Creates default ideabase entry content values for default field param values
     *
     * @param int $recordid
     * @return stdClass
     */
    protected function get_entry_for_id($recordid ) {
        global $DB;

        $sql = "SELECT dr.*, d.course
                  FROM {idea_records} dr
                  JOIN {idea} d ON d.id = dr.ideaid
                 WHERE dr.id = :drid";
        return $DB->get_record_sql($sql, array('drid' => $recordid));
    }

}
