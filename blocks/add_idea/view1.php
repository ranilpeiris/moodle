<?php

require_once('../../config.php');
require_once('add_idea_form.php');

global $DB;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);


if (!$course = $DB->get_record('course', array('id' => $courseid))) {
	print_error('invalidcourse', 'block_add_idea', $courseid);
}

require_login($course);

$add_idea = new add_idea_form();
$add_idea->display();
?>