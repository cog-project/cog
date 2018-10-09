<?php
include 'main.php';
$network = new network();
if( ($_SERVER['HTTP_HOST'] == '127.0.0.1' || $_SERVER['HTTP_HOST'] == 'localhost')
    && isset($_REQUEST['environment'])
  ) {
	$environment = $_REQUEST['environment'];
	unset($_REQUEST['environment']);
} else {
	$environment = 'cog';
}

$network->init([
	'db' => $environment,
	'collection' => 'blocks'
]);
$node = new node($network);
$node->processRequest();
?>
