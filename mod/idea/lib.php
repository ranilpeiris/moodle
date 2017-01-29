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
 * @package   mod_idea
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->libdir/coursecatlib.php");
defined('MOODLE_INTERNAL') || die();

//TODO Some constants
define ('idea_MAX_ENTRIES', 50);
define ('idea_PERPAGE_SINGLE', 1);

define ('idea_FIRSTNAME', -1);
define ('idea_LASTNAME', -2);
define ('idea_APPROVED', -3);
define ('idea_TIMEADDED', 0);
define ('idea_TIMEMODIFIED', -4);

define ('idea_CAP_EXPORT', 'mod/idea:viewalluserpresets');

define('idea_PRESET_COMPONENT', 'mod_idea');
define('idea_PRESET_FILEAREA', 'site_presets');
define('idea_PRESET_CONTEXT', SYSCONTEXTID);

// Users having assigned the default role "Non-editing teacher" can export ideabase records
// Using the mod/idea capability "viewalluserpresets" existing in Moodle 1.9.x.
// In Moodle >= 2, new roles may be introduced and used instead.

/**
 * @package   mod_idea
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class idea_field_base {     // Base class for ideabase Field Types (see field/*/field.class.php)

    /** @var string Subclasses must override the type with their name */
    var $type = 'unknown';
    /** @var object The ideabase object that this field belongs to */
    var $idea = NULL;
    /** @var object The field object itself, if we know it */
    var $field = NULL;
    /** @var int Width of the icon for this fieldtype */
    var $iconwidth = 16;
    /** @var int Width of the icon for this fieldtype */
    var $iconheight = 16;
    /** @var object course module or cmifno */
    var $cm;
    /** @var object activity context */
    var $context;
    /** @var priority for globalsearch indexing */
    protected static $priority = self::NO_PRIORITY;
    /** priority value for invalid fields regarding indexing */
    const NO_PRIORITY = 0;
    /** priority value for minimum priority */
    const MIN_PRIORITY = 1;
    /** priority value for low priority */
    const LOW_PRIORITY = 2;
    /** priority value for high priority */
    const HIGH_PRIORITY = 3;
    /** priority value for maximum priority */
    const MAX_PRIORITY = 4;

    /**
     * Constructor function
     *
     * @global object
     * @uses CONTEXT_MODULE
     * @param int $field
     * @param int $idea
     * @param int $cm
     */
    function __construct($field=0, $idea=0, $cm=0) {   // Field or idea or both, each can be id or object
        global $DB;

        if (empty($field) && empty($idea)) {
            print_error('missingfield', 'idea');
        }

        if (!empty($field)) {
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope
            } else if (!$this->field = $DB->get_record('idea_fields', array('id'=>$field))) {
                print_error('invalidfieldid', 'idea');
            }
            if (empty($idea)) {
                if (!$this->idea = $DB->get_record('idea', array('id'=>$this->field->ideaid))) {
                    print_error('invalidid', 'idea');
                }
            }
        }

        if (empty($this->idea)) {         // We need to define this properly
            if (!empty($idea)) {
                if (is_object($idea)) {
                    $this->idea = $idea;  // Programmer knows what they are doing, we hope
                } else if (!$this->idea = $DB->get_record('idea', array('id'=>$idea))) {
                    print_error('invalidid', 'idea');
                }
            } else {                      // No way to define it!
                print_error('missingidea', 'idea');
            }
        }

        if ($cm) {
            $this->cm = $cm;
        } else {
            $this->cm = get_coursemodule_from_instance('idea', $this->idea->id);
        }

        if (empty($this->field)) {         // We need to define some default values
            $this->define_default_field();
        }

        $this->context = context_module::instance($this->cm->id);
    }


    /**
     * This field just sets up a default field object
     *
     * @return bool
     */
    function define_default_field() {
        global $OUTPUT;
        if (empty($this->idea->id)) {
            echo $OUTPUT->notification('Programmer error: ideaid not defined in field class');
        }
        $this->field = new stdClass();
        $this->field->id = 0;
        $this->field->ideaid = $this->idea->id;
        $this->field->type   = $this->type;
        $this->field->param1 = '';
        $this->field->param2 = '';
        $this->field->param3 = '';
        $this->field->name = '';
        $this->field->description = '';
        $this->field->required = false;

        return true;
    }

    /**
     * Set up the field object according to idea in an object.  Now is the time to clean it!
     *
     * @return bool
     */
    function define_field($idea) {
        $this->field->type        = $this->type;
        $this->field->ideaid      = $this->idea->id;

        $this->field->name        = trim($idea->name);
        $this->field->description = trim($idea->description);
        $this->field->required    = !empty($idea->required) ? 1 : 0;

        if (isset($idea->param1)) {
            $this->field->param1 = trim($idea->param1);
        }
        if (isset($idea->param2)) {
            $this->field->param2 = trim($idea->param2);
        }
        if (isset($idea->param3)) {
            $this->field->param3 = trim($idea->param3);
        }
        if (isset($idea->param4)) {
            $this->field->param4 = trim($idea->param4);
        }
        if (isset($idea->param5)) {
            $this->field->param5 = trim($idea->param5);
        }

        return true;
    }

    /**
     * Insert a new field in the ideabase
     * We assume the field object is already defined as $this->field
     *
     * @global object
     * @return bool
     */
    function insert_field() {
        global $DB, $OUTPUT;

        if (empty($this->field)) {
            echo $OUTPUT->notification('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
        }

        $this->field->id = $DB->insert_record('idea_fields',$this->field);

        // Trigger an event for creating this field.
        $event = \mod_idea\event\field_created::create(array(
            'objectid' => $this->field->id,
            'context' => $this->context,
            'other' => array(
                'fieldname' => $this->field->name,
                'ideaid' => $this->idea->id
            )
        ));
        $event->trigger();

        return true;
    }


    /**
     * Update a field in the ideabase
     *
     * @global object
     * @return bool
     */
    function update_field() {
        global $DB;

        $DB->update_record('idea_fields', $this->field);

        // Trigger an event for updating this field.
        $event = \mod_idea\event\field_updated::create(array(
            'objectid' => $this->field->id,
            'context' => $this->context,
            'other' => array(
                'fieldname' => $this->field->name,
                'ideaid' => $this->idea->id
            )
        ));
        $event->trigger();

        return true;
    }

    /**
     * Delete a field completely
     *
     * @global object
     * @return bool
     */
    function delete_field() {
        global $DB;

        if (!empty($this->field->id)) {
            // Get the field before we delete it.
            $field = $DB->get_record('idea_fields', array('id' => $this->field->id));

            $this->delete_content();
            $DB->delete_records('idea_fields', array('id'=>$this->field->id));

            // Trigger an event for deleting this field.
            $event = \mod_idea\event\field_deleted::create(array(
                'objectid' => $this->field->id,
                'context' => $this->context,
                'other' => array(
                    'fieldname' => $this->field->name,
                    'ideaid' => $this->idea->id
                 )
            ));
            $event->add_record_snapshot('idea_fields', $field);
            $event->trigger();
        }

        return true;
    }

    /**
     * Print the relevant form element in the ADD template for this field
     *
     * @global object
     * @param int $recordid
     * @return string
     */
    function display_add_field($recordid=0, $formidea=null) {
        global $DB, $OUTPUT;

        if ($formidea) {
            $fieldname = 'field_' . $this->field->id;
            $content = $formidea->$fieldname;
        } else if ($recordid) {
            $content = $DB->get_field('idea_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content===false) {
            $content='';
        }

        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<label for="field_'.$this->field->id.'"><span class="accesshide">'.$this->field->name.'</span>';
        if ($this->field->required) {
            $image = html_writer::img($OUTPUT->pix_url('req'), get_string('requiredelement', 'form'),
                                     array('class' => 'req', 'title' => get_string('requiredelement', 'form')));
            $str .= html_writer::div($image, 'inline-req');
        }
        $str .= '</label><input class="basefieldinput form-control d-inline mod-idea-input" ' .
                'type="text" name="field_' . $this->field->id . '" ' .
                'id="field_' . $this->field->id . '" value="' . s($content) . '" />';
        $str .= '</div>';

        return $str;
    }

    /**
     * Print the relevant form element to define the attributes for this field
     * viewable by teachers only.
     *
     * @global object
     * @global object
     * @return void Output is echo'd
     */
    function display_edit_field() {
        global $CFG, $DB, $OUTPUT;

        if (empty($this->field)) {   // No field has been defined yet, try and make one
            $this->define_default_field();
        }
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

        echo '<form id="editfield" action="'.$CFG->wwwroot.'/mod/idea/field.php" method="post">'."\n";
        echo '<input type="hidden" name="d" value="'.$this->idea->id.'" />'."\n";
        if (empty($this->field->id)) {
            echo '<input type="hidden" name="mode" value="add" />'."\n";
            $savebutton = get_string('add');
        } else {
            echo '<input type="hidden" name="fid" value="'.$this->field->id.'" />'."\n";
            echo '<input type="hidden" name="mode" value="update" />'."\n";
            $savebutton = get_string('savechanges');
        }
        echo '<input type="hidden" name="type" value="'.$this->type.'" />'."\n";
        echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />'."\n";

        echo $OUTPUT->heading($this->name(), 3);

        require_once($CFG->dirroot.'/mod/idea/field/'.$this->type.'/mod.html');

        echo '<div class="mdl-align">';
        echo '<input type="submit" class="btn btn-primary" value="'.$savebutton.'" />'."\n";
        echo '<input type="submit" class="btn btn-secondary" name="cancel" value="'.get_string('cancel').'" />'."\n";
        echo '</div>';

        echo '</form>';

        echo $OUTPUT->box_end();
    }

    /**
     * Display the content of the field in browse mode
     *
     * @global object
     * @param int $recordid
     * @param object $template
     * @return bool|string
     */
    function display_browse_field($recordid, $template) {
        global $DB;

        if ($content = $DB->get_record('idea_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            if (isset($content->content)) {
                $options = new stdClass();
                if ($this->field->param1 == '1') {  // We are autolinking this field, so disable linking within us
                    //$content->content = '<span class="nolink">'.$content->content.'</span>';
                    //$content->content1 = FORMAT_HTML;
                    $options->filter=false;
                }
                $options->para = false;
                $str = format_text($content->content, $content->content1, $options);
            } else {
                $str = '';
            }
            return $str;
        }
        return false;
    }

    /**
     * Update the content of one idea field in the idea_content table
     * @global object
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    function update_content($recordid, $value, $name=''){
        global $DB;

        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        if ($oldcontent = $DB->get_record('idea_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            $content->id = $oldcontent->id;
            return $DB->update_record('idea_content', $content);
        } else {
            return $DB->insert_record('idea_content', $content);
        }
    }

    /**
     * Delete all content associated with the field
     *
     * @global object
     * @param int $recordid
     * @return bool
     */
    function delete_content($recordid=0) {
        global $DB;

        if ($recordid) {
            $conditions = array('fieldid'=>$this->field->id, 'recordid'=>$recordid);
        } else {
            $conditions = array('fieldid'=>$this->field->id);
        }

        $rs = $DB->get_recordset('idea_content', $conditions);
        if ($rs->valid()) {
            $fs = get_file_storage();
            foreach ($rs as $content) {
                $fs->delete_area_files($this->context->id, 'mod_idea', 'content', $content->id);
            }
        }
        $rs->close();

        return $DB->delete_records('idea_content', $conditions);
    }

    /**
     * Check if a field from an add form is empty
     *
     * @param mixed $value
     * @param mixed $name
     * @return bool
     */
    function notemptyfield($value, $name) {
        return !empty($value);
    }

    /**
     * Just in case a field needs to print something before the whole form
     */
    function print_before_form() {
    }

    /**
     * Just in case a field needs to print something after the whole form
     */
    function print_after_form() {
    }


    /**
     * Returns the sortable field for the content. By default, it's just content
     * but for some plugins, it could be content 1 - content4
     *
     * @return string
     */
    function get_sort_field() {
        return 'content';
    }

    /**
     * Returns the SQL needed to refer to the column.  Some fields may need to CAST() etc.
     *
     * @param string $fieldname
     * @return string $fieldname
     */
    function get_sort_sql($fieldname) {
        return $fieldname;
    }

    /**
     * Returns the name/type of the field
     *
     * @return string
     */
    function name() {
        return get_string('fieldtypelabel', "ideafield_$this->type");
    }

    /**
     * Prints the respective type icon
     *
     * @global object
     * @return string
     */
    function image() {
        global $OUTPUT;

        $params = array('d'=>$this->idea->id, 'fid'=>$this->field->id, 'mode'=>'display', 'sesskey'=>sesskey());
        $link = new moodle_url('/mod/idea/field.php', $params);
        $str = '<a href="'.$link->out().'">';
        $str .= '<img src="'.$OUTPUT->pix_url('field/'.$this->type, 'idea') . '" ';
        $str .= 'height="'.$this->iconheight.'" width="'.$this->iconwidth.'" alt="'.$this->type.'" title="'.$this->type.'" /></a>';
        return $str;
    }

    /**
     * Per default, it is assumed that fields support text exporting.
     * Override this (return false) on fields not supporting text exporting.
     *
     * @return bool true
     */
    function text_export_supported() {
        return true;
    }

    /**
     * Per default, return the record's text value only from the "content" field.
     * Override this in fields class if necesarry.
     *
     * @param string $record
     * @return string
     */
    function export_text_value($record) {
        if ($this->text_export_supported()) {
            return $record->content;
        }
    }

    /**
     * @param string $relativepath
     * @return bool false
     */
    function file_ok($relativepath) {
        return false;
    }

    /**
     * Returns the priority for being indexed by globalsearch
     *
     * @return int
     */
    public static function get_priority() {
        return static::$priority;
    }

    /**
     * Returns the presentable string value for a field content.
     *
     * The returned string should be plain text.
     *
     * @param stdClass $content
     * @return string
     */
    public static function get_content_value($content) {
        return trim($content->content, "\r\n ");
    }
}


/**
 * Given a template and a ideaid, generate a default case template
 *
 * @global object
 * @param object $idea
 * @param string template [addtemplate, singletemplate, listtempalte, rsstemplate]
 * @param int $recordid
 * @param bool $form
 * @param bool $update
 * @return bool|string
 */
function idea_generate_default_template(&$idea, $template, $recordid=0, $form=false, $update=true) {
    global $DB;

    if (!$idea && !$template) {
        return false;
    }
    if ($template == 'csstemplate' or $template == 'jstemplate' ) {
        return '';
    }

    // get all the fields for that ideabase
    if ($fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id), 'id')) {

        $table = new html_table();
        $table->attributes['class'] = 'mod-idea-default-template ##approvalstatus##';
        $table->colclasses = array('template-field', 'template-token');
        $table->data = array();
        foreach ($fields as $field) {
            if ($form) {   // Print forms instead of idea
                $fieldobj = idea_get_field($field, $idea);
                $token = $fieldobj->display_add_field($recordid, null);
            } else {           // Just print the tag
                $token = '[['.$field->name.']]';
            }
            $table->data[] = array(
                $field->name.': ',
                $token
            );
        }
        if ($template == 'listtemplate') {
            $cell = new html_table_cell('##edit##  ##more##  ##delete##  ##approve##  ##disapprove##  ##export##');
            $cell->colspan = 2;
            $cell->attributes['class'] = 'controls';
            $table->data[] = new html_table_row(array($cell));
        } else if ($template == 'singletemplate') {
            $cell = new html_table_cell('##edit##  ##delete##  ##approve##  ##disapprove##  ##export##');
            $cell->colspan = 2;
            $cell->attributes['class'] = 'controls';
            $table->data[] = new html_table_row(array($cell));
        } else if ($template == 'asearchtemplate') {
            $row = new html_table_row(array(get_string('authorfirstname', 'idea').': ', '##firstname##'));
            $row->attributes['class'] = 'searchcontrols';
            $table->data[] = $row;
            $row = new html_table_row(array(get_string('authorlastname', 'idea').': ', '##lastname##'));
            $row->attributes['class'] = 'searchcontrols';
            $table->data[] = $row;
        }

        $str = '';
        if ($template == 'listtemplate'){
            $str .= '##delcheck##';
            $str .= html_writer::empty_tag('br');
        }

        $str .= html_writer::start_tag('div', array('class' => 'defaulttemplate'));
        $str .= html_writer::table($table);
        $str .= html_writer::end_tag('div');
        if ($template == 'listtemplate'){
            $str .= html_writer::empty_tag('hr');
        }

        if ($update) {
            $newidea = new stdClass();
            $newidea->id = $idea->id;
            $newidea->{$template} = $str;
            $DB->update_record('idea', $newidea);
            $idea->{$template} = $str;
        }

        return $str;
    }
}


