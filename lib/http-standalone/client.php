<?php 
$fp = fopen($port_file, 'r'); 
$port = fgets($fp, 1024); 
fclose($fp); 
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); 
socket_connect($sock, '127.0.0.1', $port); 
socket_close($sock); 
?>