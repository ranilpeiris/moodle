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
 * ideabase external functions and service definitions.
 *
 * @package    mod_idea
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */

$functions = array(

    'mod_idea_get_databases_by_courses' => array(
        'classname' => 'mod_idea_external',
        'methodname' => 'get_databases_by_courses',
        'description' => 'Returns a list of ideabase instances in a provided set of courses, if
            no courses are provided then all the ideabase instances the user has access to will be returned.',
        'type' => 'read',
        'capabilities' => 'mod/idea:viewentry',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    )
);
