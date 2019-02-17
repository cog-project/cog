<?php
// boilerplate for cog smart contracts

require 'lib/PHPSandbox/vendor/autoload.php';
require 'lib/future/future.php';

use \PHPSandbox\PHPSandbox;

function run_contract($contract,$context,$block,$timeout = 1) {
	$input = "<?php
	  \$context = json_decode(base64_decode('".base64_encode(json_encode($context))."'));
	  \$block = json_decode(base64_decode('".base64_encode(json_encode($block))."'));
	?>";


	$x = new PHPSandbox();
	$x->setOptions([
//	  'time_limit' => $timeout+1,
	  'capture_output' => true,
	  'allow_closures' => true,
	]);

	$x->whitelistFunc([
	  'base64_decode',
	  'base64_encode',
	  'date',
	  'json_decode',
	  'json_encode',
	  'microtime',
	  'print_r',
	  'sha1',
	]);

	$x->whitelistConst([
	  'JSON_PRETTY_PRINT',
	]);

	$x->whitelistType([
	  'Exception'
	]);

	try {
	  $res = $x->execute("$input\n$contract");
	  $json = json_decode($res,JSON_PRETTY_PRINT);
	  if(!is_array($json)) {
	    throw new Exception("cog: Failed to parse JSON.\n\nOutput:\n".print_r($res,1));
	  }
	  $success = true;
	  $message = null;
	} catch (Exception $e) {
	  $success = false;
	  $json = null;
	  $message = "{$e->getMessage()}\n{$e->getTraceAsString()}";
	}

	$out = ['result' => (int)$success, 'output' => $json, 'message' => $message];
	return $out;
}

$contract = file_get_contents(dirname(__FILE__).'/sandbox_contract.txt');

$context = [
  'timeStamp' => date('Y-m-d H:i:s')
];

$block = [
  'hash' => sha1(null)
];

$timeout = 1;

$proc = future::start('run_contract',[$contract,$context,$block,$timeout]);
$start_time = microtime(true);
try {
	while(1) {
		$current_time = microtime(true);
		$check = future::check($proc);
		if(!$check) {
			$out = future::wait($proc);
			break;
		}
		$elapsed = $current_time - $start_time;
		if($elapsed > 1) {
			$status = 
			posix_kill($proc[0],SIGILL);
			throw new Exception('Execution timeout.');
		}
		usleep(1000);
	}
} catch (Exception $e) {
	  $message = "{$e->getMessage()}\n{$e->getTraceAsString()}";
	$out = ['result' => 0, 'output' => null, 'message' => $message];
}

print_r($out);
?>

