<?php
# REMINDER: we're going to want this to be only accessible by the device in question.

include 'main.php';
include 'client_functions.php';

### TODO find pernanent location for update matters ###

shell_exec("git fetch https://github.com/cog-project/cog.git");

### TODO find pernanent location for environment default ###

$network = new network();
if( ($_SERVER['HTTP_HOST'] == '127.0.0.1' || $_SERVER['HTTP_HOST'] == 'localhost')
    && isset($_REQUEST['environment'])
  ) {
	$environment = $_REQUEST['environment'];
# TODO do something with this
#	unset($_REQUEST['environment']);
} else {
	$environment = 'cog';
}

$network->init([
	'db' => $environment,
	'collection' => 'blocks'
]);

$client = wallet::init();
loadView('main',[
	'client' => $client
]);
?>
