<?php

//defined('MOODLE_INTERNAL') || die();
//require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

// get the q parameter from URL
$dataid = $_GET["dataid"];
$recordid = $_GET["recordid"];
$ideauser = $_GET["ideauser"];
$ideatitle = $_GET["ideatitle"];
$ideaownerid = $_GET["ideaownerid"];

//$data = array ( 'shortname' => $ideatitle , 'fullname' => $ideatitle , 'category' =>'1');
//$data = array ( $ideatitle , $ideatitle , '1');
//$data -> shortname = $ideatitle;
//$data -> fullname = $ideatitle;
//$data -> category  = '1';

/**$data = array(
		'shortname' => $ideatitle,
		'fullname' => $ideatitle,
		'category' => '1');
*/

//create_course($data);

echo $hint === "" ? "no suggestion" : $recordid.$dataid.$ideatitle.$ideaownerid;


