<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

    require_once(__DIR__ . '/../../config.php');
    require_once($CFG->dirroot . '/mod/idea/lib.php');
    require_once($CFG->libdir . '/rsslib.php');
    require_once($CFG->libdir . '/completionlib.php');

///TODO One of these is necessary!
    $id = optional_param('id', 0, PARAM_INT);  // course module id
    $d = optional_param('d', 0, PARAM_INT);   // ideabase id
    $rid = optional_param('rid', 0, PARAM_INT);    //record id
    $mode = optional_param('mode', '', PARAM_ALPHA);    // Force the browse mode  ('single')
    $filter = optional_param('filter', 0, PARAM_BOOL);
    // search filter will only be applied when $filter is true

    $edit = optional_param('edit', -1, PARAM_BOOL);
    $page = optional_param('page', 0, PARAM_INT);
/// These can be added to perform an action on a record
    $approve = optional_param('approve', 0, PARAM_INT);    //approval recordid
    $disapprove = optional_param('disapprove', 0, PARAM_INT);    // disapproval recordid
    $delete = optional_param('delete', 0, PARAM_INT);    //delete recordid
    $multidelete = optional_param_array('delcheck', null, PARAM_INT);
    $serialdelete = optional_param('serialdelete', null, PARAM_RAW);
    
    //ranil added
    
    $selectedidea = optional_param('selectedidea', 0, PARAM_INT);
    $avilableidea = optional_param('avilableidea', 0 , PARAM_INT);
    $supervisoridea = optional_param('supervisoridea', 0, PARAM_INT);
    $studentidea = optional_param('studentidea', 0, PARAM_INT);
    $myideas = optional_param('myideas', 0, PARAM_INT);
    
    
    
    

    if ($id) {
        if (! $cm = get_coursemodule_from_id('idea', $id)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
            print_error('coursemisconf');
        }
        if (! $idea = $DB->get_record('idea', array('id'=>$cm->instance))) {
            print_error('invalidcoursemodule');
        }
        $record = NULL;

    } else if ($rid) {
        if (! $record = $DB->get_record('idea_records', array('id'=>$rid))) {
            print_error('invalidrecord', 'idea');
        }
        if (! $idea = $DB->get_record('idea', array('id'=>$record->ideaid))) {
            print_error('invalidid', 'idea');
        }
        if (! $course = $DB->get_record('course', array('id'=>$idea->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance('idea', $idea->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    } else {   // We must have $d
        if (! $idea = $DB->get_record('idea', array('id'=>$d))) {
            print_error('invalidid', 'idea');
        }
        if (! $course = $DB->get_record('course', array('id'=>$idea->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance('idea', $idea->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
        $record = NULL;
    }

    require_course_login($course, true, $cm);

    require_once($CFG->dirroot . '/comment/lib.php');
    comment::init();

    $context = context_module::instance($cm->id);
    require_capability('mod/idea:viewentry', $context);

/// If we have an empty ideabase then redirect because this page is useless without idea
    if (has_capability('mod/idea:managetemplates', $context)) {
        if (!$DB->record_exists('idea_fields', array('ideaid'=>$idea->id))) {      // Brand new ideabase!
            redirect($CFG->wwwroot.'/mod/idea/field.php?d='.$idea->id);  // Redirect to field entry
        }
    }


/// Check further parameters that set browsing preferences
    if (!isset($SESSION->ideaprefs)) {
        $SESSION->ideaprefs = array();
    }
    
    //TODO ranil added 
    if ($selectedidea == 1 || $avilableidea == 1 || $supervisoridea == 1 ||  $studentidea ==1 || $myideas ==1){
    	$SESSION->ideaprefs = array();
    }
    //end ranil
    
    
    if (!isset($SESSION->ideaprefs[$idea->id])) {
        $SESSION->ideaprefs[$idea->id] = array();
        $SESSION->ideaprefs[$idea->id]['search'] = '';
        $SESSION->ideaprefs[$idea->id]['search_array'] = array();
        $SESSION->ideaprefs[$idea->id]['sort'] = $idea->defaultsort;
        $SESSION->ideaprefs[$idea->id]['advanced'] = 0;
        $SESSION->ideaprefs[$idea->id]['order'] = ($idea->defaultsortdir == 0) ? 'ASC' : 'DESC';
    }

    // reset advanced form
    if (!is_null(optional_param('resetadv', null, PARAM_RAW))) {
        $SESSION->ideaprefs[$idea->id]['search_array'] = array();
        // we need the redirect to cleanup the form state properly
        redirect("view.php?id=$cm->id&amp;mode=$mode&amp;search=&amp;advanced=1");
    }

    $advanced = optional_param('advanced', -1, PARAM_INT);
    if ($advanced == -1) {
        $advanced = $SESSION->ideaprefs[$idea->id]['advanced'];
    } else {
        if (!$advanced) {
            // explicitly switched to normal mode - discard all advanced search settings
            $SESSION->ideaprefs[$idea->id]['search_array'] = array();
        }
        $SESSION->ideaprefs[$idea->id]['advanced'] = $advanced;
    }

    $search_array = $SESSION->ideaprefs[$idea->id]['search_array'];

    if (!empty($advanced)) {
        $search = '';
        $vals = array();
        $fields = $DB->get_records('idea_fields', array('ideaid'=>$idea->id));

        //Added to ammend paging error. This error would occur when attempting to go from one page of advanced
        //search results to another.  All fields were reset in the page transfer, and there was no way of determining
        //whether or not the user reset them.  This would cause a blank search to execute whenever the user attempted
        //to see any page of results past the first.
        //This fix works as follows:
        //$paging flag is set to false when page 0 of the advanced search results is viewed for the first time.
        //Viewing any page of results after page 0 passes the false $paging flag though the URL (see line 523) and the
        //execution falls through to the second condition below, allowing paging to be set to true.
        //Paging remains true and keeps getting passed though the URL until a new search is performed
        //(even if page 0 is revisited).
        //A false $paging flag generates advanced search results based on the fields input by the user.
        //A true $paging flag generates davanced search results from the $SESSION global.

        $paging = optional_param('paging', NULL, PARAM_BOOL);
        if($page == 0 && !isset($paging)) {
            $paging = false;
        }
        else {
            $paging = true;
        }
        if (!empty($fields)) {
            foreach($fields as $field) {
                $searchfield = idea_get_field_from_id($field->id, $idea);
                //Get field idea to build search sql with.  If paging is false, get from user.
                //If paging is true, get idea from $search_array which is obtained from the $SESSION (see line 116).
                if(!$paging) {
                    $val = $searchfield->parse_search_field();
                } else {
                    //Set value from session if there is a value @ the required index.
                    if (isset($search_array[$field->id])) {
                        $val = $search_array[$field->id]->idea;
                    } else {             //If there is not an entry @ the required index, set value to blank.
                        $val = '';
                    }
                }
                if (!empty($val)) {
                    $search_array[$field->id] = new stdClass();
                    list($search_array[$field->id]->sql, $search_array[$field->id]->params) = $searchfield->generate_sql('c'.$field->id, $val);
                    $search_array[$field->id]->idea = $val;
                    $vals[] = $val;
                } else {
                    // clear it out
                    unset($search_array[$field->id]);
                }
            }
        }

        if (!$paging) {
            // name searching
            $fn = optional_param('u_fn', '', PARAM_NOTAGS);
            $ln = optional_param('u_ln', '', PARAM_NOTAGS);
        } else {
            $fn = isset($search_array[idea_FIRSTNAME]) ? $search_array[idea_FIRSTNAME]->idea : '';
            $ln = isset($search_array[idea_LASTNAME]) ? $search_array[idea_LASTNAME]->idea : '';
        }
        if (!empty($fn)) {
            $search_array[idea_FIRSTNAME] = new stdClass();
            $search_array[idea_FIRSTNAME]->sql    = '';
            $search_array[idea_FIRSTNAME]->params = array();
            $search_array[idea_FIRSTNAME]->field  = 'u.firstname';
            $search_array[idea_FIRSTNAME]->idea   = $fn;
            $vals[] = $fn;
        } else {
            unset($search_array[idea_FIRSTNAME]);
        }
        if (!empty($ln)) {
            $search_array[idea_LASTNAME] = new stdClass();
            $search_array[idea_LASTNAME]->sql     = '';
            $search_array[idea_LASTNAME]->params = array();
            $search_array[idea_LASTNAME]->field   = 'u.lastname';
            $search_array[idea_LASTNAME]->idea    = $ln;
            $vals[] = $ln;
        } else {
            unset($search_array[idea_LASTNAME]);
        }

        $SESSION->ideaprefs[$idea->id]['search_array'] = $search_array;     // Make it sticky

        // in case we want to switch to simple search later - there might be multiple values there ;-)
        if ($vals) {
            $val = reset($vals);
            if (is_string($val)) {
                $search = $val;
            }
        }

    } else {
        $search = optional_param('search', $SESSION->ideaprefs[$idea->id]['search'], PARAM_NOTAGS);
        //Paging variable not used for standard search. Set it to null.
        $paging = NULL;
    }

    // Disable search filters if $filter is not true:
    if (! $filter) {
        $search = '';
    }

    if (core_text::strlen($search) < 2) {
        $search = '';
    }
    $SESSION->ideaprefs[$idea->id]['search'] = $search;   // Make it sticky

    $sort = optional_param('sort', $SESSION->ideaprefs[$idea->id]['sort'], PARAM_INT);
    $SESSION->ideaprefs[$idea->id]['sort'] = $sort;       // Make it sticky

    $order = (optional_param('order', $SESSION->ideaprefs[$idea->id]['order'], PARAM_ALPHA) == 'ASC') ? 'ASC': 'DESC';
    $SESSION->ideaprefs[$idea->id]['order'] = $order;     // Make it sticky


    $oldperpage = get_user_preferences('idea_perpage_'.$idea->id, 10);
    $perpage = optional_param('perpage', $oldperpage, PARAM_INT);

    if ($perpage < 2) {
        $perpage = 2;
    }
    if ($perpage != $oldperpage) {
        set_user_preference('idea_perpage_'.$idea->id, $perpage);
    }

    $params = array(
        'context' => $context,
        'objectid' => $idea->id
    );
    $event = \mod_idea\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('idea', $idea);
    $event->trigger();

    $urlparams = array('d' => $idea->id);
    if ($record) {
        $urlparams['rid'] = $record->id;
    }
    if ($page) {
        $urlparams['page'] = $page;
    }
    if ($mode) {
        $urlparams['mode'] = $mode;
    }
    if ($filter) {
        $urlparams['filter'] = $filter;
    }
// Initialize $PAGE, compute blocks
    $PAGE->set_url('/mod/idea/view.php', $urlparams);

    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $courseshortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

/// RSS and CSS and JS meta
    $meta = '';
    if (!empty($CFG->enablerssfeeds) && !empty($CFG->idea_enablerssfeeds) && $idea->rssarticles > 0) {
        $rsstitle = $courseshortname . ': ' . format_string($idea->name);
        rss_add_http_header($context, 'mod_idea', $idea, $rsstitle);
    }
    if ($idea->csstemplate) {
        $PAGE->requires->css('/mod/idea/css.php?d='.$idea->id);
    }
    if ($idea->jstemplate) {
        $PAGE->requires->js('/mod/idea/js.php?d='.$idea->id, true);
    }

    // Mark as viewed
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

/// Print the page header
    // Note: MDL-19010 there will be further changes to printing header and blocks.
    // The code will be much nicer than this eventually.
    $title = $courseshortname.': ' . format_string($idea->name);

    if ($PAGE->user_allowed_editing()) {
        // Change URL parameter and block display string value depending on whether editing is enabled or not
        if ($PAGE->user_is_editing()) {
            $urlediting = 'off';
            $strediting = get_string('blockseditoff');
        } else {
            $urlediting = 'on';
            $strediting = get_string('blocksediton');
        }
        $url = new moodle_url($CFG->wwwroot.'/mod/idea/view.php', array('id' => $cm->id, 'edit' => $urlediting));
        $PAGE->set_button($OUTPUT->single_button($url, $strediting));
    }

    if ($mode == 'asearch') {
        $PAGE->navbar->add(get_string('search'));
    }

    $PAGE->force_settings_menu();
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    // Check to see if groups are being used here.
    // We need the most up to date current group value. Make sure it is updated at this point.
    $currentgroup = groups_get_activity_group($cm, true);
    $groupmode = groups_get_activity_groupmode($cm);
    $canmanageentries = has_capability('mod/idea:manageentries', $context);
    // If a student is not part of a group and seperate groups is enabled, we don't
    // want them seeing all records.
    if ($currentgroup == 0 && $groupmode == 1 && !$canmanageentries) {
        $canviewallrecords = false;
    } else {
        $canviewallrecords = true;
    }

    // detect entries not approved yet and show hint instead of not found error
    if ($record and $idea->approval and !$record->approved and $record->userid != $USER->id and !$canmanageentries) {
        if (!$currentgroup or $record->groupid == $currentgroup or $record->groupid == 0) {
            print_error('notapproved', 'idea');
        }
    }

    echo $OUTPUT->heading(format_string($idea->name), 2);

    // Do we need to show a link to the RSS feed for the records?
    //this links has been Settings (ideabase activity administration) block
    /*if (!empty($CFG->enablerssfeeds) && !empty($CFG->idea_enablerssfeeds) && $idea->rssarticles > 0) {
        echo '<div style="float:right;">';
        rss_print_link($context->id, $USER->id, 'mod_idea', $idea->id, get_string('rsstype'));
        echo '</div>';
        echo '<div style="clear:both;"></div>';
    }*/

    if ($idea->intro and empty($page) and empty($record) and $mode != 'single') {
        $options = new stdClass();
        $options->noclean = true;
    }
    echo $OUTPUT->box(format_module_intro('idea', $idea, $cm->id), 'generalbox', 'intro');

    $returnurl = $CFG->wwwroot . '/mod/idea/view.php?d='.$idea->id.'&amp;search='.s($search).'&amp;sort='.s($sort).'&amp;order='.s($order).'&amp;';
    groups_print_activity_menu($cm, $returnurl);

/// Delete any requested records

    if ($delete && confirm_sesskey() && (idea_user_can_manage_entry($delete, $idea, $context))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
            if (idea_delete_record($delete, $idea, $course->id, $cm->id)) {
                echo $OUTPUT->notification(get_string('recorddeleted','idea'), 'notifysuccess');
            }
        } else {   // Print a confirmation page
            $allnamefields = user_picture::fields('u');
            // Remove the id from the string. This already exists in the sql statement.
            $allnamefields = str_replace('u.id,', '', $allnamefields);
            $dbparams = array($delete);
            if ($deleterecord = $DB->get_record_sql("SELECT dr.*, $allnamefields
                                                       FROM {idea_records} dr
                                                            JOIN {user} u ON dr.userid = u.id
                                                      WHERE dr.id = ?", $dbparams, MUST_EXIST)) { // Need to check this is valid.
                if ($deleterecord->ideaid == $idea->id) {                       // Must be from this ideabase
                    $deletebutton = new single_button(new moodle_url('/mod/idea/view.php?d='.$idea->id.'&delete='.$delete.'&confirm=1'), get_string('delete'), 'post');
                    echo $OUTPUT->confirm(get_string('confirmdeleterecord','idea'),
                            $deletebutton, 'view.php?d='.$idea->id);

                    $records[] = $deleterecord;
                    echo idea_print_template('singletemplate', $records, $idea, '', 0, true);

                    echo $OUTPUT->footer();
                    exit;
                }
            }
        }
    }


    // Multi-delete.
    if ($serialdelete) {
        $multidelete = json_decode($serialdelete);
    }

    if ($multidelete && confirm_sesskey() && $canmanageentries) {
        if ($confirm = optional_param('confirm', 0, PARAM_INT)) {
            foreach ($multidelete as $value) {
                idea_delete_record($value, $idea, $course->id, $cm->id);
            }
        } else {
            $validrecords = array();
            $recordids = array();
            foreach ($multidelete as $value) {
                $allnamefields = user_picture::fields('u');
                // Remove the id from the string. This already exists in the sql statement.
                $allnamefields = str_replace('u.id,', '', $allnamefields);
                $dbparams = array('id' => $value);
                if ($deleterecord = $DB->get_record_sql("SELECT dr.*, $allnamefields
                                                           FROM {idea_records} dr
                                                           JOIN {user} u ON dr.userid = u.id
                                                          WHERE dr.id = ?", $dbparams)) { // Need to check this is valid.
                    if ($deleterecord->ideaid == $idea->id) {  // Must be from this ideabase.
                        $validrecords[] = $deleterecord;
                        $recordids[] = $deleterecord->id;
                    }
                }
            }
            $serialisedidea = json_encode($recordids);
            $submitactions = array('d' => $idea->id, 'sesskey' => sesskey(), 'confirm' => '1', 'serialdelete' => $serialisedidea);
            $action = new moodle_url('/mod/idea/view.php', $submitactions);
            $cancelurl = new moodle_url('/mod/idea/view.php', array('d' => $idea->id));
            $deletebutton = new single_button($action, get_string('delete'));
            echo $OUTPUT->confirm(get_string('confirmdeleterecords', 'idea'), $deletebutton, $cancelurl);
            echo idea_print_template('listtemplate', $validrecords, $idea, '', 0, false);
            echo $OUTPUT->footer();
            exit;
        }
    }


//if idea activity closed dont let students in
$showactivity = true;
if (!$canmanageentries) {
    $timenow = time();
    if (!empty($idea->timeavailablefrom) && $idea->timeavailablefrom > $timenow) {
        echo $OUTPUT->notification(get_string('notopenyet', 'idea', userdate($idea->timeavailablefrom)));
        $showactivity = false;
    } else if (!empty($idea->timeavailableto) && $timenow > $idea->timeavailableto) {
        echo $OUTPUT->notification(get_string('expired', 'idea', userdate($idea->timeavailableto)));
        $showactivity = false;
    }
}

if ($showactivity) {
    // Print the tabs
    if ($record or $mode == 'single') {
        $currenttab = 'single';
    } elseif($mode == 'asearch') {
        $currenttab = 'asearch';
    }
    else {
        $currenttab = 'list';
    }
    include('tabs.php');

    if ($mode == 'asearch') {
        $maxcount = 0;
        idea_print_preference_form($idea, $perpage, $search, $sort, $order, $search_array, $advanced, $mode);

    } else {
        // Approve or disapprove any requested records
        $params = array(); // named params array

        $approvecap = has_capability('mod/idea:approve', $context);

        if (($approve || $disapprove) && confirm_sesskey() && $approvecap) {
            $newapproved = $approve ? 1 : 0;
            $recordid = $newapproved ? $approve : $disapprove;
            if ($approverecord = $DB->get_record('idea_records', array('id' => $recordid))) {   // Need to check this is valid
                if ($approverecord->ideaid == $idea->id) {                       // Must be from this ideabase
                    $newrecord = new stdClass();
                    $newrecord->id = $approverecord->id;
                    $newrecord->approved = $newapproved;
                    $DB->update_record('idea_records', $newrecord);
                    $msgkey = $newapproved ? 'recordapproved' : 'recorddisapproved';
                    echo $OUTPUT->notification(get_string($msgkey, 'idea'), 'notifysuccess');
                }
            }
        }

         $numentries = idea_numentries($idea);
    /// Check the number of entries required against the number of entries already made (doesn't apply to teachers)
        if ($idea->requiredentries > 0 && $numentries < $idea->requiredentries && !$canmanageentries) {
            $idea->entriesleft = $idea->requiredentries - $numentries;
            $strentrieslefttoadd = get_string('entrieslefttoadd', 'idea', $idea);
            echo $OUTPUT->notification($strentrieslefttoadd);
        }

    /// Check the number of entries required before to view other participant's entries against the number of entries already made (doesn't apply to teachers)
        $requiredentries_allowed = true;
        if ($idea->requiredentriestoview > 0 && $numentries < $idea->requiredentriestoview && !$canmanageentries) {
            $idea->entrieslefttoview = $idea->requiredentriestoview - $numentries;
            $strentrieslefttoaddtoview = get_string('entrieslefttoaddtoview', 'idea', $idea);
            echo $OUTPUT->notification($strentrieslefttoaddtoview);
            $requiredentries_allowed = false;
        }

        // Initialise the first group of params for advanced searches.
        $initialparams   = array();

    /// setup group and approve restrictions
        if (!$approvecap && $idea->approval) {
            if (isloggedin()) {
                $approveselect = ' AND (r.approved=1 OR r.userid=:myid1) ';
                $params['myid1'] = $USER->id;
                $initialparams['myid1'] = $params['myid1'];
            } else {
                $approveselect = ' AND r.approved=1 ';
            }
        } else {
            $approveselect = ' ';
        }

        if ($currentgroup) {
            $groupselect = " AND (r.groupid = :currentgroup OR r.groupid = 0)";
            $params['currentgroup'] = $currentgroup;
            $initialparams['currentgroup'] = $params['currentgroup'];
        } else {
            if ($canviewallrecords) {
                $groupselect = ' ';
            } else {
                // If separate groups are enabled and the user isn't in a group or
                // a teacher, manager, admin etc, then just show them entries for 'All participants'.
                $groupselect = " AND r.groupid = 0";
            }
        }

        // Init some variables to be used by advanced search
        $advsearchselect = '';
        $advwhere        = '';
        $advtables       = '';
        $advparams       = array();
        // This is used for the initial reduction of advanced search results with required entries.
        $entrysql        = '';
        $namefields = user_picture::fields('u');
        // Remove the id from the string. This already exists in the sql statement.
        $namefields = str_replace('u.id,', '', $namefields);

    /// Find the field we are sorting on
        if ($sort <= 0 or !$sortfield = idea_get_field_from_id($sort, $idea)) {

            switch ($sort) {
                case idea_LASTNAME:
                    $ordering = "u.lastname $order, u.firstname $order";
                    break;
                case idea_FIRSTNAME:
                    $ordering = "u.firstname $order, u.lastname $order";
                    break;
                case idea_APPROVED:
                    $ordering = "r.approved $order, r.timecreated $order";
                    break;
                case idea_TIMEMODIFIED:
                    $ordering = "r.timemodified $order";
                    break;
                case idea_TIMEADDED:
                default:
                    $sort     = 0;
                    $ordering = "r.timecreated $order";
            }

            $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields;
            $count = ' COUNT(DISTINCT c.recordid) ';
            $tables = '{idea_content} c,{idea_records} r, {user} u ';
            $where =  'WHERE c.recordid = r.id
                         AND r.ideaid = :ideaid
                         AND r.userid = u.id ';
            $params['ideaid'] = $idea->id;
            $sortorder = " ORDER BY $ordering, r.id $order";
            $searchselect = '';
            
           //TODO ranil section check and select filter options cahnge 
           

            $customfiletrwhere = "";
            
            if ($selectedidea == 1 || $avilableidea == 1 || $supervisoridea == 1 ||  $studentidea ==1 ||$myideas ==1){
            		
            	if($selectedidea == 0 && $avilableidea == 0 && $supervisoridea == 0 &&  $studentidea ==0 && $myideas ==1 ){
            		$customfiletrwhere = "AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id =" . $USER->id . " AND  co.id =". "$course->id";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	} elseif ($selectedidea == 1 && $avilableidea == 0 && $supervisoridea == 0 &&  $studentidea ==0 && $myideas ==1  ){
            		$customfiletrwhere = "AND r.selected = 1  AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id =" . $USER->id . " AND  co.id =". "$course->id";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	} elseif ($selectedidea == 0 && $avilableidea == 1 && $supervisoridea == 0 &&  $studentidea ==0 && $myideas ==1  ){
            		$customfiletrwhere = "AND r.selected = 0  AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id =" . $USER->id . " AND  co.id =". "$course->id";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	} elseif ($selectedidea == 1 && $avilableidea == 0 && $supervisoridea==1 &&  $studentidea==1){
            		$customfiletrwhere = "AND r.selected = 1 ";
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 1 && $supervisoridea==1 &&  $studentidea==1){
            		$customfiletrwhere = "AND r.selected = 0 ";
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 1 && $avilableidea == 1 && $supervisoridea==1 &&  $studentidea==0){
            		$customfiletrwhere = "AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND (ro.id = 4 OR ro.id = 3 OR ro.id = 1)";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 1 && $avilableidea == 1 && $supervisoridea==0 &&  $studentidea==1){
            		$customfiletrwhere = "AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid AND co.id =". "$course->id" ." AND ro.id = 5";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 1 && $avilableidea == 0 && $supervisoridea==1 &&  $studentidea==0){
            		$customfiletrwhere = "AND r.selected = 1 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND (ro.id = 4 OR ro.id = 3 OR ro.id = 1)";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 1 && $avilableidea == 0 && $supervisoridea==0 &&  $studentidea==1){
            		$customfiletrwhere = "AND r.selected = 1 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND ro.id = 5";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 1 && $supervisoridea==1 &&  $studentidea==0){
            		$customfiletrwhere = "AND r.selected = 0 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND (ro.id = 4 OR ro.id = 3 OR ro.id = 1)";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 1 && $supervisoridea==0 &&  $studentidea==1){
            		$customfiletrwhere = "AND r.selected = 0 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND ro.id = 5";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 1 && $avilableidea == 0 && $supervisoridea==0 &&  $studentidea==0){
            		$customfiletrwhere = "AND r.selected = 1 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ."";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 1 && $supervisoridea==0 &&  $studentidea==0){
            		$customfiletrwhere = "AND r.selected = 0 AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ."";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 0 && $supervisoridea==1 &&  $studentidea==0){
            		$customfiletrwhere = " AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND (ro.id = 4 OR ro.id = 3 OR ro.id = 1)";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            
            	}elseif ($selectedidea == 0 && $avilableidea == 0 && $supervisoridea==0 &&  $studentidea==1){
            		$customfiletrwhere = " AND con.instanceid = co.id AND con.id = ra.contextid AND con.contextlevel = 50 AND ra.roleid = ro.id AND u.id = ra.userid  AND  co.id =". "$course->id" ." AND ro.id = 5";
            		$customtables = ", {course} co , {context} con , {role_assignments} ra , {role} ro "  ;
            		$tables = $tables .$customtables;
            		$where = $where . $customfiletrwhere;
            	}
            }
            
            
            
            
            
            
         //TODO ranil section end
            
            
            // If requiredentries is not reached, only show current user's entries
            if (!$requiredentries_allowed) {
                $where .= ' AND u.id = :myid2 ';
                $entrysql = ' AND r.userid = :myid3 ';
                $params['myid2'] = $USER->id;
                $initialparams['myid3'] = $params['myid2'];
            }

            if (!empty($advanced)) {                                                  //If advanced box is checked.
                $i = 0;
                foreach($search_array as $key => $val) {                              //what does $search_array hold?
                    if ($key == idea_FIRSTNAME or $key == idea_LASTNAME) {
                        $i++;
                        $searchselect .= " AND ".$DB->sql_like($val->field, ":search_flname_$i", false);
                        $params['search_flname_'.$i] = "%$val->idea%";
                        continue;
                    }
                    $advtables .= ', {idea_content} c'.$key.' ';
                    $advwhere .= ' AND c'.$key.'.recordid = r.id';
                    $advsearchselect .= ' AND ('.$val->sql.') ';
                    $advparams = array_merge($advparams, $val->params);
                }
            } else if ($search) {
                $searchselect = " AND (".$DB->sql_like('c.content', ':search1', false)."
                                  OR ".$DB->sql_like('u.firstname', ':search2', false)."
                                  OR ".$DB->sql_like('u.lastname', ':search3', false)." ) ";
                $params['search1'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            } else {
                $searchselect = ' ';
            }
            
        } else {

            $sortcontent = $DB->sql_compare_text('c.' . $sortfield->get_sort_field());
            $sortcontentfull = $sortfield->get_sort_sql($sortcontent);

            $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ',
                    ' . $sortcontentfull . ' AS sortorder ';
            $count = ' COUNT(DISTINCT c.recordid) ';
            $tables = '{idea_content} c, {idea_records} r, {user} u ';
            $where =  'WHERE c.recordid = r.id
                         AND r.ideaid = :ideaid
                         AND r.userid = u.id ';
            if (!$advanced) {
                $where .=  'AND c.fieldid = :sort';
            }
            $params['ideaid'] = $idea->id;
            $params['sort'] = $sort;
            $sortorder = ' ORDER BY sortorder '.$order.' , r.id ASC ';
            $searchselect = '';

            // If requiredentries is not reached, only show current user's entries
            if (!$requiredentries_allowed) {
                $where .= ' AND u.id = :myid2';
                $entrysql = ' AND r.userid = :myid3';
                $params['myid2'] = $USER->id;
                $initialparams['myid3'] = $params['myid2'];
            }
            $i = 0;
            if (!empty($advanced)) {                                                  //If advanced box is checked.
                foreach($search_array as $key => $val) {                              //what does $search_array hold?
                    if ($key == idea_FIRSTNAME or $key == idea_LASTNAME) {
                        $i++;
                        $searchselect .= " AND ".$DB->sql_like($val->field, ":search_flname_$i", false);
                        $params['search_flname_'.$i] = "%$val->idea%";
                        continue;
                    }
                    $advtables .= ', {idea_content} c'.$key.' ';
                    $advwhere .= ' AND c'.$key.'.recordid = r.id AND c'.$key.'.fieldid = '.$key;
                    $advsearchselect .= ' AND ('.$val->sql.') ';
                    $advparams = array_merge($advparams, $val->params);
                }
            } else if ($search) {
                $searchselect = " AND (".$DB->sql_like('c.content', ':search1', false)." OR ".$DB->sql_like('u.firstname', ':search2', false)." OR ".$DB->sql_like('u.lastname', ':search3', false)." ) ";
                $params['search1'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            } else {
                $searchselect = ' ';
            }
        }

    /// To actually fetch the records

        $fromsql    = "FROM $tables $advtables $where $advwhere $groupselect $approveselect $searchselect $advsearchselect";
        $allparams  = array_merge($params, $advparams);

        // Provide initial sql statements and parameters to reduce the number of total records.
        $initialselect = $groupselect . $approveselect . $entrysql;

        $recordids = idea_get_all_recordids($idea->id, $initialselect, $initialparams);
        $newrecordids = idea_get_advance_search_ids($recordids, $search_array, $idea->id);
        $totalcount = count($newrecordids);
        $selectidea = $where . $groupselect . $approveselect;

        if (!empty($advanced)) {
            $advancedsearchsql = idea_get_advanced_search_sql($sort, $idea, $newrecordids, $selectidea, $sortorder);
            $sqlselect = $advancedsearchsql['sql'];
            $allparams = array_merge($allparams, $advancedsearchsql['params']);
        } else {
            $sqlselect  = "SELECT $what $fromsql $sortorder";
        }

        /// Work out the paging numbers and counts
        if (empty($searchselect) && empty($advsearchselect)) {
            $maxcount = $totalcount;
        } else {
            $maxcount = count($recordids);
        }

        if ($record) {     // We need to just show one, so where is it in context?
            $nowperpage = 1;
            $mode = 'single';
            $page = 0;
            // TODO MDL-33797 - Reduce this or consider redesigning the paging system.
            if ($allrecordids = $DB->get_fieldset_sql($sqlselect, $allparams)) {
                $page = (int)array_search($record->id, $allrecordids);
                unset($allrecordids);
            }
        } else if ($mode == 'single') {  // We rely on ambient $page settings
            $nowperpage = 1;

        } else {
            $nowperpage = $perpage;
        }

        // Advanced search form doesn't make sense for single (redirects list view).
        if ($maxcount && $mode != 'single') {
            idea_print_preference_form($idea, $perpage, $search, $sort, $order, $search_array, $advanced, $mode);
        }

    /// Get the actual records

        if (!$records = $DB->get_records_sql($sqlselect, $allparams, $page * $nowperpage, $nowperpage)) {
            // Nothing to show!
            if ($record) {         // Something was requested so try to show that at least (bug 5132)
                if ($canmanageentries || empty($idea->approval) ||
                         $record->approved || (isloggedin() && $record->userid == $USER->id)) {
                    if (!$currentgroup || $record->groupid == $currentgroup || $record->groupid == 0) {
                        // OK, we can show this one
                        $records = array($record->id => $record);
                        $totalcount = 1;
                    }
                }
            }
        }

        if (empty($records)) {
            if ($maxcount){
                $a = new stdClass();
                $a->max = $maxcount;
                $a->reseturl = "view.php?id=$cm->id&amp;mode=$mode&amp;search=&amp;advanced=0";
                echo $OUTPUT->notification(get_string('foundnorecords','idea', $a));
            } else {
                echo $OUTPUT->notification(get_string('norecords','idea'));
            }

        } else {
            //  We have some records to print.
            $url = new moodle_url('/mod/idea/view.php', array('d' => $idea->id, 'sesskey' => sesskey()));
            echo html_writer::start_tag('form', array('action' => $url, 'method' => 'post'));

            if ($maxcount != $totalcount) {
                $a = new stdClass();
                $a->num = $totalcount;
                $a->max = $maxcount;
                $a->reseturl = "view.php?id=$cm->id&amp;mode=$mode&amp;search=&amp;advanced=0";
                echo $OUTPUT->notification(get_string('foundrecords', 'idea', $a), 'notifysuccess');
            }

            if ($mode == 'single') { // Single template
                $baseurl = 'view.php?d=' . $idea->id . '&mode=single&';
                if (!empty($search)) {
                    $baseurl .= 'filter=1&';
                }
                if (!empty($page)) {
                    $baseurl .= 'page=' . $page;
                }
                echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl);

                if (empty($idea->singletemplate)){
                    echo $OUTPUT->notification(get_string('nosingletemplate','idea'));
                    idea_generate_default_template($idea, 'singletemplate', 0, false, false);
                }

                //idea_print_template() only adds ratings for singletemplate which is why we're attaching them here
                //attach ratings to idea records
                require_once($CFG->dirroot.'/rating/lib.php');
                if ($idea->assessed != RATING_AGGREGATE_NONE) {
                    $ratingoptions = new stdClass;
                    $ratingoptions->context = $context;
                    $ratingoptions->component = 'mod_idea';
                    $ratingoptions->ratingarea = 'entry';
                    $ratingoptions->items = $records;
                    $ratingoptions->aggregate = $idea->assessed;//the aggregation method
                    $ratingoptions->scaleid = $idea->scale;
                    $ratingoptions->userid = $USER->id;
                    $ratingoptions->returnurl = $CFG->wwwroot.'/mod/idea/'.$baseurl;
                    $ratingoptions->assesstimestart = $idea->assesstimestart;
                    $ratingoptions->assesstimefinish = $idea->assesstimefinish;

                    $rm = new rating_manager();
                    $records = $rm->get_ratings($ratingoptions);
                }

                idea_print_template('singletemplate', $records, $idea, $search, $page, false, new moodle_url($baseurl));

                echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl);

            } else {                                  // List template
                $baseurl = 'view.php?d='.$idea->id.'&amp;';
                //send the advanced flag through the URL so it is remembered while paging.
                $baseurl .= 'advanced='.$advanced.'&amp;';
                if (!empty($search)) {
                    $baseurl .= 'filter=1&amp;';
                }
                //pass variable to allow determining whether or not we are paging through results.
                $baseurl .= 'paging='.$paging.'&amp;';

                echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl);

                if (empty($idea->listtemplate)){
                    echo $OUTPUT->notification(get_string('nolisttemplate','idea'));
                    idea_generate_default_template($idea, 'listtemplate', 0, false, false);
                }
                echo $idea->listtemplateheader;
                idea_print_template('listtemplate', $records, $idea, $search, $page, false, new moodle_url($baseurl));
                echo $idea->listtemplatefooter;

                echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl);
            }

            if ($mode != 'single' && $canmanageentries) {
                echo html_writer::empty_tag('input', array(
                        'type' => 'button',
                        'id' => 'checkall',
                        'value' => get_string('selectall'),
                        'class' => 'btn btn-secondary m-r-1'
                    ));
                echo html_writer::empty_tag('input', array(
                        'type' => 'button',
                        'id' => 'checknone',
                        'value' => get_string('deselectall'),
                        'class' => 'btn btn-secondary m-r-1'
                    ));
                echo html_writer::empty_tag('input', array(
                        'class' => 'form-submit',
                        'type' => 'submit',
                        'value' => get_string('deleteselected'),
                        'class' => 'btn btn-secondary m-r-1'
                    ));

                $module = array('name' => 'mod_idea', 'fullpath' => '/mod/idea/module.js');
                $PAGE->requires->js_init_call('M.mod_idea.init_view', null, false, $module);
            }

            echo html_writer::end_tag('form');
        }
    }

    $search = trim($search);
    if (empty($records)) {
        $records = array();
    }

    // Check to see if we can export records to a portfolio. This is for exporting all records, not just the ones in the search.
    if ($mode == '' && !empty($CFG->enableportfolios) && !empty($records)) {
        $canexport = false;
        // Exportallentries and exportentry are basically the same capability.
        if (has_capability('mod/idea:exportallentries', $context) || has_capability('mod/idea:exportentry', $context)) {
            $canexport = true;
        } else if (has_capability('mod/idea:exportownentry', $context) &&
                $DB->record_exists('idea_records', array('userid' => $USER->id))) {
            $canexport = true;
        }
        if ($canexport) {
            require_once($CFG->libdir . '/portfoliolib.php');
            $button = new portfolio_add_button();
            $button->set_callback_options('idea_portfolio_caller', array('id' => $cm->id), 'mod_idea');
            if (idea_portfolio_caller::has_files($idea)) {
                $button->set_formats(array(PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_LEAP2A)); // No plain html for us.
            }
            echo $button->to_html(PORTFOLIO_ADD_FULL_FORM);
        }
    }
}

echo $OUTPUT->footer();
