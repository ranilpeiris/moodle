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
 * Steps definitions related with the ideabase activity.
 *
 * @package    mod_idea
 * @category   test
 * @copyright  2014 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;
/**
 * ideabase-related steps definitions.
 *
 * @package    mod_idea
 * @category   test
 * @copyright  2014 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_idea extends behat_base {

    /**
     * Adds a new field to a ideabase
     *
     * @Given /^I add a "(?P<fieldtype_string>(?:[^"]|\\")*)" field to "(?P<activityname_string>(?:[^"]|\\")*)" ideabase and I fill the form with:$/
     *
     * @param string $fieldtype
     * @param string $activityname
     * @param TableNode $fieldidea
     */
    public function i_add_a_field_to_ideabase_and_i_fill_the_form_with($fieldtype, $activityname, TableNode $fieldidea) {

        $this->execute("behat_general::click_link", $this->escape($activityname));

        // Open "Fields" tab if it is not already open.
        $fieldsstr = get_string('fields', 'mod_idea');
        $xpath = '//ul[contains(@class,\'nav-tabs\')]//*[contains(@class,\'active\') and contains(normalize-space(.), \'' .
            $fieldsstr . '\')]';
        if (!$this->getSession()->getPage()->findAll('xpath', $xpath)) {
            $this->execute("behat_general::i_click_on_in_the", array($fieldsstr, 'link', '.nav-tabs', 'css_element'));
        }

        $this->execute('behat_forms::i_set_the_field_to', array('newtype', $this->escape($fieldtype)));

        if (!$this->running_javascript()) {
            $this->execute('behat_general::i_click_on_in_the',
                array(get_string('go'), "button", ".fieldadd", "css_element")
            );
        }

        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $fieldidea);
        $this->execute('behat_forms::press_button', get_string('add'));
    }

    /**
     * Adds an entry to a ideabase.
     *
     * @Given /^I add an entry to "(?P<activityname_string>(?:[^"]|\\")*)" ideabase with:$/
     *
     * @param string $activityname
     * @param TableNode $entryidea
     */
    public function i_add_an_entry_to_ideabase_with($activityname, TableNode $entryidea) {

        $this->execute("behat_general::click_link", $this->escape($activityname));
        $this->execute("behat_navigation::i_navigate_to_node_in", array(get_string('add', 'mod_idea'),
            get_string('pluginadministration', 'mod_idea')));

        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $entryidea);
    }
}