/**
 * Search for a field name and replaces it with another one in all the
 * form templates. Set $newfieldname as '' if you want to delete the
 * field from the form.
 *
 * @global object
 * @param object $idea
 * @param string $searchfieldname
 * @param string $newfieldname
 * @return bool
 */
function idea_replace_field_in_templates($idea, $searchfieldname, $newfieldname) {
    global $DB;

    if (!empty($newfieldname)) {
        $prestring = '[[';
        $poststring = ']]';
        $idpart = '#id';

    } else {
        $prestring = '';
        $poststring = '';
        $idpart = '';
    }

    $newidea = new stdClass();
    $newidea->id = $idea->id;
    $newidea->singletemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $idea->singletemplate);

    $newidea->listtemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $idea->listtemplate);

    $newidea->addtemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $idea->addtemplate);

    $newidea->addtemplate = str_ireplace('[['.$searchfieldname.'#id]]',
            $prestring.$newfieldname.$idpart.$poststring, $idea->addtemplate);

    $newidea->rsstemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $idea->rsstemplate);

    return $DB->update_record('idea', $newidea);
}


/**
 * Appends a new field at the end of the form template.
 *
 * @global object
 * @param object $idea
 * @param string $newfieldname
 */
function idea_append_new_field_to_templates($idea, $newfieldname) {
    global $DB;

    $newidea = new stdClass();
    $newidea->id = $idea->id;
    $change = false;

    if (!empty($idea->singletemplate)) {
        $newidea->singletemplate = $idea->singletemplate.' [[' . $newfieldname .']]';
        $change = true;
    }
    if (!empty($idea->addtemplate)) {
        $newidea->addtemplate = $idea->addtemplate.' [[' . $newfieldname . ']]';
        $change = true;
    }
    if (!empty($idea->rsstemplate)) {
        $newidea->rsstemplate = $idea->singletemplate.' [[' . $newfieldname . ']]';
        $change = true;
    }
    if ($change) {
        $DB->update_record('idea', $newidea);
    }
}


/**
 * given a field name
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $name
 * @param object $idea
 * @return object|bool
 */
function idea_get_field_from_name($name, $idea){
    global $DB;

    $field = $DB->get_record('idea_fields', array('name'=>$name, 'ideaid'=>$idea->id));

    if ($field) {
        return idea_get_field($field, $idea);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param int $fieldid
 * @param object $idea
 * @return bool|object
 */
function idea_get_field_from_id($fieldid, $idea){
    global $DB;

    $field = $DB->get_record('idea_fields', array('id'=>$fieldid, 'ideaid'=>$idea->id));

    if ($field) {
        return idea_get_field($field, $idea);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $type
 * @param object $idea
 * @return object
 */
function idea_get_field_new($type, $idea) {
    global $CFG;

    require_once($CFG->dirroot.'/mod/idea/field/'.$type.'/field.class.php');
    $newfield = 'idea_field_'.$type;
    $newfield = new $newfield(0, $idea);
    return $newfield;
}

/**
 * returns a subclass field object given a record of the field, used to
 * invoke plugin methods
 * input: $param $field - record from db
 *
 * @global object
 * @param object $field
 * @param object $idea
 * @param object $cm
 * @return object
 */
function idea_get_field($field, $idea, $cm=null) {
    global $CFG;

    if ($field) {
        require_once('field/'.$field->type.'/field.class.php');
        $newfield = 'idea_field_'.$field->type;
        $newfield = new $newfield($field, $idea, $cm);
        return $newfield;
    }
}


/**
 * Given record object (or id), returns true if the record belongs to the current user
 *
 * @global object
 * @global object
 * @param mixed $record record object or id
 * @return bool
 */
function idea_isowner($record) {
    global $USER, $DB;

    if (!isloggedin()) { // perf shortcut
        return false;
    }

    if (!is_object($record)) {
        if (!$record = $DB->get_record('idea_records', array('id'=>$record))) {
            return false;
        }
    }

    return ($record->userid == $USER->id);
}

/**
 * has a user reached the max number of entries?
 *
 * @param object $idea
 * @return bool
 */
function idea_atmaxentries($idea){
    if (!$idea->maxentries){
        return false;

    } else {
        return (idea_numentries($idea) >= $idea->maxentries);
    }
}

/**
 * returns the number of entries already made by this user
 *
 * @global object
 * @global object
 * @param object $idea
 * @return int
 */
function idea_numentries($idea){
    global $USER, $DB;
    $sql = 'SELECT COUNT(*) FROM {idea_records} WHERE ideaid=? AND userid=?';
    return $DB->count_records_sql($sql, array($idea->id, $USER->id));
}

/**
 * function that takes in a ideaid and adds a record
 * this is used everytime an add template is submitted
 *
 * @global object
 * @global object
 * @param object $idea
 * @param int $groupid
 * @return bool
 */
function idea_add_record($idea, $groupid=0){
    global $USER, $DB;

    $cm = get_coursemodule_from_instance('idea', $idea->id);
    $context = context_module::instance($cm->id);

    $record = new stdClass();
    $record->userid = $USER->id;
    $record->ideaid = $idea->id;
    $record->groupid = $groupid;
    $record->timecreated = $record->timemodified = time();
    if (has_capability('mod/idea:approve', $context)) {
        $record->approved = 1;
    } else {
        $record->approved = 0;
    }
    $record->id = $DB->insert_record('idea_records', $record);

    // Trigger an event for creating this record.
    $event = \mod_idea\event\record_created::create(array(
        'objectid' => $record->id,
        'context' => $context,
        'other' => array(
            'ideaid' => $idea->id
        )
    ));
    $event->trigger();

    return $record->id;
}

/**
 * check the multple existence any tag in a template
 *
 * check to see if there are 2 or more of the same tag being used.
 *
 * @global object
 * @param int $ideaid,
 * @param string $template
 * @return bool
 */
function idea_tags_check($ideaid, $template) {
    global $DB, $OUTPUT;

    // first get all the possible tags
    $fields = $DB->get_records('idea_fields', array('ideaid'=>$ideaid));
    // then we generate strings to replace
    $tagsok = true; // let's be optimistic
    foreach ($fields as $field){
        $pattern="/\[\[" . preg_quote($field->name, '/') . "\]\]/i";
        if (preg_match_all($pattern, $template, $dummy)>1){
            $tagsok = false;
            echo $OUTPUT->notification('[['.$field->name.']] - '.get_string('multipletags','idea'));
        }
    }
    // else return true
    return $tagsok;
}

/**
 * Adds an instance of a idea
 *
 * @param stdClass $idea
 * @param mod_idea_mod_form $mform
 * @return int intance id
 */
function idea_add_instance($idea, $mform = null) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/idea/locallib.php');

    if (empty($idea->assessed)) {
        $idea->assessed = 0;
    }

    if (empty($idea->ratingtime) || empty($idea->assessed)) {
        $idea->assesstimestart  = 0;
        $idea->assesstimefinish = 0;
    }

    $idea->timemodified = time();

    $idea->id = $DB->insert_record('idea', $idea);

    // Add calendar events if necessary.
    idea_set_events($idea);

    idea_grade_item_update($idea);

    return $idea->id;
}

/**
 * updates an instance of a idea
 *
 * @global object
 * @param object $idea
 * @return bool
 */
function idea_update_instance($idea) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/idea/locallib.php');

    $idea->timemodified = time();
    $idea->id           = $idea->instance;

    if (empty($idea->assessed)) {
        $idea->assessed = 0;
    }

    if (empty($idea->ratingtime) or empty($idea->assessed)) {
        $idea->assesstimestart  = 0;
        $idea->assesstimefinish = 0;
    }

    if (empty($idea->notification)) {
        $idea->notification = 0;
    }

    $DB->update_record('idea', $idea);

    // Add calendar events if necessary.
    idea_set_events($idea);

    idea_grade_item_update($idea);

    return true;

}

/**
 * deletes an instance of a idea
 *
 * @global object
 * @param int $id
 * @return bool
 */
function idea_delete_instance($id) {    // takes the ideaid
    global $DB, $CFG;

    if (!$idea = $DB->get_record('idea', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('idea', $idea->id);
    $context = context_module::instance($cm->id);

/// Delete all the associated information

    // files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_idea');

    // get all the records in this idea
    $sql = "SELECT r.id
              FROM {idea_records} r
             WHERE r.ideaid = ?";

    $DB->delete_records_select('idea_content', "recordid IN ($sql)", array($id));

    // delete all the records and fields
    $DB->delete_records('idea_records', array('ideaid'=>$id));
    $DB->delete_records('idea_fields', array('ideaid'=>$id));

    // Remove old calendar events.
    $events = $DB->get_records('event', array('modulename' => 'idea', 'instance' => $id));
    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    // Delete the instance itself
    $result = $DB->delete_records('idea', array('id'=>$id));

    // cleanup gradebook
    idea_grade_item_delete($idea);

    return $result;
}

/**
 * returns a summary of idea activity of this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $idea
 * @return object|null
 */
function idea_user_outline($course, $user, $mod, $idea) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'idea', $idea->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }


    if ($countrecords = $DB->count_records('idea_records', array('ideaid'=>$idea->id, 'userid'=>$user->id))) {
        $result = new stdClass();
        $result->info = get_string('numrecords', 'idea', $countrecords);
        $lastrecord   = $DB->get_record_sql('SELECT id,timemodified FROM {idea_records}
                                              WHERE ideaid = ? AND userid = ?
                                           ORDER BY timemodified DESC', array($idea->id, $user->id), true);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new stdClass();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;

        //datesubmitted == time created. dategraded == time modified or time overridden
        //if grade was last modified by the user themselves use date graded. Otherwise use date submitted
        //TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }

        return $result;
    }
    return NULL;
}

/**
 * Prints all the records uploaded by this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $idea
 */
function idea_user_complete($course, $user, $mod, $idea) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'idea', $idea->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
        if ($grade->str_feedback) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }

    if ($records = $DB->get_records('idea_records', array('ideaid'=>$idea->id,'userid'=>$user->id), 'timemodified DESC')) {
        idea_print_template('singletemplate', $records, $idea);
    }
}

/**
 * Return grade for given user or all users.
 *
 * @global object
 * @param object $idea
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function idea_get_user_grades($idea, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');

    $ratingoptions = new stdClass;
    $ratingoptions->component = 'mod_idea';
    $ratingoptions->ratingarea = 'entry';
    $ratingoptions->modulename = 'idea';
    $ratingoptions->moduleid   = $idea->id;

    $ratingoptions->userid = $userid;
    $ratingoptions->aggregationmethod = $idea->assessed;
    $ratingoptions->scaleid = $idea->scale;
    $ratingoptions->itemtable = 'idea_records';
    $ratingoptions->itemtableusercolumn = 'userid';

    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}

/**
 * Update activity grades
 *
 * @category grade
 * @param object $idea
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function idea_update_grades($idea, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$idea->assessed) {
        idea_grade_item_update($idea);

    } else if ($grades = idea_get_user_grades($idea, $userid)) {
        idea_grade_item_update($idea, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        idea_grade_item_update($idea, $grade);

    } else {
        idea_grade_item_update($idea);
    }
}

/**
 * Update/create grade item for given idea
 *
 * @category grade
 * @param stdClass $idea A ideabase instance with extra cmidnumber property
 * @param mixed $grades Optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function idea_grade_item_update($idea, $grades=NULL) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$idea->name, 'idnumber'=>$idea->cmidnumber);

    if (!$idea->assessed or $idea->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($idea->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $idea->scale;
        $params['grademin']  = 0;

    } else if ($idea->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$idea->scale;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/idea', $idea->course, 'mod', 'idea', $idea->id, 0, $grades, $params);
}

/**
 * Delete grade item for given idea
 *
 * @category grade
 * @param object $idea object
 * @return object grade_item
 */
function idea_grade_item_delete($idea) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/idea', $idea->course, 'mod', 'idea', $idea->id, 0, NULL, array('deleted'=>1));
}

// junk functions
/**
 * takes a list of records, the current idea, a search string,
 * and mode to display prints the translated template
 *
 * @global object
 * @global object
 * @param string $template
 * @param array $records
 * @param object $idea
 * @param string $search
 * @param int $page
 * @param bool $return
 * @param object $jumpurl a moodle_url by which to jump back to the record list (can be null)
 * @return mixed
 */
