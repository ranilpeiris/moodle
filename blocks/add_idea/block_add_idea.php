<?php
use core_competency\url;

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
 * Blog Add Idea page.
 *
 * @package    block_add_idea
 * @copyright  2009 Nicolas Connault
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The blog menu block class
 */
global $CFG;
class block_add_idea extends block_base {

   public function init() {
        $this->title = get_string('pluginname', 'block_add_idea');
        
    }

    public function get_content() {
    	if ($this->content !== null) {
    		return $this->content;
    	}
    	/**$urltoaddidea = $CFG->wwwroot;
    	$urltoaddidea=  html_writer::link('/edit_form.php', 'Create a an Idea');//  $urltoaddidea.'/blocks/add_idea/edit_form.php';
    	$this->content         =  new stdClass;
    	$this->content->text   = $urltoaddidea;
    	$this->content->footer = 'Footer here...';
    */ 
   
    	global $COURSE;
    	$url = new moodle_url('/blocks/add_idea/course_edit_form.php?category=1');
    	$this->content->footer = html_writer::link($url, get_string('addpage', 'block_add_idea'));
    }
}
