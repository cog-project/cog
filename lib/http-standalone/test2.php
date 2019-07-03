<?php
require '../PHPWebserver/vendor/autoload.php';
require '../future/future.php';

use ClanCats\Station\PHPServer\Server; 
use ClanCats\Station\PHPServer\Request;
use ClanCats\Station\PHPServer\Response;

chdir(dirname(__FILE__) . '/../../');

array_shift($argv);

if (empty($argv)) {
	$port = 80;
} else {
	$port = array_shift($argv);
}

$server = new Server('127.0.0.1', $port);

$server->listen(function(Request $request, $client) {

	echo $request->method() . ' ' . $request->uri() . "\n";
	print_r($request);

	$post = [];
	if($request->header("Expect") == '100-continue') {
	  $resp = new Response("",100);
	  socket_write($client,$resp->__toString());
	  $post = Request::withHeaderString(socket_read($client,/*65536*/5242880));
	} else {
	  $post = $request->post;
	}

	$uri = $request->uri();

	# we're going to want to restrict access to /client based on where the request is coming from

        if(!is_null($post)) {
          $post = escapeshellarg(json_encode($post));
        }

        if(preg_match("/\.php$/",$uri)) {
	  $cmd = "php ".getcwd()."{$uri}".($post ? " --_POST={$post}" : "");
          echo "$cmd\n";
          $res = shell_exec($cmd); #todo: $_POST, $_FILE, etc.
        } else {
	  $cmd = "cat ".getcwd()."{$uri}";
          echo "$cmd\n";
          $res = shell_exec($cmd);
        }
		
	return new Response($res);
});

?>