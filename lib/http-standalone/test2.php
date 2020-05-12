<?php
require dirname(__FILE__).'/../PHPWebserver/src/Server.php';
require dirname(__FILE__).'/../PHPWebserver/src/Response.php';
require dirname(__FILE__).'/../PHPWebserver/src/Request.php';
require dirname(__FILE__).'/../PHPWebserver/src/Exception.php';
require dirname(__FILE__).'/../future/future.php';

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
	#print_r($request);
	$get = $request->parameters;

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
	if (!empty($get)) {
	  $get = escapeshellarg(json_encode($get));
	}

        if(preg_match("/\.php$/",$uri)) {
	  $cmd = "php ".getcwd()."{$uri}";
	  if ($post) {
	    $cmd .= " --_POST={$post}";
	  }
	  if ($get) {
	    $cmd .= " --_GET={$get}";
	  }
          echo "$cmd\n";
          $res = shell_exec($cmd); #todo: $_POST, $_FILE, etc.
	  if ($uri == "/cog/server.php") {
	    echo "$res\n";
	  }
        } else {
	  $cmd = "cat ".getcwd()."{$uri}";
          echo "$cmd\n";
          $res = shell_exec($cmd);
        }
		
	return new Response($res);
});

?>
