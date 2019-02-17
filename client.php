<?php
# REMINDER: we're going to want this to be only accessible by the device in question.

include 'main.php';
include 'client_functions.php';

### TODO find pernanent location for update matters ###

shell_exec("git fetch github");

###

$client = wallet::init();
loadView('main',[
	'client' => $client
]);
?>