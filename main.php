<?php
if(isset($argv)) { # cli
  $opts = getopt('',[
    '_POST::',
    '_GET::',
  ]);
  foreach($opts as $k => $v) {
    $$k = json_decode($v,true);
    if($k == '_POST' || $k == '_GET') {
      foreach($$k as $kk => $vv) {
        $_REQUEST[$kk] = $vv;
      }
    }
  }
} else { # apache
  
}

#set_time_limit(5);
require dirname(__FILE__).'/block.class.php';
require dirname(__FILE__).'/cog.class.php';
require dirname(__FILE__).'/contract.class.php';
require dirname(__FILE__).'/dbClient.class.php';
require dirname(__FILE__).'/node.class.php';
require dirname(__FILE__).'/wallet.class.php';
require dirname(__FILE__).'/request.class.php';
require dirname(__FILE__).'/session.class.php';
require dirname(__FILE__).'/party.class.php';
require dirname(__FILE__).'/network.class.php';
require dirname(__FILE__).'/mongointerface.class.php';
require dirname(__FILE__).'/request_validator.class.php';
require dirname(__FILE__).'/type_hinting.class.php';
require dirname(__FILE__).'/lib/curl-emulator/curlemu.php';
require dirname(__FILE__).'/lib/flat/flat.class.php';
require dirname(__FILE__).'/lib/PHPSandbox/vendor/autoload.php';
require dirname(__FILE__).'/lib/future/future.php';

#use \PHPSandbox\PHPSandbox;

$reqExt = [
];

$success = true;

foreach($reqExt as $ext) {
  if(!extension_loaded($ext)) {
    $success = false;
    cog::emit("PHP extension not installed: {$ext}");
  }
}
if(!$success) die;
?>
