<?php
function _req($_uri,$post = null) {
  
  if(preg_match("/\.php$/",$_uri)) {
    echo "php {$_uri}".($post ? "--_POST={$post}" : "")."\n";
    $res = shell_exec("php {$_uri}".($post ? "--_POST={$post}" : "")); #todo: $_POST, $_FILE, etc.
  } else {
    echo "cat {$_uri}\n";
    $res = shell_exec("cat {$_uri}");
  }
  echo "$res\n";
  return $res;
}

require '../PHPWebserver/vendor/autoload.php';
require '../future/future.php';

use ClanCats\Station\PHPServer\Server; 
use ClanCats\Station\PHPServer\Request;
use ClanCats\Station\PHPServer\Response;

chdir(dirname(__FILE__) . '/../../');

array_shift($argv);

if (empty($argv)) {
	$port = 81;
} else {
	$port = array_shift($argv);
}

$server = new Server('127.0.0.1', $port);

$server->listen(function(Request $request, $client) {
	echo $request->method() . ' ' . $request->uri() . "\n";

	$post = [];
	if($request->header("Expect") == '100-continue') {
	  $resp = new Response('go ahead',100);
	  socket_write($client,$resp->__toString());
	  $post = Request::withHeaderString(socket_read($client,65536));
	} else {
	  $post = $request->post;
	}
	print_r($post);

	$uri = $request->uri();

	# we're going to want to restrict access to /client based on where the request is coming from
	if($uri == '/client') {
	    $res = _req('client.php',escapeshellarg(json_encode($post)));
	} elseif ($uri == '/server') {
	    $res = _req('server.php',escapeshellarg(json_encode($post)));
	} else {
	    $res = _req(getcwd() . $uri);
	}
	
	return new Response($res);
});

?>