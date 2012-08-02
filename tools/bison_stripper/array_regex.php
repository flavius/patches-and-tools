<?php
$subject = array('hello',"'",'w',"'");

$t = array_contract($subject);
var_dump($t);

function array_contract($subject) {
	return explode(' ',preg_replace("/' (.) '/",'\'\1\'',implode(' ',$subject)));
}
