<?php
// This file keeps track of upgrades to
// the idea module
//
// Sometimes, changes between versions involve
// alterations to ideabase structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be ideabase-neutral,
// using the methods of ideabase_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_idea_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.8.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2015030900) {
        // Define field required to be added to idea_fields.
        $table = new xmldb_table('idea_fields');
        $field = new xmldb_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'description');

        // Conditionally launch add field required.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2015030900, 'idea');
    }

    // Moodle v2.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2015092200) {

        // Define field manageapproved to be added to idea.
        $table = new xmldb_table('idea');
        $field = new xmldb_field('manageapproved', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'approval');

        // Conditionally launch add field manageapproved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // idea savepoint reached.
        upgrade_mod_savepoint(true, 2015092200, 'idea');
    }

    // Moodle v3.0.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2016030300) {

        // Define field timemodified to be added to idea.
        $table = new xmldb_table('idea');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'notification');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // idea savepoint reached.
        upgrade_mod_savepoint(true, 2016030300, 'idea');
    }


    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2016090600) {

        // Define field config to be added to idea.
        $table = new xmldb_table('idea');
        $field = new xmldb_field('config', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

        // Conditionally launch add field config.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // idea savepoint reached.
        upgrade_mod_savepoint(true, 2016090600, 'idea');
    }

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
