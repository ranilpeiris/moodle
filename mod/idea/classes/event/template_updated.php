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
 * The mod_idea template updated event.
 *
 * @package    mod_idea
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_idea\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_idea template updated event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int ideaid: the id of the idea activity.
 * }
 *
 * @package    mod_idea
 * @since      Moodle 2.7
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class template_updated extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtemplateupdated', 'mod_idea');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the template for the idea activity with course module " .
            "id '$this->contextinstanceid'.";
    }

    /**
     * Get the legacy event log idea.
     *
     * @return array
     */
    public function get_legacy_logidea() {
        return array($this->courseid, 'idea', 'templates saved', 'templates.php?id=' . $this->contextinstanceid .
            '&amp;d=' . $this->other['ideaid'], $this->other['ideaid'], $this->contextinstanceid);
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/idea/templates.php', array('d' => $this->other['ideaid']));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception when validation does not pass.
     * @return void
     */
    protected function validate_idea() {
        parent::validate_idea();

        if (!isset($this->other['ideaid'])) {
            throw new \coding_exception('The \'ideaid\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['ideaid'] = array('db' => 'idea', 'restore' => 'idea');

        return $othermapped;
    }
}