function idea_print_template($template, $records, $idea, $search='', $page=0, $return=false, moodle_url $jumpurl=null) {
    global $CFG, $DB, $OUTPUT;

    $cm = get_coursemodule_from_instance('idea', $idea->id);
    $context = context_module::instance($cm->id);

    static $fields = array();
    static $ideaid = null;

    if (empty($ideaid)) {
        $ideaid = $idea->id;
    } else if ($ideaid != $idea->id) {
        $fields = array();
    }

    if (empty($fields)) {
        $fieldrecords = $DB->get_records('idea_fields', array('ideaid'=>$idea->id));
        foreach ($fieldrecords as $fieldrecord) {
            $fields[]= idea_get_field($fieldrecord, $idea);
        }
    }

    if (empty($records)) {
        return;
    }

    if (!$jumpurl) {
        $jumpurl = new moodle_url('/mod/idea/view.php', array('d' => $idea->id));
    }
    $jumpurl = new moodle_url($jumpurl, array('page' => $page, 'sesskey' => sesskey()));

    foreach ($records as $record) {   // Might be just one for the single template

    // Replacing tags
        $patterns = array();
        $replacement = array();

    // Then we generate strings to replace for normal tags
        foreach ($fields as $field) {
            $patterns[]='[['.$field->field->name.']]';
            $replacement[] = highlight($search, $field->display_browse_field($record->id, $template));
        }

        $canmanageentries = has_capability('mod/idea:manageentries', $context);

    // Replacing special tags (##Edit##, ##Delete##, ##More##)
        $patterns[]='##edit##';
        $patterns[]='##delete##';
        if (idea_user_can_manage_entry($record, $idea, $context)) {
            $replacement[] = '<a href="'.$CFG->wwwroot.'/mod/idea/edit.php?d='
                             .$idea->id.'&amp;rid='.$record->id.'&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
            $replacement[] = '<a href="'.$CFG->wwwroot.'/mod/idea/view.php?d='
                             .$idea->id.'&amp;delete='.$record->id.'&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
        } else {
            $replacement[] = '';
            $replacement[] = '';
        }

        $moreurl = $CFG->wwwroot . '/mod/idea/view.php?d=' . $idea->id . '&amp;rid=' . $record->id;
        if ($search) {
            $moreurl .= '&amp;filter=1';
        }
        $patterns[]='##more##';
        $replacement[] = '<a href="'.$moreurl.'"><img src="'.$OUTPUT->pix_url('t/preview').
                        '" class="iconsmall" alt="'.get_string('more', 'idea').'" title="'.get_string('more', 'idea').
                        '" /></a>';

        $patterns[]='##moreurl##';
        $replacement[] = $moreurl;

        $patterns[]='##delcheck##';
        if ($canmanageentries) {
            $replacement[] = html_writer::checkbox('delcheck[]', $record->id, false, '', array('class' => 'recordcheckbox'));
        } else {
            $replacement[] = '';
        }

        $patterns[]='##user##';
        $replacement[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$record->userid.
                               '&amp;course='.$idea->course.'">'.fullname($record).'</a>';

        $patterns[] = '##userpicture##';
        $ruser = user_picture::unalias($record, null, 'userid');
        $replacement[] = $OUTPUT->user_picture($ruser, array('courseid' => $idea->course));

        $patterns[]='##export##';

        if (!empty($CFG->enableportfolios) && ($template == 'singletemplate' || $template == 'listtemplate')
            && ((has_capability('mod/idea:exportentry', $context)
                || (idea_isowner($record->id) && has_capability('mod/idea:exportownentry', $context))))) {
            require_once($CFG->libdir . '/portfoliolib.php');
            $button = new portfolio_add_button();
            $button->set_callback_options('idea_portfolio_caller', array('id' => $cm->id, 'recordid' => $record->id), 'mod_idea');
            list($formats, $files) = idea_portfolio_caller::formats($fields, $record);
            $button->set_formats($formats);
            $replacement[] = $button->to_html(PORTFOLIO_ADD_ICON_LINK);
        } else {
            $replacement[] = '';
        }

        $patterns[] = '##timeadded##';
        $replacement[] = userdate($record->timecreated);

        $patterns[] = '##timemodified##';
        $replacement [] = userdate($record->timemodified);

        $patterns[]='##approve##';
        if (has_capability('mod/idea:approve', $context) && ($idea->approval) && (!$record->approved)) {
            $approveurl = new moodle_url($jumpurl, array('approve' => $record->id));
            $approveicon = new pix_icon('t/approve', get_string('approve', 'idea'), '', array('class' => 'iconsmall'));
            $replacement[] = html_writer::tag('span', $OUTPUT->action_icon($approveurl, $approveicon),
                    array('class' => 'approve'));
        } else {
            $replacement[] = '';
        }

        $patterns[]='##disapprove##';
        if (has_capability('mod/idea:approve', $context) && ($idea->approval) && ($record->approved)) {
            $disapproveurl = new moodle_url($jumpurl, array('disapprove' => $record->id));
            $disapproveicon = new pix_icon('t/block', get_string('disapprove', 'idea'), '', array('class' => 'iconsmall'));
            $replacement[] = html_writer::tag('span', $OUTPUT->action_icon($disapproveurl, $disapproveicon),
                    array('class' => 'disapprove'));
        } else {
            $replacement[] = '';
        }

        $patterns[] = '##approvalstatus##';
        if (!$idea->approval) {
            $replacement[] = '';
        } else if ($record->approved) {
            $replacement[] = get_string('approved', 'idea');
        } else {
            $replacement[] = get_string('notapproved', 'idea');
        }

        $patterns[]='##comments##';
        if (($template == 'listtemplate') && ($idea->comments)) {

            if (!empty($CFG->usecomments)) {
                require_once($CFG->dirroot  . '/comment/lib.php');
                list($context, $course, $cm) = get_context_info_array($context->id);
                $cmt = new stdClass();
                $cmt->context = $context;
                $cmt->course  = $course;
                $cmt->cm      = $cm;
                $cmt->area    = 'ideabase_entry';
                $cmt->itemid  = $record->id;
                $cmt->showcount = true;
                $cmt->component = 'mod_idea';
                $comment = new comment($cmt);
                $replacement[] = $comment->output(true);
            }
        } else {
            $replacement[] = '';
        }

        // actual replacement of the tags
        $newtext = str_ireplace($patterns, $replacement, $idea->{$template});

        // no more html formatting and filtering - see MDL-6635
        if ($return) {
            return $newtext;
        } else {
            echo $newtext;

            // hack alert - return is always false in singletemplate anyway ;-)
            /**********************************
             *    Printing Ratings Form       *
             *********************************/
            if ($template == 'singletemplate') {    //prints ratings options
                idea_print_ratings($idea, $record);
            }

            /**********************************
             *    Printing Comments Form       *
             *********************************/
            //TODO  Ranil - Add CREATE course project button OR MANAGE button
            //TODO
            if ($template == 'singletemplate') {    //prints ratings options
            	global $DB, $USER;
            	$courseid=$idea->course;
            	$ideaid = $idea->id;
            	$recordid = $record->id;
            	$userid = $USER->id;
            	//$moodlebase =$CFG->wwwroot;
            	
            	
            	//get record title
            	$sql="SELECT {idea_content}.content  FROM {idea_content} , {idea_fields} where {idea_content}.fieldid = {idea_fields}.id AND {idea_content}.recordid = ? order by {idea_content}.fieldid ASC LIMIT 1";
            	$recordtitle = $DB->get_field_sql($sql, array ($recordid), $strictness=IGNORE_MISSING);
            	$coursecontext = context_course::instance($courseid);
            	$recorduserid = $DB->get_field('idea_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
            
            	$usertype = ideagetanyuserroleid($coursecontext,$userid);
            	$recordusertype = ideagetanyuserroleid($coursecontext,$recorduserid);
            
            		
            //activate button to select manage or delete
            	
            	
            //check is the idea matched
            	$idea_matched_message="";
            	$publisher_matched_message="";
            	if ($DB->get_field('idea_records', 'usermatched', array('id'=> $recordid), $strictness=IGNORE_MISSING) == 1) {
            		$idea_matched_message='<h6> This idea already matched and project has created</h6>';
            	}elseif ($DB->get_field('idea_records', 'notavilable', array('id'=> $recordid), $strictness=IGNORE_MISSING) == 1) { //check is idea matched due to other
            		$publisher_matched_message='<h6> The publisher of this idea has already matched</h6>';
            	}
            	 
            //check is own idea
            	$own_idea_message="";
            	$sameuser_type_matchmessage ="";
            	 
            	if ($userid==$recorduserid ){
            		$own_idea_message = '<h6> This is published by you</h6>';
            	}elseif (ideagetcustomrolename($usertype) == ideagetcustomrolename($recordusertype)){ // Check user types of user and idea publisher
            		$sameuser_type_matchmessage= "<h6>&nbsp;This has published by another ". ideagetcustomrolename(ideagetanyuserroleid( $coursecontext , $recorduserid  )). "</h6>";
            	}
            	
            	$normaluser_message ="";
            	$normaluser_message = $own_idea_message . $sameuser_type_matchmessage . $idea_matched_message . $publisher_matched_message  ;
            	 
            	//for normal users check is there any constrain to create project file for start processing
            	if ($normaluser_message == "") {
            		echo '<a href="http://localhost/moodle/mod/idea/prepare_create_project.php?courseid='.$idea->course .'&ideaid='. $idea->id.'&recordid='. $record->id.'&recordtitle='.$recordtitle.'"> Select This Idea </a>';
            		// echo "test course id $idea->course";
            	} else {
            		echo "<h5> As a &nbsp;". ideagetcustomrolename(ideagetanyuserroleid( $coursecontext , $userid  )). " You can not select this idea </h5> Reasons are:". $normaluser_message . "</br>";
            	}
            	
            	
           // for admin and managers

            	////////
            	if ( ideagetanyuserroleid($coursecontext,$userid)==1 || is_siteadmin($userid)  ){
            	
            		if($publisher_matched_message=="" && $idea_matched_message==""){
            			echo '<a href="http://localhost/moodle/mod/idea/manage_create_project.php?courseid='.$idea->course .'&recourduserid='. $recorduserid .'&recourdusertype='. $recordusertype . '&ideaid='. $idea->id.'&recordid='. $record->id.'&recordtitle='.$recordtitle.'"> </br> Match This Idea </a>';
            		}elseif($publisher_matched_message<>""){
            			echo '<h6> Publisher of this idea have already selected another idea</h6>';
            		}elseif ($idea_matched_message<>""){
            			echo '<h5>Warning: check is idea is correct! Project data will be delete if the project has started</h5>';
            			echo '<a href="http://localhost/moodle/mod/idea/unmatched_project.php?courseid='.$idea->course .'&ideaid='. $idea->id.'&recordid='. $record->id.'&recordtitle='.$recordtitle.'"> </br> Unmatch This Idea </a>';
            			
            			/**
            			echo '<form action="a href="http://localhost/moodle/mod/idea/unmatched_project.php" method="post">
            			<input type="hidden" name="courseid" value="'.$idea->course.'" />
            			<input type="hidden" name="ideaid" value="'.$idea->id.'" />
            			<input type="hidden" name="recordid" value="'.$record->id.'" />
            			<input type="hidden" name="recordtitle" value="'.$recordtitle.'" />
            			<input type="submit" value="Start Unmatch">
            			</form>';
            			*/
            			
            			
            			
            			//echo '<a href="http://localhost/moodle/mod/idea/delete_project.php?courseid='.$idea->course .'&ideaid='. $idea->id.'&recordid='. $record->id.'&recordtitle='.$recordtitle.'"> Unmatched This Idea </a>';
            		}
            	}
            	// end of manage select button enable secton
            }
            
            
            
            if (($template == 'singletemplate') && ($idea->comments)) {
                if (!empty($CFG->usecomments)) {
                    require_once($CFG->dirroot . '/comment/lib.php');
                    list($context, $course, $cm) = get_context_info_array($context->id);
                    $cmt = new stdClass();
                    $cmt->context = $context;
                    $cmt->course  = $course;
                    $cmt->cm      = $cm;
                    $cmt->area    = 'ideabase_entry';
                    $cmt->itemid  = $record->id;
                    $cmt->showcount = true;
                    $cmt->component = 'mod_idea';
                    $comment = new comment($cmt);
                    $comment->output(false);
                }
            }
        }
    }
}
//
/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function idea_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_idea' || $ratingarea != 'entry') {
        return null;
    }
    return array(
        'view'    => has_capability('mod/idea:viewrating',$context),
        'viewany' => has_capability('mod/idea:viewanyrating',$context),
        'viewall' => has_capability('mod/idea:viewallratings',$context),
        'rate'    => has_capability('mod/idea:rate',$context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted idea
 *            context => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [required]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function idea_rating_validate($params) {
    global $DB, $USER;

    // Check the component is mod_idea
    if ($params['component'] != 'mod_idea') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is entry (the only rating area in idea module)
    if ($params['ratingarea'] != 'entry') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own entries
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $ideasql = "SELECT d.id as ideaid, d.scale, d.course, r.userid as userid, d.approval, r.approved, r.timecreated, d.assesstimestart, d.assesstimefinish, r.groupid
                  FROM {idea_records} r
                  JOIN {idea} d ON r.ideaid = d.id
                 WHERE r.id = :itemid";
    $ideaparams = array('itemid'=>$params['itemid']);
    if (!$info = $DB->get_record_sql($ideasql, $ideaparams)) {
        //item doesn't exist
        throw new rating_exception('invaliditemid');
    }

    if ($info->scale != $params['scaleid']) {
        //the scale being submitted doesnt match the one in the ideabase
        throw new rating_exception('invalidscaleid');
    }

    //check that the submitted rating is valid for the scale

    // lower limit
    if ($params['rating'] < 0  && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // upper limit
    if ($info->scale < 0) {
        //its a custom scale
        $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->scale) {
        //if its numeric and submitted rating is above maximum
        throw new rating_exception('invalidnum');
    }

    if ($info->approval && !$info->approved) {
        //ideabase requires approval but this item isnt approved
        throw new rating_exception('nopermissiontorate');
    }

    // check the item we're rating was created in the assessable time window
    if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
            throw new rating_exception('notavailable');
        }
    }

    $course = $DB->get_record('course', array('id'=>$info->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('idea', $info->ideaid, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // if the supplied context doesnt match the item's context
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    // Make sure groups allow this user to see the item they're rating
    $groupid = $info->groupid;
    if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
        if (!groups_group_exists($groupid)) { // Can't find group
            throw new rating_exception('cannotfindgroup');//something is wrong
        }

        if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
            throw new rating_exception('notmemberofgroup');
        }
    }

    return true;
}

/**
 * Can the current user see ratings for a given itemid?
 *
 * @param array $params submitted idea
 *            contextid => int contextid [required]
 *            component => The component for this module - should always be mod_idea [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int scale id [optional]
 * @return bool
 * @throws coding_exception
 * @throws rating_exception
 */
function mod_idea_rating_can_see_item_ratings($params) {
    global $DB;

    // Check the component is mod_idea.
    if (!isset($params['component']) || $params['component'] != 'mod_idea') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is entry (the only rating area in idea).
    if (!isset($params['ratingarea']) || $params['ratingarea'] != 'entry') {
        throw new rating_exception('invalidratingarea');
    }

    if (!isset($params['itemid'])) {
        throw new rating_exception('invaliditemid');
    }

    $ideasql = "SELECT d.id as ideaid, d.course, r.groupid
                  FROM {idea_records} r
                  JOIN {idea} d ON r.ideaid = d.id
                 WHERE r.id = :itemid";
    $ideaparams = array('itemid' => $params['itemid']);
    if (!$info = $DB->get_record_sql($ideasql, $ideaparams)) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }

    // User can see ratings of all participants.
    if ($info->groupid == 0) {
        return true;
    }

    $course = $DB->get_record('course', array('id' => $info->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('idea', $info->ideaid, $course->id, false, MUST_EXIST);

    // Make sure groups allow this user to see the item they're rating.
    return groups_group_visible($info->groupid, $course, $cm);
}


/**
 * function that takes in the current idea, number of items per page,
 * a search string and prints a preference box in view.php
 *
 * This preference box prints a searchable advanced search template if
 *     a) A template is defined
 *  b) The advanced search checkbox is checked.
 *
 * @global object
 * @global object
 * @param object $idea
 * @param int $perpage
 * @param string $search
 * @param string $sort
 * @param string $order
 * @param array $search_array
 * @param int $advanced
 * @param string $mode
 * @return void
 */
function idea_print_preference_form($idea, $perpage, $search, $sort='', $order='ASC', $search_array = '', $advanced = 0, $mode= ''){
    global $CFG, $DB, $PAGE, $OUTPUT;

    $cm = get_coursemodule_from_instance('idea', $idea->id);
    $context = context_module::instance($cm->id);
    
    //TODO ranil ADD CHECK BOXES TO FILTER PANEL    line 1742
    
    
    echo '<form action="view.php?d='. $idea->id .'" method="post">';
    echo '<table><td><td><input type="checkbox" name="matchedidea" value="1"'. ((isset($_POST['matchedidea'])) ? 'checked="checked"' : "") . ' onclick="submit()">Matched Ideas </td>';
    echo '<td><input type="checkbox" name="avilableidea" value="1"'. ((isset($_POST['avilableidea'])) ? 'checked="checked"' : "") . ' onclick="submit()"> Avilable ideas </td>';
    echo '<td><input type="checkbox" name="supervisoridea" value="1"'. ((isset($_POST['supervisoridea'])) ? 'checked="checked"' : "") . ' onclick="submit()">Supervisor Ideas</td>';
    echo '<td><input type="checkbox" name="studentidea" value="1"'. ((isset($_POST['studentidea'])) ? 'checked="checked"' : "") . ' onclick="submit()">Student Ideas</td>';
    echo '<td><input type="checkbox" name="myideas" value="1"'. ((isset($_POST['myideas'])) ? 'checked="checked"' : "") . ' onclick="submit()">My Ideas</td>';
    echo '<td><input type="submit" value="Filter idea"</td></th></table>';
    echo '</form>';
    
    
    //TODO END OF FILTER PANELranil
    
    echo '<br /><div class="ideapreferences">';
    echo '<form id="options" action="view.php" method="get">';
    echo '<div>';
    echo '<input type="hidden" name="d" value="'.$idea->id.'" />';
    if ($mode =='asearch') {
        $advanced = 1;
        echo '<input type="hidden" name="mode" value="list" />';
    }
    echo '<label for="pref_perpage">'.get_string('pagesize','idea').'</label> ';
    $pagesizes = array(2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                       20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
    echo html_writer::select($pagesizes, 'perpage', $perpage, false, array('id' => 'pref_perpage', 'class' => 'custom-select'));

    if ($advanced) {
        $regsearchclass = 'search_none';
        $advancedsearchclass = 'search_inline';
    } else {
        $regsearchclass = 'search_inline';
        $advancedsearchclass = 'search_none';
    }
    echo '<div id="reg_search" class="' . $regsearchclass . ' form-inline" >&nbsp;&nbsp;&nbsp;';
    echo '<label for="pref_search">' . get_string('search') . '</label> <input type="text" ' .
         'class="form-control" size="16" name="search" id= "pref_search" value="' . s($search) . '" /></div>';
    echo '&nbsp;&nbsp;&nbsp;<label for="pref_sortby">'.get_string('sortby').'</label> ';
    // foreach field, print the option
    echo '<select name="sort" id="pref_sortby" class="custom-select m-r-1">';
    if ($fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id), 'name')) {
        echo '<optgroup label="'.get_string('fields', 'idea').'">';
        foreach ($fields as $field) {
            if ($field->id == $sort) {
                echo '<option value="'.$field->id.'" selected="selected">'.$field->name.'</option>';
            } else {
                echo '<option value="'.$field->id.'">'.$field->name.'</option>';
            }
        }
        echo '</optgroup>';
    }
    $options = array();
    $options[idea_TIMEADDED]    = get_string('timeadded', 'idea');
    $options[idea_TIMEMODIFIED] = get_string('timemodified', 'idea');
    $options[idea_FIRSTNAME]    = get_string('authorfirstname', 'idea');
    $options[idea_LASTNAME]     = get_string('authorlastname', 'idea');
    if ($idea->approval and has_capability('mod/idea:approve', $context)) {
        $options[idea_APPROVED] = get_string('approved', 'idea');
    }
    echo '<optgroup label="'.get_string('other', 'idea').'">';
    foreach ($options as $key => $name) {
        if ($key == $sort) {
            echo '<option value="'.$key.'" selected="selected">'.$name.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$name.'</option>';
        }
    }
    echo '</optgroup>';
    echo '</select>';
    echo '<label for="pref_order" class="accesshide">'.get_string('order').'</label>';
    echo '<select id="pref_order" name="order" class="custom-select m-r-1">';
    if ($order == 'ASC') {
        echo '<option value="ASC" selected="selected">'.get_string('ascending','idea').'</option>';
    } else {
        echo '<option value="ASC">'.get_string('ascending','idea').'</option>';
    }
    if ($order == 'DESC') {
        echo '<option value="DESC" selected="selected">'.get_string('descending','idea').'</option>';
    } else {
        echo '<option value="DESC">'.get_string('descending','idea').'</option>';
    }
    echo '</select>';

    if ($advanced) {
        $checked = ' checked="checked" ';
    }
    else {
        $checked = '';
    }
    $PAGE->requires->js('/mod/idea/idea.js');
    echo '&nbsp;<input type="hidden" name="advanced" value="0" />';
    echo '&nbsp;<input type="hidden" name="filter" value="1" />';
    echo '&nbsp;<input type="checkbox" id="advancedcheckbox" name="advanced" value="1" ' . $checked . ' ' .
         'onchange="showHideAdvSearch(this.checked);" class="m-x-1" />' .
         '<label for="advancedcheckbox">' . get_string('advancedsearch', 'idea') . '</label>';
    echo '&nbsp;<input type="submit" class="btn btn-secondary" value="' . get_string('savesettings', 'idea') . '" />';

    echo '<br />';
    echo '<div class="' . $advancedsearchclass . '" id="idea_adv_form">';
    echo '<table class="boxaligncenter">';

    // print ASC or DESC
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    $i = 0;

    // Determine if we are printing all fields for advanced search, or the template for advanced search
    // If a template is not defined, use the deafault template and display all fields.
    if(empty($idea->asearchtemplate)) {
        idea_generate_default_template($idea, 'asearchtemplate');
    }

    static $fields = array();
    static $ideaid = null;

    if (empty($ideaid)) {
        $ideaid = $idea->id;
    } else if ($ideaid != $idea->id) {
        $fields = array();
    }

    if (empty($fields)) {
        $fieldrecords = $DB->get_records('idea_fields', array('ideaid'=>$idea->id));
        foreach ($fieldrecords as $fieldrecord) {
            $fields[]= idea_get_field($fieldrecord, $idea);
        }
    }

    // Replacing tags
    $patterns = array();
    $replacement = array();

    // Then we generate strings to replace for normal tags
    foreach ($fields as $field) {
        $fieldname = $field->field->name;
        $fieldname = preg_quote($fieldname, '/');
        $patterns[] = "/\[\[$fieldname\]\]/i";
        $searchfield = idea_get_field_from_id($field->field->id, $idea);
        if (!empty($search_array[$field->field->id]->idea)) {
            $replacement[] = $searchfield->display_search_field($search_array[$field->field->id]->idea);
        } else {
            $replacement[] = $searchfield->display_search_field();
        }
    }
    $fn = !empty($search_array[idea_FIRSTNAME]->idea) ? $search_array[idea_FIRSTNAME]->idea : '';
    $ln = !empty($search_array[idea_LASTNAME]->idea) ? $search_array[idea_LASTNAME]->idea : '';
    $patterns[]    = '/##firstname##/';
    $replacement[] = '<label class="accesshide" for="u_fn">' . get_string('authorfirstname', 'idea') . '</label>' .
                     '<input type="text" class="form-control" size="16" id="u_fn" name="u_fn" value="' . s($fn) . '" />';
    $patterns[]    = '/##lastname##/';
    $replacement[] = '<label class="accesshide" for="u_ln">' . get_string('authorlastname', 'idea') . '</label>' .
                     '<input type="text" class="form-control" size="16" id="u_ln" name="u_ln" value="' . s($ln) . '" />';

    // actual replacement of the tags
    $newtext = preg_replace($patterns, $replacement, $idea->asearchtemplate);

    $options = new stdClass();
    $options->para=false;
    $options->noclean=true;
    echo '<tr><td>';
    echo format_text($newtext, FORMAT_HTML, $options);
    echo '</td></tr>';

    echo '<tr><td colspan="4"><br/>' .
         '<input type="submit" class="btn btn-primary m-r-1" value="' . get_string('savesettings', 'idea') . '" />' .
         '<input type="submit" class="btn btn-secondary" name="resetadv" value="' . get_string('resetsettings', 'idea') . '" />' .
         '</td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
}

/**
 * @global object
 * @global object
 * @param object $idea
 * @param object $record
 * @return void Output echo'd
 */
function idea_print_ratings($idea, $record) {
    global $OUTPUT;
    if (!empty($record->rating)){
        echo $OUTPUT->render($record->rating);
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function idea_get_view_actions() {
    return array('view');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function idea_get_post_actions() {
    return array('add','update','record delete');
}

/**
 * @param string $name
 * @param int $ideaid
 * @param int $fieldid
 * @return bool
 */
function idea_fieldname_exists($name, $ideaid, $fieldid = 0) {
    global $DB;

    if (!is_numeric($name)) {
        $like = $DB->sql_like('df.name', ':name', false);
    } else {
        $like = "df.name = :name";
    }
    $params = array('name'=>$name);
    if ($fieldid) {
        $params['ideaid']   = $ideaid;
        $params['fieldid1'] = $fieldid;
        $params['fieldid2'] = $fieldid;
        return $DB->record_exists_sql("SELECT * FROM {idea_fields} df
                                        WHERE $like AND df.ideaid = :ideaid
                                              AND ((df.id < :fieldid1) OR (df.id > :fieldid2))", $params);
    } else {
        $params['ideaid']   = $ideaid;
        return $DB->record_exists_sql("SELECT * FROM {idea_fields} df
                                        WHERE $like AND df.ideaid = :ideaid", $params);
    }
}

/**
 * @param array $fieldinput
 */
function idea_convert_arrays_to_strings(&$fieldinput) {
    foreach ($fieldinput as $key => $val) {
        if (is_array($val)) {
            $str = '';
            foreach ($val as $inner) {
                $str .= $inner . ',';
            }
            $str = substr($str, 0, -1);

            $fieldinput->$key = $str;
        }
    }
}


/**
 * Converts a ideabase (module instance) to use the Roles System
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CAP_PREVENT
 * @uses CAP_ALLOW
 * @param object $idea a idea object with the same attributes as a record
 *                     from the idea ideabase table
 * @param int $ideamodid the id of the idea module, from the modules table
 * @param array $teacherroles array of roles that have archetype teacher
 * @param array $studentroles array of roles that have archetype student
 * @param array $guestroles array of roles that have archetype guest
 * @param int $cmid the course_module id for this idea instance
 * @return boolean idea module was converted or not
 */
function idea_convert_to_roles($idea, $teacherroles=array(), $studentroles=array(), $cmid=NULL) {
    global $CFG, $DB, $OUTPUT;

    if (!isset($idea->participants) && !isset($idea->assesspublic)
            && !isset($idea->groupmode)) {
        // We assume that this ideabase has already been converted to use the
        // Roles System. above fields get dropped the idea module has been
        // upgraded to use Roles.
        return false;
    }

    if (empty($cmid)) {
        // We were not given the course_module id. Try to find it.
        if (!$cm = get_coursemodule_from_instance('idea', $idea->id)) {
            echo $OUTPUT->notification('Could not get the course module for the idea');
            return false;
        } else {
            $cmid = $cm->id;
        }
    }
    $context = context_module::instance($cmid);


    // $idea->participants:
    // 1 - Only teachers can add entries
    // 3 - Teachers and students can add entries
    switch ($idea->participants) {
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:writeentry', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 3:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:writeentry', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $idea->assessed:
    // 2 - Only teachers can rate posts
    // 1 - Everyone can rate posts
    // 0 - No one can rate posts
    switch ($idea->assessed) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:rate', CAP_PREVENT, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:rate', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 2:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $idea->assesspublic:
    // 0 - Students can only see their own ratings
    // 1 - Students can see everyone's ratings
    switch ($idea->assesspublic) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:viewrating', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/idea:viewrating', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/idea:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    if (empty($cm)) {
        $cm = $DB->get_record('course_modules', array('id'=>$cmid));
    }

    switch ($cm->groupmode) {
        case NOGROUPS:
            break;
        case SEPARATEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case VISIBLEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }
    return true;
}

/**
 * Returns the best name to show for a preset
 *
 * @param string $shortname
 * @param  string $path
 * @return string
 */
function idea_preset_name($shortname, $path) {

    // We are looking inside the preset itself as a first choice, but also in normal idea directory
    $string = get_string('modulename', 'ideapreset_'.$shortname);

    if (substr($string, 0, 1) == '[') {
        return $shortname;
    } else {
        return $string;
    }
}

/**
 * Returns an array of all the available presets.
 *
 * @return array
 */
function idea_get_available_presets($context) {
    global $CFG, $USER;

    $presets = array();

    // First load the ratings sub plugins that exist within the modules preset dir
    if ($dirs = core_component::get_plugin_list('ideapreset')) {
        foreach ($dirs as $dir=>$fulldir) {
            if (is_directory_idea_preset($fulldir)) {
                $preset = new stdClass();
                $preset->path = $fulldir;
                $preset->userid = 0;
                $preset->shortname = $dir;
                $preset->name = idea_preset_name($dir, $fulldir);
                if (file_exists($fulldir.'/screenshot.jpg')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/idea/preset/'.$dir.'/screenshot.jpg';
                } else if (file_exists($fulldir.'/screenshot.png')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/idea/preset/'.$dir.'/screenshot.png';
                } else if (file_exists($fulldir.'/screenshot.gif')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/idea/preset/'.$dir.'/screenshot.gif';
                }
                $presets[] = $preset;
            }
        }
    }
    // Now add to that the site presets that people have saved
    $presets = idea_get_available_site_presets($context, $presets);
    return $presets;
}

/**
 * Gets an array of all of the presets that users have saved to the site.
 *
 * @param stdClass $context The context that we are looking from.
 * @param array $presets
 * @return array An array of presets
 */
function idea_get_available_site_presets($context, array $presets=array()) {
    global $USER;

    $fs = get_file_storage();
    $files = $fs->get_area_files(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA);
    $canviewall = has_capability('mod/idea:viewalluserpresets', $context);
    if (empty($files)) {
        return $presets;
    }
    foreach ($files as $file) {
        if (($file->is_directory() && $file->get_filepath()=='/') || !$file->is_directory() || (!$canviewall && $file->get_userid() != $USER->id)) {
            continue;
        }
        $preset = new stdClass;
        $preset->path = $file->get_filepath();
        $preset->name = trim($preset->path, '/');
        $preset->shortname = $preset->name;
        $preset->userid = $file->get_userid();
        $preset->id = $file->get_id();
        $preset->storedfile = $file;
        $presets[] = $preset;
    }
    return $presets;
}

/**
 * Deletes a saved preset.
 *
 * @param string $name
 * @return bool
 */
function idea_delete_site_preset($name) {
    $fs = get_file_storage();

    $files = $fs->get_directory_files(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA, 0, '/'.$name.'/');
    if (!empty($files)) {
        foreach ($files as $file) {
            $file->delete();
        }
    }

    $dir = $fs->get_file(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA, 0, '/'.$name.'/', '.');
    if (!empty($dir)) {
        $dir->delete();
    }
    return true;
}

/**
 * Prints the heads for a page
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $idea
 * @param string $currenttab
 */
function idea_print_header($course, $cm, $idea, $currenttab='') {

    global $CFG, $displaynoticegood, $displaynoticebad, $OUTPUT, $PAGE;

    $PAGE->set_title($idea->name);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($idea->name), 2);
    echo $OUTPUT->box(format_module_intro('idea', $idea, $cm->id), 'generalbox', 'intro');

    // Groups needed for Add entry tab
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    // Print the tabs

    if ($currenttab) {
        include('tabs.php');
    }

    // Print any notices

    if (!empty($displaynoticegood)) {
        echo $OUTPUT->notification($displaynoticegood, 'notifysuccess');    // good (usually green)
    } else if (!empty($displaynoticebad)) {
        echo $OUTPUT->notification($displaynoticebad);                     // bad (usuually red)
    }
}

/**
 * Can user add more entries?
 *
 * @param object $idea
 * @param mixed $currentgroup
 * @param int $groupmode
 * @param stdClass $context
 * @return bool
 */
function idea_user_can_add_entry($idea, $currentgroup, $groupmode, $context = null) {
    global $USER;

    if (empty($context)) {
        $cm = get_coursemodule_from_instance('idea', $idea->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    }

    if (has_capability('mod/idea:manageentries', $context)) {
        // no entry limits apply if user can manage

    } else if (!has_capability('mod/idea:writeentry', $context)) {
        return false;

    } else if (idea_atmaxentries($idea)) {
        return false;
    } else if (idea_in_readonly_period($idea)) {
        // Check whether we're in a read-only period
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return groups_is_member($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Check whether the current user is allowed to manage the given record considering manageentries capability,
 * idea_in_readonly_period() result, ownership (determined by idea_isowner()) and manageapproved setting.
 * @param mixed $record record object or id
 * @param object $idea idea object
 * @param object $context context object
 * @return bool returns true if the user is allowd to edit the entry, false otherwise
 */
function idea_user_can_manage_entry($record, $idea, $context) {
    global $DB;

    if (has_capability('mod/idea:manageentries', $context)) {
        return true;
    }

    // Check whether this activity is read-only at present.
    $readonly = idea_in_readonly_period($idea);

    if (!$readonly) {
        // Get record object from db if just id given like in idea_isowner.
        // ...done before calling idea_isowner() to avoid querying db twice.
        if (!is_object($record)) {
            if (!$record = $DB->get_record('idea_records', array('id' => $record))) {
                return false;
            }
        }
        if (idea_isowner($record)) {
            if ($idea->approval && $record->approved) {
                return $idea->manageapproved == 1;
            } else {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check whether the specified ideabase activity is currently in a read-only period
 *
 * @param object $idea
 * @return bool returns true if the time fields in $idea indicate a read-only period; false otherwise
 */
function idea_in_readonly_period($idea) {
    $now = time();
    if (!$idea->timeviewfrom && !$idea->timeviewto) {
        return false;
    } else if (($idea->timeviewfrom && $now < $idea->timeviewfrom) || ($idea->timeviewto && $now > $idea->timeviewto)) {
        return false;
    }
    return true;
}

/**
 * @return bool
 */
function is_directory_idea_preset($directory) {
    $directory = rtrim($directory, '/\\') . '/';
    $status = file_exists($directory.'singletemplate.html') &&
              file_exists($directory.'listtemplate.html') &&
              file_exists($directory.'listtemplateheader.html') &&
              file_exists($directory.'listtemplatefooter.html') &&
              file_exists($directory.'addtemplate.html') &&
              file_exists($directory.'rsstemplate.html') &&
              file_exists($directory.'rsstitletemplate.html') &&
              file_exists($directory.'csstemplate.css') &&
              file_exists($directory.'jstemplate.js') &&
              file_exists($directory.'preset.xml');

    return $status;
}

/**
 * Abstract class used for idea preset importers
 */
abstract class idea_preset_importer {

    protected $course;
    protected $cm;
    protected $module;
    protected $directory;

    /**
     * Constructor
     *
     * @param stdClass $course
     * @param stdClass $cm
     * @param stdClass $module
     * @param string $directory
     */
    public function __construct($course, $cm, $module, $directory) {
        $this->course = $course;
        $this->cm = $cm;
        $this->module = $module;
        $this->directory = $directory;
    }

    /**
     * Returns the name of the directory the preset is located in
     * @return string
     */
    public function get_directory() {
        return basename($this->directory);
    }

    /**
     * Retreive the contents of a file. That file may either be in a conventional directory of the Moodle file storage
     * @param file_storage $filestorage. should be null if using a conventional directory
     * @param stored_file $fileobj the directory to look in. null if using a conventional directory
     * @param string $dir the directory to look in. null if using the Moodle file storage
     * @param string $filename the name of the file we want
     * @return string the contents of the file or null if the file doesn't exist.
     */
    public function idea_preset_get_file_contents(&$filestorage, &$fileobj, $dir, $filename) {
        if(empty($filestorage) || empty($fileobj)) {
            if (substr($dir, -1)!='/') {
                $dir .= '/';
            }
            if (file_exists($dir.$filename)) {
                return file_get_contents($dir.$filename);
            } else {
                return null;
            }
        } else {
            if ($filestorage->file_exists(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA, 0, $fileobj->get_filepath(), $filename)) {
                $file = $filestorage->get_file(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA, 0, $fileobj->get_filepath(), $filename);
                return $file->get_content();
            } else {
                return null;
            }
        }

    }
    /**
     * Gets the preset settings
     * @global moodle_ideabase $DB
     * @return stdClass
     */
    public function get_preset_settings() {
        global $DB;

        $fs = $fileobj = null;
        if (!is_directory_idea_preset($this->directory)) {
            //maybe the user requested a preset stored in the Moodle file storage

            $fs = get_file_storage();
            $files = $fs->get_area_files(idea_PRESET_CONTEXT, idea_PRESET_COMPONENT, idea_PRESET_FILEAREA);

            //preset name to find will be the final element of the directory
            $explodeddirectory = explode('/', $this->directory);
            $presettofind = end($explodeddirectory);

            //now go through the available files available and see if we can find it
            foreach ($files as $file) {
                if (($file->is_directory() && $file->get_filepath()=='/') || !$file->is_directory()) {
                    continue;
                }
                $presetname = trim($file->get_filepath(), '/');
                if ($presetname==$presettofind) {
                    $this->directory = $presetname;
                    $fileobj = $file;
                }
            }

            if (empty($fileobj)) {
                print_error('invalidpreset', 'idea', '', $this->directory);
            }
        }

        $allowed_settings = array(
            'intro',
            'comments',
            'requiredentries',
            'requiredentriestoview',
            'maxentries',
            'rssarticles',
            'approval',
            'defaultsortdir',
            'defaultsort');

        $result = new stdClass;
        $result->settings = new stdClass;
        $result->importfields = array();
        $result->currentfields = $DB->get_records('idea_fields', array('ideaid'=>$this->module->id));
        if (!$result->currentfields) {
            $result->currentfields = array();
        }


        /* Grab XML */
        $presetxml = $this->idea_preset_get_file_contents($fs, $fileobj, $this->directory,'preset.xml');
        $parsedxml = xmlize($presetxml, 0);

        /* First, do settings. Put in user friendly array. */
        $settingsarray = $parsedxml['preset']['#']['settings'][0]['#'];
        $result->settings = new StdClass();
        foreach ($settingsarray as $setting => $value) {
            if (!is_array($value) || !in_array($setting, $allowed_settings)) {
                // unsupported setting
                continue;
            }
            $result->settings->$setting = $value[0]['#'];
        }

        /* Now work out fields to user friendly array */
        $fieldsarray = $parsedxml['preset']['#']['field'];
        foreach ($fieldsarray as $field) {
            if (!is_array($field)) {
                continue;
            }
            $f = new StdClass();
            foreach ($field['#'] as $param => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $f->$param = $value[0]['#'];
            }
            $f->ideaid = $this->module->id;
            $f->type = clean_param($f->type, PARAM_ALPHA);
            $result->importfields[] = $f;
        }
        /* Now add the HTML templates to the settings array so we can update d */
        $result->settings->singletemplate     = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"singletemplate.html");
        $result->settings->listtemplate       = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplate.html");
        $result->settings->listtemplateheader = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplateheader.html");
        $result->settings->listtemplatefooter = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplatefooter.html");
        $result->settings->addtemplate        = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"addtemplate.html");
        $result->settings->rsstemplate        = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstemplate.html");
        $result->settings->rsstitletemplate   = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstitletemplate.html");
        $result->settings->csstemplate        = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"csstemplate.css");
        $result->settings->jstemplate         = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"jstemplate.js");
        $result->settings->asearchtemplate    = $this->idea_preset_get_file_contents($fs, $fileobj,$this->directory,"asearchtemplate.html");

        $result->settings->instance = $this->module->id;
        return $result;
    }

    /**
     * Import the preset into the given ideabase module
     * @return bool
     */
    function import($overwritesettings) {
        global $DB, $CFG;

        $params = $this->get_preset_settings();
        $settings = $params->settings;
        $newfields = $params->importfields;
        $currentfields = $params->currentfields;
        $preservedfields = array();

        /* Maps fields and makes new ones */
        if (!empty($newfields)) {
            /* We require an injective mapping, and need to know what to protect */
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);
                if ($cid == -1) {
                    continue;
                }
                if (array_key_exists($cid, $preservedfields)){
                    print_error('notinjectivemap', 'idea');
                }
                else $preservedfields[$cid] = true;
            }

            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);

                /* A mapping. Just need to change field params. idea kept. */
                if ($cid != -1 and isset($currentfields[$cid])) {
                    $fieldobject = idea_get_field_from_id($currentfields[$cid]->id, $this->module);
                    foreach ($newfield as $param => $value) {
                        if ($param != "id") {
                            $fieldobject->field->$param = $value;
                        }
                    }
                    unset($fieldobject->field->similarfield);
                    $fieldobject->update_field();
                    unset($fieldobject);
                } else {
                    /* Make a new field */
                    include_once("field/$newfield->type/field.class.php");

                    if (!isset($newfield->description)) {
                        $newfield->description = '';
                    }
                    $classname = 'idea_field_'.$newfield->type;
                    $fieldclass = new $classname($newfield, $this->module);
                    $fieldclass->insert_field();
                    unset($fieldclass);
                }
            }
        }

        /* Get rid of all old unused idea */
        if (!empty($preservedfields)) {
            foreach ($currentfields as $cid => $currentfield) {
                if (!array_key_exists($cid, $preservedfields)) {
                    /* idea not used anymore so wipe! */
                    print "Deleting field $currentfield->name<br />";

                    $id = $currentfield->id;
                    //Why delete existing idea records and related comments/ratings??
                    $DB->delete_records('idea_content', array('fieldid'=>$id));
                    $DB->delete_records('idea_fields', array('id'=>$id));
                }
            }
        }

        // handle special settings here
        if (!empty($settings->defaultsort)) {
            if (is_numeric($settings->defaultsort)) {
                // old broken value
                $settings->defaultsort = 0;
            } else {
                $settings->defaultsort = (int)$DB->get_field('idea_fields', 'id', array('ideaid'=>$this->module->id, 'name'=>$settings->defaultsort));
            }
        } else {
            $settings->defaultsort = 0;
        }

        // do we want to overwrite all current ideabase settings?
        if ($overwritesettings) {
            // all supported settings
            $overwrite = array_keys((array)$settings);
        } else {
            // only templates and sorting
            $overwrite = array('singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter',
                               'addtemplate', 'rsstemplate', 'rsstitletemplate', 'csstemplate', 'jstemplate',
                               'asearchtemplate', 'defaultsortdir', 'defaultsort');
        }

        // now overwrite current idea settings
        foreach ($this->module as $prop=>$unused) {
            if (in_array($prop, $overwrite)) {
                $this->module->$prop = $settings->$prop;
            }
        }

        idea_update_instance($this->module);

        return $this->cleanup();
    }

    /**
     * Any clean up routines should go here
     * @return bool
     */
    public function cleanup() {
        return true;
    }
}

/**
 * idea preset importer for uploaded presets
 */
class idea_preset_upload_importer extends idea_preset_importer {
    public function __construct($course, $cm, $module, $filepath) {
        global $USER;
        if (is_file($filepath)) {
            $fp = get_file_packer();
            if ($fp->extract_to_pathname($filepath, $filepath.'_extracted')) {
                fulldelete($filepath);
            }
            $filepath .= '_extracted';
        }
        parent::__construct($course, $cm, $module, $filepath);
    }
    public function cleanup() {
        return fulldelete($this->directory);
    }
}

/**
 * idea preset importer for existing presets
 */
class idea_preset_existing_importer extends idea_preset_importer {
    protected $userid;
    public function __construct($course, $cm, $module, $fullname) {
        global $USER;
        list($userid, $shortname) = explode('/', $fullname, 2);
        $context = context_module::instance($cm->id);
        if ($userid && ($userid != $USER->id) && !has_capability('mod/idea:manageuserpresets', $context) && !has_capability('mod/idea:viewalluserpresets', $context)) {
           throw new coding_exception('Invalid preset provided');
        }

        $this->userid = $userid;
        $filepath = idea_preset_path($course, $userid, $shortname);
        parent::__construct($course, $cm, $module, $filepath);
    }
    public function get_userid() {
        return $this->userid;
    }
}

/**
 * @global object
 * @global object
 * @param object $course
 * @param int $userid
 * @param string $shortname
 * @return string
 */
function idea_preset_path($course, $userid, $shortname) {
    global $USER, $CFG;

    $context = context_course::instance($course->id);

    $userid = (int)$userid;

    $path = null;
    if ($userid > 0 && ($userid == $USER->id || has_capability('mod/idea:viewalluserpresets', $context))) {
        $path = $CFG->idearoot.'/idea/preset/'.$userid.'/'.$shortname;
    } else if ($userid == 0) {
        $path = $CFG->dirroot.'/mod/idea/preset/'.$shortname;
    } else if ($userid < 0) {
        $path = $CFG->tempdir.'/idea/'.-$userid.'/'.$shortname;
    }

    return $path;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the idea.
 *
 * @param $mform form passed by reference
 */
function idea_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'ideaheader', get_string('modulenameplural', 'idea'));
    $mform->addElement('checkbox', 'reset_data', get_string('deleteallentries','idea'));

    $mform->addElement('checkbox', 'reset_data_notenrolled', get_string('deletenotenrolled', 'idea'));
    $mform->disabledIf('reset_data_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_data_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_data_comments', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function idea_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_data_ratings'=>1, 'reset_data_comments'=>1, 'reset_data_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 *
 * @global object
 * @global object
 * @param int $courseid
 * @param string $type optional type
 */
function idea_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {idea} d, {course_modules} cm, {modules} m
             WHERE m.name='idea' AND m.id=cm.module AND cm.instance=d.id AND d.course=?";

    if ($ideas = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($ideas as $idea) {
            idea_grade_item_update($idea, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * idea responses for course $idea->courseid.
 *
 * @global object
 * @global object
 * @param object $idea the idea submitted from the reset course.
 * @return array status array
 */
function idea_reset_useridea($idea) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'idea');
    $status = array();

    $allrecordssql = "SELECT r.id
                        FROM {idea_records} r
                             INNER JOIN {idea} d ON r.ideaid = d.id
                       WHERE d.course = ?";

    $allideassql = "SELECT d.id
                      FROM {idea} d
                     WHERE d.course=?";

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_idea';
    $ratingdeloptions->ratingarea = 'entry';

    // Set the file storage - may need it to remove files later.
    $fs = get_file_storage();

    // delete entries if requested
    if (!empty($idea->reset_data)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='ideabase_entry'", array($idea->courseid));
        $DB->delete_records_select('idea_content', "recordid IN ($allrecordssql)", array($idea->courseid));
        $DB->delete_records_select('idea_records', "ideaid IN ($allideassql)", array($idea->courseid));

        if ($ideas = $DB->get_records_sql($allideassql, array($idea->courseid))) {
            foreach ($ideas as $ideaid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('idea', $ideaid)) {
                    continue;
                }
                $ideacontext = context_module::instance($cm->id);

                // Delete any files that may exist.
                $fs->delete_area_files($ideacontext->id, 'mod_idea', 'content');

                $ratingdeloptions->contextid = $ideacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($idea->reset_gradebook_grades)) {
            // remove all grades from gradebook
            idea_reset_gradebook($idea->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'idea'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($idea->reset_data_notenrolled)) {
        $recordssql = "SELECT r.id, r.userid, r.ideaid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {idea_records} r
                              JOIN {idea} d ON r.ideaid = d.id
                              LEFT JOIN {user} u ON r.userid = u.id
                        WHERE d.course = ? AND r.userid > 0";

        $course_context = context_course::instance($idea->courseid);
        $notenrolled = array();
        $fields = array();
        $rs = $DB->get_recordset_sql($recordssql, array($idea->courseid));
        foreach ($rs as $record) {
            if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
              or !is_enrolled($course_context, $record->userid)) {
                //delete ratings
                if (!$cm = get_coursemodule_from_instance('idea', $record->ideaid)) {
                    continue;
                }
                $ideacontext = context_module::instance($cm->id);
                $ratingdeloptions->contextid = $ideacontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                // Delete any files that may exist.
                if ($contents = $DB->get_records('idea_content', array('recordid' => $record->id), '', 'id')) {
                    foreach ($contents as $content) {
                        $fs->delete_area_files($ideacontext->id, 'mod_idea', 'content', $content->id);
                    }
                }
                $notenrolled[$record->userid] = true;

                $DB->delete_records('comments', array('itemid' => $record->id, 'commentarea' => 'ideabase_entry'));
                $DB->delete_records('idea_content', array('recordid' => $record->id));
                $DB->delete_records('idea_records', array('id' => $record->id));
            }
        }
        $rs->close();
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'idea'), 'error'=>false);
    }

    // remove all ratings
    if (!empty($idea->reset_data_ratings)) {
        if ($ideas = $DB->get_records_sql($allideassql, array($idea->courseid))) {
            foreach ($ideas as $ideaid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('idea', $ideaid)) {
                    continue;
                }
                $ideacontext = context_module::instance($cm->id);

                $ratingdeloptions->contextid = $ideacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($idea->reset_gradebook_grades)) {
            // remove all grades from gradebook
            idea_reset_gradebook($idea->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($idea->reset_data_comments)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='ideabase_entry'", array($idea->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // updating dates - shift may be negative too
    if ($idea->timeshift) {
        shift_course_mod_dates('idea', array('timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto'), $idea->timeshift, $idea->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function idea_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames', 'moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate', 'moodle/comment:view', 'moodle/comment:post', 'moodle/comment:delete');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function idea_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMMENT:                 return true;

        default: return null;
    }
}
/**
 * @global object
 * @param array $export
 * @param string $delimiter_name
 * @param object $ideabase
 * @param int $count
 * @param bool $return
 * @return string|void
 */
function idea_export_csv($export, $delimiter_name, $ideabase, $count, $return=false) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = $ideabase . '-' . $count . '-record';
    if ($count > 1) {
        $filename .= 's';
    }
    if ($return) {
        return csv_export_writer::print_array($export, $delimiter_name, '"', true);
    } else {
        csv_export_writer::download_array($filename, $export, $delimiter_name);
    }
}

/**
 * @global object
 * @param array $export
 * @param string $ideaname
 * @param int $count
 * @return string
 */
function idea_export_xls($export, $ideaname, $count) {
    global $CFG;
    require_once("$CFG->libdir/excellib.class.php");
    $filename = clean_filename("{$ideaname}-{$count}_record");
    if ($count > 1) {
        $filename .= 's';
    }
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= '.xls';

    $filearg = '-';
    $workbook = new MoodleExcelWorkbook($filearg);
    $workbook->send($filename);
    $worksheet = array();
    $worksheet[0] = $workbook->add_worksheet('');
    $rowno = 0;
    foreach ($export as $row) {
        $colno = 0;
        foreach($row as $col) {
            $worksheet[0]->write($rowno, $colno, $col);
            $colno++;
        }
        $rowno++;
    }
    $workbook->close();
    return $filename;
}

/**
 * @global object
 * @param array $export
 * @param string $ideaname
 * @param int $count
 * @param string
 */
function idea_export_ods($export, $ideaname, $count) {
    global $CFG;
    require_once("$CFG->libdir/odslib.class.php");
    $filename = clean_filename("{$ideaname}-{$count}_record");
    if ($count > 1) {
        $filename .= 's';
    }
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= '.ods';
    $filearg = '-';
    $workbook = new MoodleODSWorkbook($filearg);
    $workbook->send($filename);
    $worksheet = array();
    $worksheet[0] = $workbook->add_worksheet('');
    $rowno = 0;
    foreach ($export as $row) {
        $colno = 0;
        foreach($row as $col) {
            $worksheet[0]->write($rowno, $colno, $col);
            $colno++;
        }
        $rowno++;
    }
    $workbook->close();
    return $filename;
}

/**
 * @global object
 * @param int $ideaid
 * @param array $fields
 * @param array $selectedfields
 * @param int $currentgroup group ID of the current group. This is used for
 * exporting idea while maintaining group divisions.
 * @param object $context the context in which the operation is performed (for capability checks)
 * @param bool $userdetails whether to include the details of the record author
 * @param bool $time whether to include time created/modified
 * @param bool $approval whether to include approval status
 * @return array
 */
function idea_get_exportidea($ideaid, $fields, $selectedfields, $currentgroup=0, $context=null,
                             $userdetails=false, $time=false, $approval=false) {
    global $DB;

    if (is_null($context)) {
        $context = context_system::instance();
    }
    // exporting user idea needs special permission
    $userdetails = $userdetails && has_capability('mod/idea:exportuserinfo', $context);

    $exportidea = array();

    // populate the header in first row of export
    foreach($fields as $key => $field) {
        if (!in_array($field->field->id, $selectedfields)) {
            // ignore values we aren't exporting
            unset($fields[$key]);
        } else {
            $exportidea[0][] = $field->field->name;
        }
    }
    if ($userdetails) {
        $exportidea[0][] = get_string('user');
        $exportidea[0][] = get_string('username');
        $exportidea[0][] = get_string('email');
    }
    if ($time) {
        $exportidea[0][] = get_string('timeadded', 'idea');
        $exportidea[0][] = get_string('timemodified', 'idea');
    }
    if ($approval) {
        $exportidea[0][] = get_string('approved', 'idea');
    }

    $idearecords = $DB->get_records('idea_records', array('ideaid'=>$ideaid));
    ksort($idearecords);
    $line = 1;
    foreach($idearecords as $record) {
        // get content indexed by fieldid
        if ($currentgroup) {
            $select = 'SELECT c.fieldid, c.content, c.content1, c.content2, c.content3, c.content4 FROM {idea_content} c, {idea_records} r WHERE c.recordid = ? AND r.id = c.recordid AND r.groupid = ?';
            $where = array($record->id, $currentgroup);
        } else {
            $select = 'SELECT fieldid, content, content1, content2, content3, content4 FROM {idea_content} WHERE recordid = ?';
            $where = array($record->id);
        }

        if( $content = $DB->get_records_sql($select, $where) ) {
            foreach($fields as $field) {
                $contents = '';
                if(isset($content[$field->field->id])) {
                    $contents = $field->export_text_value($content[$field->field->id]);
                }
                $exportidea[$line][] = $contents;
            }
            if ($userdetails) { // Add user details to the export idea
                $useridea = get_complete_user_data('id', $record->userid);
                $exportidea[$line][] = fullname($useridea);
                $exportidea[$line][] = $useridea->username;
                $exportidea[$line][] = $useridea->email;
            }
            if ($time) { // Add time added / modified
                $exportidea[$line][] = userdate($record->timecreated);
                $exportidea[$line][] = userdate($record->timemodified);
            }
            if ($approval) { // Add approval status
                $exportidea[$line][] = (int) $record->approved;
            }
        }
        $line++;
    }
    $line--;
    return $exportidea;
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Lists all browsable file areas
 *
 * @package  mod_idea
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function idea_get_file_areas($course, $cm, $context) {
    return array('content' => get_string('areacontent', 'mod_idea'));
}

/**
 * File browsing support for idea module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info_stored file_info_stored instance or null if not found
 */
function idea_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    if (!isset($areas[$filearea])) {
        return null;
    }

    if (is_null($itemid)) {
        require_once($CFG->dirroot.'/mod/idea/locallib.php');
        return new idea_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
    }

    if (!$content = $DB->get_record('idea_content', array('id'=>$itemid))) {
        return null;
    }

    if (!$field = $DB->get_record('idea_fields', array('id'=>$content->fieldid))) {
        return null;
    }

    if (!$record = $DB->get_record('idea_records', array('id'=>$content->recordid))) {
        return null;
    }

    if (!$idea = $DB->get_record('idea', array('id'=>$field->ideaid))) {
        return null;
    }

    //check if approved
    if ($idea->approval and !$record->approved and !idea_isowner($record) and !has_capability('mod/idea:approve', $context)) {
        return null;
    }

    // group access
    if ($record->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($record->groupid)) {
                return null;
            }
        }
    }

    $fieldobj = idea_get_field($field, $idea, $cm);

    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!$fieldobj->file_ok($filepath.$filename)) {
        return null;
    }

    $fs = get_file_storage();
    if (!($storedfile = $fs->get_file($context->id, 'mod_idea', $filearea, $itemid, $filepath, $filename))) {
        return null;
    }

    // Checks to see if the user can manage files or is the owner.
    // TODO MDL-33805 - Do not use userid here and move the capability check above.
    if (!has_capability('moodle/course:managefiles', $context) && $storedfile->get_userid() != $USER->id) {
        return null;
    }

    $urlbase = $CFG->wwwroot.'/pluginfile.php';

    return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemid, true, true, false, false);
}

/**
 * Serves the idea attachments. Implements needed access control ;-)
 *
 * @package  mod_idea
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function idea_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'content') {
        $contentid = (int)array_shift($args);

        if (!$content = $DB->get_record('idea_content', array('id'=>$contentid))) {
            return false;
        }

        if (!$field = $DB->get_record('idea_fields', array('id'=>$content->fieldid))) {
            return false;
        }

        if (!$record = $DB->get_record('idea_records', array('id'=>$content->recordid))) {
            return false;
        }

        if (!$idea = $DB->get_record('idea', array('id'=>$field->ideaid))) {
            return false;
        }

        if ($idea->id != $cm->instance) {
            // hacker attempt - context does not match the contentid
            return false;
        }

        //check if approved
        if ($idea->approval and !$record->approved and !idea_isowner($record) and !has_capability('mod/idea:approve', $context)) {
            return false;
        }

        // group access
        if ($record->groupid) {
            $groupmode = groups_get_activity_groupmode($cm, $course);
            if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                if (!groups_is_member($record->groupid)) {
                    return false;
                }
            }
        }

        $fieldobj = idea_get_field($field, $idea, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_idea/content/$content->id/$relativepath";

        if (!$fieldobj->file_ok($relativepath)) {
            return false;
        }

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
    }

    return false;
}


function idea_extend_navigation($navigation, $course, $module, $cm) {
    global $CFG, $OUTPUT, $USER, $DB;

    $rid = optional_param('rid', 0, PARAM_INT);

    $idea = $DB->get_record('idea', array('id'=>$cm->instance));
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

     $numentries = idea_numentries($idea);
    /// Check the number of entries required against the number of entries already made (doesn't apply to teachers)
    if ($idea->requiredentries > 0 && $numentries < $idea->requiredentries && !has_capability('mod/idea:manageentries', context_module::instance($cm->id))) {
        $idea->entriesleft = $idea->requiredentries - $numentries;
        $entriesnode = $navigation->add(get_string('entrieslefttoadd', 'idea', $idea));
        $entriesnode->add_class('note');
    }

    $navigation->add(get_string('list', 'idea'), new moodle_url('/mod/idea/view.php', array('d'=>$cm->instance)));
    if (!empty($rid)) {
        $navigation->add(get_string('single', 'idea'), new moodle_url('/mod/idea/view.php', array('d'=>$cm->instance, 'rid'=>$rid)));
    } else {
        $navigation->add(get_string('single', 'idea'), new moodle_url('/mod/idea/view.php', array('d'=>$cm->instance, 'mode'=>'single')));
    }
    $navigation->add(get_string('search', 'idea'), new moodle_url('/mod/idea/view.php', array('d'=>$cm->instance, 'mode'=>'asearch')));
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $ideanode The node to add module settings to
 */
function idea_extend_settings_navigation(settings_navigation $settings, navigation_node $ideanode) {
    global $PAGE, $DB, $CFG, $USER;

    $idea = $DB->get_record('idea', array("id" => $PAGE->cm->instance));

    $currentgroup = groups_get_activity_group($PAGE->cm);
    $groupmode = groups_get_activity_groupmode($PAGE->cm);

    if (idea_user_can_add_entry($idea, $currentgroup, $groupmode, $PAGE->cm->context)) { // took out participation list here!
        if (empty($editentry)) { //TODO: undefined
            $addstring = get_string('add', 'idea');
        } else {
            $addstring = get_string('editentry', 'idea');
        }
        $ideanode->add($addstring, new moodle_url('/mod/idea/edit.php', array('d'=>$PAGE->cm->instance)));
    }

    if (has_capability(idea_CAP_EXPORT, $PAGE->cm->context)) {
        // The capability required to Export ideabase records is centrally defined in 'lib.php'
        // and should be weaker than those required to edit Templates, Fields and Presets.
        $ideanode->add(get_string('exportentries', 'idea'), new moodle_url('/mod/idea/export.php', array('d'=>$idea->id)));
    }
    if (has_capability('mod/idea:manageentries', $PAGE->cm->context)) {
        $ideanode->add(get_string('importentries', 'idea'), new moodle_url('/mod/idea/import.php', array('d'=>$idea->id)));
    }

    if (has_capability('mod/idea:managetemplates', $PAGE->cm->context)) {
        $currenttab = '';
        if ($currenttab == 'list') {
            $defaultemplate = 'listtemplate';
        } else if ($currenttab == 'add') {
            $defaultemplate = 'addtemplate';
        } else if ($currenttab == 'asearch') {
            $defaultemplate = 'asearchtemplate';
        } else {
            $defaultemplate = 'singletemplate';
        }

        $templates = $ideanode->add(get_string('templates', 'idea'));

        $templatelist = array ('listtemplate', 'singletemplate', 'asearchtemplate', 'addtemplate', 'rsstemplate', 'csstemplate', 'jstemplate');
        foreach ($templatelist as $template) {
            $templates->add(get_string($template, 'idea'), new moodle_url('/mod/idea/templates.php', array('d'=>$idea->id,'mode'=>$template)));
        }

        $ideanode->add(get_string('fields', 'idea'), new moodle_url('/mod/idea/field.php', array('d'=>$idea->id)));
        $ideanode->add(get_string('presets', 'idea'), new moodle_url('/mod/idea/preset.php', array('d'=>$idea->id)));
    }

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->idea_enablerssfeeds) && $idea->rssarticles > 0) {
        require_once("$CFG->libdir/rsslib.php");

        $string = get_string('rsstype','forum');

        $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $USER->id, 'mod_idea', $idea->id));
        $ideanode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));
    }
}

/**
 * Save the ideabase configuration as a preset.
 *
 * @param stdClass $course The course the ideabase module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $idea The ideabase record
 * @param string $path
 * @return bool
 */
function idea_presets_save($course, $cm, $idea, $path) {
    global $USER;
    $fs = get_file_storage();
    $filerecord = new stdClass;
    $filerecord->contextid = idea_PRESET_CONTEXT;
    $filerecord->component = idea_PRESET_COMPONENT;
    $filerecord->filearea = idea_PRESET_FILEAREA;
    $filerecord->itemid = 0;
    $filerecord->filepath = '/'.$path.'/';
    $filerecord->userid = $USER->id;

    $filerecord->filename = 'preset.xml';
    $fs->create_file_from_string($filerecord, idea_presets_generate_xml($course, $cm, $idea));

    $filerecord->filename = 'singletemplate.html';
    $fs->create_file_from_string($filerecord, $idea->singletemplate);

    $filerecord->filename = 'listtemplateheader.html';
    $fs->create_file_from_string($filerecord, $idea->listtemplateheader);

    $filerecord->filename = 'listtemplate.html';
    $fs->create_file_from_string($filerecord, $idea->listtemplate);

    $filerecord->filename = 'listtemplatefooter.html';
    $fs->create_file_from_string($filerecord, $idea->listtemplatefooter);

    $filerecord->filename = 'addtemplate.html';
    $fs->create_file_from_string($filerecord, $idea->addtemplate);

    $filerecord->filename = 'rsstemplate.html';
    $fs->create_file_from_string($filerecord, $idea->rsstemplate);

    $filerecord->filename = 'rsstitletemplate.html';
    $fs->create_file_from_string($filerecord, $idea->rsstitletemplate);

    $filerecord->filename = 'csstemplate.css';
    $fs->create_file_from_string($filerecord, $idea->csstemplate);

    $filerecord->filename = 'jstemplate.js';
    $fs->create_file_from_string($filerecord, $idea->jstemplate);

    $filerecord->filename = 'asearchtemplate.html';
    $fs->create_file_from_string($filerecord, $idea->asearchtemplate);

    return true;
}

/**
 * Generates the XML for the ideabase module provided
 *
 * @global moodle_ideabase $DB
 * @param stdClass $course The course the ideabase module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $idea The ideabase record
 * @return string The XML for the preset
 */
function idea_presets_generate_xml($course, $cm, $idea) {
    global $DB;

    // Assemble "preset.xml":
    $presetxmlidea = "<preset>\n\n";

    // Raw settings are not preprocessed during saving of presets
    $raw_settings = array(
        'intro',
        'comments',
        'requiredentries',
        'requiredentriestoview',
        'maxentries',
        'rssarticles',
        'approval',
        'manageapproved',
        'defaultsortdir'
    );

    $presetxmlidea .= "<settings>\n";
    // First, settings that do not require any conversion
    foreach ($raw_settings as $setting) {
        $presetxmlidea .= "<$setting>" . htmlspecialchars($idea->$setting) . "</$setting>\n";
    }

    // Now specific settings
    if ($idea->defaultsort > 0 && $sortfield = idea_get_field_from_id($idea->defaultsort, $idea)) {
        $presetxmlidea .= '<defaultsort>' . htmlspecialchars($sortfield->field->name) . "</defaultsort>\n";
    } else {
        $presetxmlidea .= "<defaultsort>0</defaultsort>\n";
    }
    $presetxmlidea .= "</settings>\n\n";
    // Now for the fields. Grab all that are non-empty
    $fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id));
    ksort($fields);
    if (!empty($fields)) {
        foreach ($fields as $field) {
            $presetxmlidea .= "<field>\n";
            foreach ($field as $key => $value) {
                if ($value != '' && $key != 'id' && $key != 'ideaid') {
                    $presetxmlidea .= "<$key>" . htmlspecialchars($value) . "</$key>\n";
                }
            }
            $presetxmlidea .= "</field>\n\n";
        }
    }
    $presetxmlidea .= '</preset>';
    return $presetxmlidea;
}

