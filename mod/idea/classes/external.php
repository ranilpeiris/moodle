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
 * ideabase module external API
 *
 * @package    mod_idea
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * ideabase module external functions
 *
 * @package    mod_idea
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */
class mod_idea_external extends external_api {

    /**
     * Describes the parameters for get_databases_by_courses.
     *
     * @return external_external_function_parameters
     * @since Moodle 2.9
     */
    public static function get_databases_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                    'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of ideabases in a provided list of courses,
     * if no list is provided all ideabases that the user can view will be returned.
     *
     * @param array $courseids the course ids
     * @return array the ideabase details
     * @since Moodle 2.9
     */
    public static function get_databases_by_courses($courseids = array()) {
        global $CFG;

        $params = self::validate_parameters(self::get_databases_by_courses_parameters(), array('courseids' => $courseids));
        $warnings = array();

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Array to store the ideabases to return.
        $arrideabases = array();

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($dbcourses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the ideabases in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $ideabases = get_all_instances_in_courses("idea", $dbcourses);

            foreach ($ideabases as $ideabase) {

                $ideacontext = context_module::instance($ideabase->coursemodule);

                // Entry to return.
                $newdb = array();

                // First, we return information that any user can see in the web interface.
                $newdb['id'] = $ideabase->id;
                $newdb['coursemodule'] = $ideabase->coursemodule;
                $newdb['course'] = $ideabase->course;
                $newdb['name']  = external_format_string($ideabase->name, $ideacontext->id);
                // Format intro.
                list($newdb['intro'], $newdb['introformat']) =
                    external_format_text($ideabase->intro, $ideabase->introformat,
                                            $ideacontext->id, 'mod_idea', 'intro', null);
                $newdb['introfiles'] = external_util::get_area_files($ideacontext->id, 'mod_idea', 'intro', false, false);

                // This information should be only available if the user can see the ideabase entries.
                if (has_capability('mod/idea:viewentry', $ideacontext)) {
                    $viewablefields = array('comments', 'timeavailablefrom', 'timeavailableto', 'timeviewfrom',
                                            'timeviewto', 'requiredentries', 'requiredentriestoview');

                    // This is for avoid a long repetitive list and for
                    // checking that we are retrieving all the required fields.
                    foreach ($viewablefields as $field) {
                        // We do not use isset because it won't work for existing null values.
                        if (!property_exists($ideabase, $field)) {
                            throw new invalid_response_exception('Missing ideabase module required field: ' . $field);
                        }
                        $newdb[$field] = $ideabase->{$field};
                    }
                }

                // Check additional permissions for returning optional private settings.
                // I avoid intentionally to use can_[add|update]_moduleinfo.
                if (has_capability('moodle/course:manageactivities', $ideacontext)) {

                    $additionalfields = array('maxentries', 'rssarticles', 'singletemplate', 'listtemplate',
                        'listtemplateheader', 'listtemplatefooter', 'addtemplate', 'rsstemplate', 'rsstitletemplate',
                        'csstemplate', 'jstemplate', 'asearchtemplate', 'approval', 'manageapproved', 'scale', 'assessed', 'assesstimestart',
                        'assesstimefinish', 'defaultsort', 'defaultsortdir', 'editany', 'notification', 'timemodified');

                    // This is for avoid a long repetitive list.
                    foreach ($additionalfields as $field) {
                        if (property_exists($ideabase, $field)) {
                            $newdb[$field] = $ideabase->{$field};
                        }
                    }
                }

                $arrideabases[] = $newdb;
            }
        }

        $result = array();
        $result['ideabases'] = $arrideabases;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_databases_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 2.9
     */
    public static function get_databases_by_courses_returns() {

        return new external_single_structure(
            array(
                'ideabases' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'ideabase id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'ideabase name'),
                            'intro' => new external_value(PARAM_RAW, 'The ideabase intro'),
                            'introformat' => new external_format_value('intro'),
                            'introfiles' => new external_files('Files in the introduction text', VALUE_OPTIONAL),
                            'comments' => new external_value(PARAM_BOOL, 'comments enabled', VALUE_OPTIONAL),
                            'timeavailablefrom' => new external_value(PARAM_INT, 'timeavailablefrom field', VALUE_OPTIONAL),
                            'timeavailableto' => new external_value(PARAM_INT, 'timeavailableto field', VALUE_OPTIONAL),
                            'timeviewfrom' => new external_value(PARAM_INT, 'timeviewfrom field', VALUE_OPTIONAL),
                            'timeviewto' => new external_value(PARAM_INT, 'timeviewto field', VALUE_OPTIONAL),
                            'requiredentries' => new external_value(PARAM_INT, 'requiredentries field', VALUE_OPTIONAL),
                            'requiredentriestoview' => new external_value(PARAM_INT, 'requiredentriestoview field', VALUE_OPTIONAL),
                            'maxentries' => new external_value(PARAM_INT, 'maxentries field', VALUE_OPTIONAL),
                            'rssarticles' => new external_value(PARAM_INT, 'rssarticles field', VALUE_OPTIONAL),
                            'singletemplate' => new external_value(PARAM_RAW, 'singletemplate field', VALUE_OPTIONAL),
                            'listtemplate' => new external_value(PARAM_RAW, 'listtemplate field', VALUE_OPTIONAL),
                            'listtemplateheader' => new external_value(PARAM_RAW, 'listtemplateheader field', VALUE_OPTIONAL),
                            'listtemplatefooter' => new external_value(PARAM_RAW, 'listtemplatefooter field', VALUE_OPTIONAL),
                            'addtemplate' => new external_value(PARAM_RAW, 'addtemplate field', VALUE_OPTIONAL),
                            'rsstemplate' => new external_value(PARAM_RAW, 'rsstemplate field', VALUE_OPTIONAL),
                            'rsstitletemplate' => new external_value(PARAM_RAW, 'rsstitletemplate field', VALUE_OPTIONAL),
                            'csstemplate' => new external_value(PARAM_RAW, 'csstemplate field', VALUE_OPTIONAL),
                            'jstemplate' => new external_value(PARAM_RAW, 'jstemplate field', VALUE_OPTIONAL),
                            'asearchtemplate' => new external_value(PARAM_RAW, 'asearchtemplate field', VALUE_OPTIONAL),
                            'approval' => new external_value(PARAM_BOOL, 'approval field', VALUE_OPTIONAL),
                            'manageapproved' => new external_value(PARAM_BOOL, 'manageapproved field', VALUE_OPTIONAL),
                            'scale' => new external_value(PARAM_INT, 'scale field', VALUE_OPTIONAL),
                            'assessed' => new external_value(PARAM_INT, 'assessed field', VALUE_OPTIONAL),
                            'assesstimestart' => new external_value(PARAM_INT, 'assesstimestart field', VALUE_OPTIONAL),
                            'assesstimefinish' => new external_value(PARAM_INT, 'assesstimefinish field', VALUE_OPTIONAL),
                            'defaultsort' => new external_value(PARAM_INT, 'defaultsort field', VALUE_OPTIONAL),
                            'defaultsortdir' => new external_value(PARAM_INT, 'defaultsortdir field', VALUE_OPTIONAL),
                            'editany' => new external_value(PARAM_BOOL, 'editany field', VALUE_OPTIONAL),
                            'notification' => new external_value(PARAM_INT, 'notification field', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT, 'Time modified', VALUE_OPTIONAL)
                        ), 'ideabase'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

}
