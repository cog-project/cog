<?php
function sig_handler($signo) {
  switch ($signo) {
      case SIGTERM:
      $sock = $GLOBALS['sock'];
      $linger = array ('l_linger' => 0, 'l_onoff' => 1);
      socket_set_option($sock, SOL_SOCKET, SO_LINGER, $linger);
      socket_shutdown($sock, 2);
      socket_close($sock);
      exit;
      break;
  }
}

$port = 80;
$backlog = 16;
$port_file = 'test';

$sock = socket_create_listen($port,$backlog);
socket_getsockname($sock,$addr,$port);
print "Server Listening on $addr:$port\n"; 
$fp = fopen($port_file, 'w'); 
fwrite($fp, $port); 
fclose($fp); 
while($c = socket_accept($sock)) {
   /* do something useful */ 
   socket_getpeername($c, $raddr, $rport); 
   print "Received Connection from $raddr:$rport\n";

    $line = trim(socket_read($c,65536));
    echo "$line\n";
    
    $mes = "whywhywhy";

$mes = $line;

    $len = strlen($mes);

    socket_write($c,$mes,$len);
    socket_close($c);
} 
?>