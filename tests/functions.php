<?php
function emit($message,$verbose = false) {
	if($verbose && (!defined('VERBOSE') || !VERBOSE)) return;

	$str = "[".date("Y-m-d H:i:s")."]\t";
	if(is_array($message) || is_object($message)) {
		$str .= print_r($message,1);
	} else {
		$str .= $message;
	}
	echo "$str\n";
}
?>