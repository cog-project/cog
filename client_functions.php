<?php
function loadView($f,$args = []) {
	$f = dirname(__FILE__)."/views/$f.php";
	foreach($args as $k => $v) {
		$$k = $v;
	}
	require($f);
}

function renderElement($f,$args = []) {
	$f = dirname(__FILE__)."/elements/$f.php";
	foreach($args as $k => $v) {
		$$k = $v;
	}
	require($f);
}
?>