function idea_presets_export($course, $cm, $idea, $tostorage=false) {
    global $CFG, $DB;

    $presetname = clean_filename($idea->name) . '-preset-' . gmdate("Ymd_Hi");
    $exportsubdir = "mod_idea/presetexport/$presetname";
    make_temp_directory($exportsubdir);
    $exportdir = "$CFG->tempdir/$exportsubdir";

    // Assemble "preset.xml":
    $presetxmlidea = idea_presets_generate_xml($course, $cm, $idea);

    // After opening a file in write mode, close it asap
    $presetxmlfile = fopen($exportdir . '/preset.xml', 'w');
    fwrite($presetxmlfile, $presetxmlidea);
    fclose($presetxmlfile);

    // Now write the template files
    $singletemplate = fopen($exportdir . '/singletemplate.html', 'w');
    fwrite($singletemplate, $idea->singletemplate);
    fclose($singletemplate);

    $listtemplateheader = fopen($exportdir . '/listtemplateheader.html', 'w');
    fwrite($listtemplateheader, $idea->listtemplateheader);
    fclose($listtemplateheader);

    $listtemplate = fopen($exportdir . '/listtemplate.html', 'w');
    fwrite($listtemplate, $idea->listtemplate);
    fclose($listtemplate);

    $listtemplatefooter = fopen($exportdir . '/listtemplatefooter.html', 'w');
    fwrite($listtemplatefooter, $idea->listtemplatefooter);
    fclose($listtemplatefooter);

    $addtemplate = fopen($exportdir . '/addtemplate.html', 'w');
    fwrite($addtemplate, $idea->addtemplate);
    fclose($addtemplate);

    $rsstemplate = fopen($exportdir . '/rsstemplate.html', 'w');
    fwrite($rsstemplate, $idea->rsstemplate);
    fclose($rsstemplate);

    $rsstitletemplate = fopen($exportdir . '/rsstitletemplate.html', 'w');
    fwrite($rsstitletemplate, $idea->rsstitletemplate);
    fclose($rsstitletemplate);

    $csstemplate = fopen($exportdir . '/csstemplate.css', 'w');
    fwrite($csstemplate, $idea->csstemplate);
    fclose($csstemplate);

    $jstemplate = fopen($exportdir . '/jstemplate.js', 'w');
    fwrite($jstemplate, $idea->jstemplate);
    fclose($jstemplate);

    $asearchtemplate = fopen($exportdir . '/asearchtemplate.html', 'w');
    fwrite($asearchtemplate, $idea->asearchtemplate);
    fclose($asearchtemplate);

    // Check if all files have been generated
    if (! is_directory_idea_preset($exportdir)) {
        print_error('generateerror', 'idea');
    }

    $filenames = array(
        'preset.xml',
        'singletemplate.html',
        'listtemplateheader.html',
        'listtemplate.html',
        'listtemplatefooter.html',
        'addtemplate.html',
        'rsstemplate.html',
        'rsstitletemplate.html',
        'csstemplate.css',
        'jstemplate.js',
        'asearchtemplate.html'
    );

    $filelist = array();
    foreach ($filenames as $filename) {
        $filelist[$filename] = $exportdir . '/' . $filename;
    }

    $exportfile = $exportdir.'.zip';
    file_exists($exportfile) && unlink($exportfile);

    $fp = get_file_packer('application/zip');
    $fp->archive_to_pathname($filelist, $exportfile);

    foreach ($filelist as $file) {
        unlink($file);
    }
    rmdir($exportdir);

    // Return the full path to the exported preset file:
    return $exportfile;
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @package  mod_idea
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function idea_comment_permissions($comment_param) {
    global $CFG, $DB;
    if (!$record = $DB->get_record('idea_records', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$idea = $DB->get_record('idea', array('id'=>$record->ideaid))) {
        throw new comment_exception('invalidid', 'idea');
    }
    if ($idea->comments) {
        return array('post'=>true, 'view'=>true);
    } else {
        return array('post'=>false, 'view'=>false);
    }
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @package  mod_idea
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function idea_comment_validate($comment_param) {
    global $DB;
    // validate comment area
    if ($comment_param->commentarea != 'ideabase_entry') {
        throw new comment_exception('invalidcommentarea');
    }
    // validate itemid
    if (!$record = $DB->get_record('idea_records', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$idea = $DB->get_record('idea', array('id'=>$record->ideaid))) {
        throw new comment_exception('invalidid', 'idea');
    }
    if (!$course = $DB->get_record('course', array('id'=>$idea->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('idea', $idea->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    if (!$idea->comments) {
        throw new comment_exception('commentsoff', 'idea');
    }
    $context = context_module::instance($cm->id);

    //check if approved
    if ($idea->approval and !$record->approved and !idea_isowner($record) and !has_capability('mod/idea:approve', $context)) {
        throw new comment_exception('notapproved', 'idea');
    }

    // group access
    if ($record->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($record->groupid)) {
                throw new comment_exception('notmemberofgroup');
            }
        }
    }
    // validate context id
    if ($context->id != $comment_param->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // validation for comment deletion
    if (!empty($comment_param->commentid)) {
        if ($comment = $DB->get_record('comments', array('id'=>$comment_param->commentid))) {
            if ($comment->commentarea != 'ideabase_entry') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $comment_param->context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $comment_param->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function idea_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-idea-*'=>get_string('page-mod-idea-x', 'idea'));
    return $module_pagetype;
}

/**
 * Get all of the record ids from a ideabase activity.
 *
 * @param int    $ideaid      The ideaid of the ideabase module.
 * @param object $selectidea  Contains an additional sql statement for the
 *                            where clause for group and approval fields.
 * @param array  $params      Parameters that coincide with the sql statement.
 * @return array $idarray     An array of record ids
 */
function idea_get_all_recordids($ideaid, $selectidea = '', $params = null) {
    global $DB;
    $initsql = 'SELECT r.id
                  FROM {idea_records} r
                 WHERE r.ideaid = :ideaid';
    if ($selectidea != '') {
        $initsql .= $selectidea;
        $params = array_merge(array('ideaid' => $ideaid), $params);
    } else {
        $params = array('ideaid' => $ideaid);
    }
    $initsql .= ' GROUP BY r.id';
    $initrecord = $DB->get_recordset_sql($initsql, $params);
    $idarray = array();
    foreach ($initrecord as $idea) {
        $idarray[] = $idea->id;
    }
    // Close the record set and free up resources.
    $initrecord->close();
    return $idarray;
}

/**
 * Get the ids of all the records that match that advanced search criteria
 * This goes and loops through each criterion one at a time until it either
 * runs out of records or returns a subset of records.
 *
 * @param array $recordids    An array of record ids.
 * @param array $searcharray  Contains information for the advanced search criteria
 * @param int $ideaid         The idea id of the ideabase.
 * @return array $recordids   An array of record ids.
 */
function idea_get_advance_search_ids($recordids, $searcharray, $ideaid) {
    // Check to see if we have any record IDs.
    if (empty($recordids)) {
        // Send back an empty search.
        return array();
    }
    $searchcriteria = array_keys($searcharray);
    // Loop through and reduce the IDs one search criteria at a time.
    foreach ($searchcriteria as $key) {
        $recordids = idea_get_recordids($key, $searcharray, $ideaid, $recordids);
        // If we don't have anymore IDs then stop.
        if (!$recordids) {
            break;
        }
    }
    return $recordids;
}

/**
 * Gets the record IDs given the search criteria
 *
 * @param string $alias       Record alias.
 * @param array $searcharray  Criteria for the search.
 * @param int $ideaid         idea ID for the ideabase
 * @param array $recordids    An array of record IDs.
 * @return array $nestarray   An arry of record IDs
 */
function idea_get_recordids($alias, $searcharray, $ideaid, $recordids) {
    global $DB;

    $nestsearch = $searcharray[$alias];
    // searching for content outside of mdl_idea_content
    if ($alias < 0) {
        $alias = '';
    }
    list($insql, $params) = $DB->get_in_or_equal($recordids, SQL_PARAMS_NAMED);
    $nestselect = 'SELECT c' . $alias . '.recordid
                     FROM {idea_content} c' . $alias . ',
                          {idea_fields} f,
                          {idea_records} r,
                          {user} u ';
    $nestwhere = 'WHERE u.id = r.userid
                    AND f.id = c' . $alias . '.fieldid
                    AND r.id = c' . $alias . '.recordid
                    AND r.ideaid = :ideaid
                    AND c' . $alias .'.recordid ' . $insql . '
                    AND ';

    $params['ideaid'] = $ideaid;
    if (count($nestsearch->params) != 0) {
        $params = array_merge($params, $nestsearch->params);
        $nestsql = $nestselect . $nestwhere . $nestsearch->sql;
    } else {
        $thing = $DB->sql_like($nestsearch->field, ':search1', false);
        $nestsql = $nestselect . $nestwhere . $thing . ' GROUP BY c' . $alias . '.recordid';
        $params['search1'] = "%$nestsearch->idea%";
    }
    $nestrecords = $DB->get_recordset_sql($nestsql, $params);
    $nestarray = array();
    foreach ($nestrecords as $idea) {
        $nestarray[] = $idea->recordid;
    }
    // Close the record set and free up resources.
    $nestrecords->close();
    return $nestarray;
}

/**
 * Returns an array with an sql string for advanced searches and the parameters that go with them.
 *
 * @param int $sort            idea_*
 * @param stdClass $idea       idea module object
 * @param array $recordids     An array of record IDs.
 * @param string $selectidea   Information for the where and select part of the sql statement.
 * @param string $sortorder    Additional sort parameters
 * @return array sqlselect     sqlselect['sql'] has the sql string, sqlselect['params'] contains an array of parameters.
 */
function idea_get_advanced_search_sql($sort, $idea, $recordids, $selectidea, $sortorder) {
    global $DB;

    $namefields = user_picture::fields('u');
    // Remove the id from the string. This already exists in the sql statement.
    $namefields = str_replace('u.id,', '', $namefields);

    if ($sort == 0) {
        $nestselectsql = 'SELECT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . '
                        FROM {idea_content} c,
                             {idea_records} r,
                             {user} u ';
        $groupsql = ' GROUP BY r.id, r.approved, r.timecreated, r.timemodified, r.userid, u.firstname, u.lastname, ' . $namefields;
    } else {
        // Sorting through 'Other' criteria
        if ($sort <= 0) {
            switch ($sort) {
                case idea_LASTNAME:
                    $sortcontentfull = "u.lastname";
                    break;
                case idea_FIRSTNAME:
                    $sortcontentfull = "u.firstname";
                    break;
                case idea_APPROVED:
                    $sortcontentfull = "r.approved";
                    break;
                case idea_TIMEMODIFIED:
                    $sortcontentfull = "r.timemodified";
                    break;
                case idea_TIMEADDED:
                default:
                    $sortcontentfull = "r.timecreated";
            }
        } else {
            $sortfield = idea_get_field_from_id($sort, $idea);
            $sortcontent = $DB->sql_compare_text('c.' . $sortfield->get_sort_field());
            $sortcontentfull = $sortfield->get_sort_sql($sortcontent);
        }

        $nestselectsql = 'SELECT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ',
                                 ' . $sortcontentfull . '
                              AS sortorder
                            FROM {idea_content} c,
                                 {idea_records} r,
                                 {user} u ';
        $groupsql = ' GROUP BY r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ', ' .$sortcontentfull;
    }

    // Default to a standard Where statement if $selectidea is empty.
    if ($selectidea == '') {
        $selectidea = 'WHERE c.recordid = r.id
                         AND r.ideaid = :ideaid
                         AND r.userid = u.id ';
    }

    // Find the field we are sorting on
    if ($sort > 0 or idea_get_field_from_id($sort, $idea)) {
        $selectidea .= ' AND c.fieldid = :sort';
    }

    // If there are no record IDs then return an sql statment that will return no rows.
    if (count($recordids) != 0) {
        list($insql, $inparam) = $DB->get_in_or_equal($recordids, SQL_PARAMS_NAMED);
    } else {
        list($insql, $inparam) = $DB->get_in_or_equal(array('-1'), SQL_PARAMS_NAMED);
    }
    $nestfromsql = $selectidea . ' AND c.recordid ' . $insql . $groupsql;
    $sqlselect['sql'] = "$nestselectsql $nestfromsql $sortorder";
    $sqlselect['params'] = $inparam;
    return $sqlselect;
}

/**
 * Checks to see if the user has permission to delete the preset.
 * @param stdClass $context  Context object.
 * @param stdClass $preset  The preset object that we are checking for deletion.
 * @return bool  Returns true if the user can delete, otherwise false.
 */
function idea_user_can_delete_preset($context, $preset) {
    global $USER;

    if (has_capability('mod/idea:manageuserpresets', $context)) {
        return true;
    } else {
        $candelete = false;
        if ($preset->userid == $USER->id) {
            $candelete = true;
        }
        return $candelete;
    }
}

/**
 * Delete a record entry.
 *
 * @param int $recordid The ID for the record to be deleted.
 * @param object $idea The idea object for this activity.
 * @param int $courseid ID for the current course (for logging).
 * @param int $cmid The course module ID.
 * @return bool True if the record deleted, false if not.
 */
function idea_delete_record($recordid, $idea, $courseid, $cmid) {
    global $DB, $CFG;

    if ($deleterecord = $DB->get_record('idea_records', array('id' => $recordid))) {
        if ($deleterecord->ideaid == $idea->id) {
            if ($contents = $DB->get_records('idea_content', array('recordid' => $deleterecord->id))) {
                foreach ($contents as $content) {
                    if ($field = idea_get_field_from_id($content->fieldid, $idea)) {
                        $field->delete_content($content->recordid);
                    }
                }
                $DB->delete_records('idea_content', array('recordid'=>$deleterecord->id));
                $DB->delete_records('idea_records', array('id'=>$deleterecord->id));

                // Delete cached RSS feeds.
                if (!empty($CFG->enablerssfeeds)) {
                    require_once($CFG->dirroot.'/mod/idea/rsslib.php');
                    idea_rss_delete_file($idea);
                }

                // Trigger an event for deleting this record.
                $event = \mod_idea\event\record_deleted::create(array(
                    'objectid' => $deleterecord->id,
                    'context' => context_module::instance($cmid),
                    'courseid' => $courseid,
                    'other' => array(
                        'ideaid' => $deleterecord->ideaid
                    )
                ));
                $event->add_record_snapshot('idea_records', $deleterecord);
                $event->trigger();

                return true;
            }
        }
    }
    return false;
}

/**
 * Check for required fields, and build a list of fields to be updated in a
 * submission.
 *
 * @param $mod stdClass The current recordid - provided as an optimisation.
 * @param $fields array The field idea
 * @param $idearecord stdClass The submitted idea.
 * @return stdClass containing:
 * * string[] generalnotifications Notifications for the form as a whole.
 * * string[] fieldnotifications Notifications for a specific field.
 * * bool validated Whether the field was validated successfully.
 * * idea_field_base[] fields The field objects to be update.
 */
function idea_process_submission(stdClass $mod, $fields, stdClass $idearecord) {
    $result = new stdClass();

    // Empty form checking - you can't submit an empty form.
    $emptyform = true;
    $requiredfieldsfilled = true;
    $fieldsvalidated = true;

    // Store the notifications.
    $result->generalnotifications = array();
    $result->fieldnotifications = array();

    // Store the instantiated classes as an optimisation when processing the result.
    // This prevents the fields being re-initialised when updating.
    $result->fields = array();

    $submittedidea = array();
    foreach ($idearecord as $fieldname => $fieldvalue) {
        if (strpos($fieldname, '_')) {
            $namearray = explode('_', $fieldname, 3);
            $fieldid = $namearray[1];
            if (!isset($submittedidea[$fieldid])) {
                $submittedidea[$fieldid] = array();
            }
            if (count($namearray) === 2) {
                $subfieldid = 0;
            } else {
                $subfieldid = $namearray[2];
            }

            $fieldidea = new stdClass();
            $fieldidea->fieldname = $fieldname;
            $fieldidea->value = $fieldvalue;
            $submittedidea[$fieldid][$subfieldid] = $fieldidea;
        }
    }

    // Check all form fields which have the required are filled.
    foreach ($fields as $fieldrecord) {
        // Check whether the field has any idea.
        $fieldhascontent = false;

        $field = idea_get_field($fieldrecord, $mod);
        if (isset($submittedidea[$fieldrecord->id])) {
            // Field validation check.
            if (method_exists($field, 'field_validation')) {
                $errormessage = $field->field_validation($submittedidea[$fieldrecord->id]);
                if ($errormessage) {
                    $result->fieldnotifications[$field->field->name][] = $errormessage;
                    $fieldsvalidated = false;
                }
            }
            foreach ($submittedidea[$fieldrecord->id] as $fieldname => $value) {
                if ($field->notemptyfield($value->value, $value->fieldname)) {
                    // The field has content and the form is not empty.
                    $fieldhascontent = true;
                    $emptyform = false;
                }
            }
        }

        // If the field is required, add a notification to that effect.
        if ($field->field->required && !$fieldhascontent) {
            if (!isset($result->fieldnotifications[$field->field->name])) {
                $result->fieldnotifications[$field->field->name] = array();
            }
            $result->fieldnotifications[$field->field->name][] = get_string('errormustsupplyvalue', 'idea');
            $requiredfieldsfilled = false;
        }

        // Update the field.
        if (isset($submittedidea[$fieldrecord->id])) {
            foreach ($submittedidea[$fieldrecord->id] as $value) {
                $result->fields[$value->fieldname] = $field;
            }
        }
    }

    if ($emptyform) {
        // The form is empty.
        $result->generalnotifications[] = get_string('emptyaddform', 'idea');
    }

    $result->validated = $requiredfieldsfilled && !$emptyform && $fieldsvalidated;

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every idea event in the site is checked, else
 * only idea events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @return bool
 */
function idea_refresh_events($courseid = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/idea/locallib.php');

    if ($courseid) {
        if (! $idea = $DB->get_records("idea", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (! $idea = $DB->get_records("idea")) {
            return true;
        }
    }

    foreach ($idea as $datum) {
        idea_set_events($datum);
    }
    return true;
}

/**
 * Fetch the configuration for this ideabase activity.
 *
 * @param   stdClass    $ideabase   The object returned from the ideabase for this instance
 * @param   string      $key        The name of the key to retrieve. If none is supplied, then all configuration is returned
 * @param   mixed       $default    The default value to use if no value was found for the specified key
 * @return  mixed                   The returned value
 */
function idea_get_config($ideabase, $key = null, $default = null) {
    if (!empty($ideabase->config)) {
        $config = json_decode($ideabase->config);
    } else {
        $config = new stdClass();
    }

    if ($key === null) {
        return $config;
    }

    if (property_exists($config, $key)) {
        return $config->$key;
    }
    return $default;
}

/**
 * Update the configuration for this ideabase activity.
 *
 * @param   stdClass    $ideabase   The object returned from the ideabase for this instance
 * @param   string      $key        The name of the key to set
 * @param   mixed       $value      The value to set for the key
 */
function idea_set_config(&$ideabase, $key, $value) {
    // Note: We must pass $ideabase by reference because there may be subsequent calls to update_record and these should
    // not overwrite the configuration just set.
    global $DB;

    $config = idea_get_config($ideabase);

    if (!isset($config->$key) || $config->$key !== $value) {
        $config->$key = $value;
        $ideabase->config = json_encode($config);
        $DB->set_field('idea', 'config', $ideabase->config, ['id' => $ideabase->id]);
    }
}


//todo ranil functions

// This commented due to installation error
/**function isIdeaSelectionOk($courseid, $ideaid, $recordid , $approved ,$nofapprovedideas  ){ 
	global $DB;
	global $USER;
	$coursecontext = context_course::instance($courseid);
	$recorduserid = $DB->get_field('idea_records', 'userid', array('id'=>$recordid), $strictness=IGNORE_MISSING);
	$coursecategory= $DB->get_field('course', 'category', array('id'=> $courseid), $strictness=IGNORE_MISSING);
	//$recordUserRoleId="";



	if ($approved==1) {
		return  "The idea has already selected";
	} elseif ((getrecordUserRoleId($recorduserid,$coursecontext))==5 AND $nofapprovedideas >=1 )  {
		return "Already you have a selected idea: Contact coordinator if you want to change";
	} elseif ($recorduserid==$USER->id) {
		return "You are the author of this Idea";
	} elseif ((getuserroleid($USER->id, $coursecontext)) == (getrecordUserRoleId( $recorduserid , $coursecontext ))){ // Check user types of user and idea publisher

		return setrecordusertype(getrecordUserRoleId( $recorduserid , $coursecontext ));
	} else {
		return "ok";
	}




}
*/

//TODO check numberof projects

function get_no_projects_per_user( $category ,  $checkuserid ){

	$catcourses = coursecat::get($category)->get_courses();

		$usercount = 0;
		
		foreach($catcourses as $acourse) { //for all cources in the category
			$cContext = context_course::instance($acourse->id);

			if ($roles = get_user_roles($cContext,$checkuserid)) {
				foreach ($roles as $role) {
					$userroleid = $role->roleid; //role id of the user on the course
				}
				if($userroleid==5){
					$usercount++;
				}
			}
		}
		
	return $usercount;
}







function ideagetcustomrolename($anyUserRoleId) {
	global $USER;
	if ($anyUserRoleId==4 || $anyUserRoleId==3 || $anyUserRoleId==1 || is_siteadmin($USER->id)) {
		return "Teacher";
	}elseif ($anyUserRoleId==5){
		return "Student";
	}
}


function ideagetanyuserroleid($coursecontext,$anyuserid){
	if ($roles = get_user_roles($coursecontext,$anyuserid)) {
		foreach ($roles as $role) {
			$anyuserRoleId = $role->roleid; //role id of the user on the course
		}
		return  $anyuserRoleId;
	}
}

