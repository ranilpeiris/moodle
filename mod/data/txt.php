
<?php

require_once('config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title("About page");
$PAGE->set_heading("About");
$PAGE->set_url('/mod/data/txt.php');

echo "<script type='text/javascript'>alert('message from call to create course');</script>";
echo $OUTPUT->header();

// Actual content goes here
echo "Hello World";

echo $OUTPUT->footer();


