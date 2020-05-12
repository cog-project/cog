<?php
chdir(dirname(__FILE__));

require '../main.php';

require 'traits/mining.php';
require 'traits/network.php';
require 'traits/party.php';
require 'traits/contract.php';
require 'traits/mongo.php';
require 'traits/wallet.php';
require 'traits/node.php';
require 'traits/bug.php';
require 'functions.php';

require 'phpunit/vendor/autoload.php';

// TODO: Convert "fallback" FZPL phpunit test case class into library for reuse elsewhere

if(!class_exists("PHPUnit\Framework\TestCase") && 0){
	error_log("WARNING: phpunit_framework_testcase does not exist.  Creating placeholder.");

	class phpunit_framework_testcase {
		public function assertTrue($bool,$desc = '') {
		}
		public function assertFalse($bool,$desc = '') {
		}
	}
}

class CogTest extends PHPUnit\Framework\TestCase {

	use mongoTests;
	use networkTests;
	use partyTests;
	use contractTests;
	use miningTests;
	use walletTests;
	use nodeTests;
	use bugTests;
	
	// No heavy mining in testing, so we can just use integers.
	protected $counter = 0;
	protected $forks = [];

	function setUp() {	
		$this->testNetwork();
		
		emit("Running CogTest::{$this->getName()}");
		// Initialize Collection
		$this->initialize_collection();
	}

	function tearDown() {
		network::setInstance(null);

		foreach($this->forks as $fork) {
		#	\future::kill($fork);
			passthru("kill -9 {$fork[0]}");
		}
		
		// Delete Collection
		$this->delete_collection();
	}

	function initialize_collection() {
		emit("Initializing db collection",true);
		$this->testMongoCreateCollection("cogTest","blocks");
		$this->testMongoCreateCollection("cogTest","nodes");
		$this->testMongoCreateCollection("cogTest","config");
		$this->testMongoCreateCollection("cogTest","endpoints");
	}

	function delete_collection() {
		emit("Deleting db collection",true);
		$this->testMongoDropCollection("cogTest","blocks");
		$this->testMongoDropCollection("cogTest","nodes");
		$this->testMongoDropCollection("cogTest","config");
		$this->testMongoDropCollection("cogTest","endpoints");
	}
/*
	function key2dec($key) {
		$raw = key::get_binary_key($key);
	}

	function bin2dec($bin) {
		$split = str_split($bin);
		$ord = array_map('ord',$split);
		$b2 = array_map(function($x) { return sprintf('%08d',base_convert($x,10,2));},$ord);
		$b2s = implode('',$b2);
		$b10 = base_convert($b2s,2,10);
		return $b10;
	}
	function str2dec($str) {
		$bin = hex2bin($str);
		$b10 = $this->bin2dec($bin);
		return $b10;
	}

	function testCert() {
		$party = $this->testParty();
		$pub = $party->getPublicKey();
		$priv = $party->getPrivateKey();

		$bin = cog::get_binary_key($priv);
		$pk = $this->bin2dec($bin);

		emit($pk);

		$f1 = function($r,$n) { return pow($r,2) % $n; };

		$f2 = function($r,$s,$c,$n) { return $r*pow($s,$c) % $n; };
	}
*/

	function nodeRequest($params = []) {
		$req = new request(null);
		if(!is_object(cog::$wallet)) {
			$wallet = $this->testWallet();
		}

		if(isset($params['action'])) {
			$req->setAction($params['action']);
		}
		if(isset($params['params'])) {
			$req->setParams($params['params']);
		}
		if(isset($params['header'])) {
			$req->setHeaders($params['headers']);
		}
		$res = $req->submit('localhost','80',false,true);
		$this->assertTrue(strlen($res) > 0,"No response from localhost:80.");
		$assoc = json_decode($res,true);
		$resp = json_decode($assoc['server_response'],true);
		$this->assertTrue(is_array($resp), "Response is not an array:\n".print_r($resp,1));
		$this->assertTrue(isset($resp['result']),"No result field found in response:\n".print_r($assoc,1));
		$this->assertTrue(isset($resp['message']));
		return $resp;
	}
	
	function testServEmpty() {
		$res = $this->nodeRequest();
		$this->assertTrue(isset($res['result']),"No 'result' field found in response:\n".print_r($res,1));
		$this->assertTrue(!$res['result']);
		$this->assertTrue(empty($res['data']));
		$this->assertTrue(!empty($res['message']));
		return $res;
	}

	function testValidateRequest($params = [], $bool = true, $verbose = false /* should be false by default */) {
		$this->testServEmpty();
		if(!empty($params)) {
			$params = array_merge($params,['environment' => 'cogTest']);
			$res = $this->nodeRequest($params);
			if($verbose) {
				emit($res);
			}
			$this->assertTrue(is_array($res),"Result is not an array. (".print_r($res,1).")");
			$this->assertTrue(isset($res['result']),'Result has no result flag.');
			$this->assertTrue(!empty($res['result']) == $bool,"Result flag does not match expected bool '".($bool ? "true" : "false")."'.  Result:".print_r($res,1));
			return $res;
		}
	}

	public function testSend() {
		$a = $this->testWallet();
		$b = $this->testWallet();
		$this->testValidateRequest([
			'action' => 'send'
		],false);
		$this->testValidateRequest([
			'action' => 'send',
			'params' => [
				'inputs' => [
					'from' => $a->getAddress(),
					'to' => $b->getAddress(),
					'amount' => 10,
					'message' => 'cogtest::testsend'
				]
			]
		]);
		$network = $this->testNetwork();
		$db = $network->getDbClient();
		$data = $db->dbQuery("cogTest.blocks",[]);
		$this->assertTrue(count($data) == 1);
	}

	function testCreateContract(
		$a = null, # Party A
		$b = null, # Party B
		$c = null, # Party C
		$d = null # Party D
	) {
		if(empty($a)) {
			$a = $this->testWallet(); // Party A
		}
		if(empty($b)) {
			$b = $this->testWallet(); // Party B
		}
		if(empty($c)) {
			$c = $this->testWallet(); // Guarantor
		}
		if(empty($d)) {
			$d = $this->testWallet(); // Arbitrator
		}

		# Initialization
		$network = $this->testNetwork();
		$db = $network->getDbClient();

		# Create Contract
		$res = $this->testValidateRequest([
			'action' => 'contract',
			'params' => [
				'inputs' => [
					[
						'from' => $a->getAddress(),
						'to' => $b->getAddress(),
						'amount' => 10,
						'message' => 'do a barrel roll',
					],
					[
						'from' => $a->getAddress(),
						'to' => $c->getAddress(),
						'amount' => 1,
						'role' => 'arbitrator',
					],
					[
						'from' => $a->getAddress(),
						'to' => $d->getAddress(),
						'amount' => 1,
						'role' => 'guarantor',
					]
				],
			],
		]);

		$rows = $db->dbQuery("cogTest.blocks",['request.action'=>'contract']);
		$this->assertTrue(count($rows) == 1);
		return reset($rows)['hash'];
	}

	function testSignContract() {
		$a = $this->testWallet(); // Party A

		$hash = $this->testCreateContract($a);

		# Sign Contract
		$params = [
			'action' => 'sign',
			'params' => [
				'hash' => $hash,
				'signature' => $a->sign($hash)
			],
		];

		cog::set_wallet($a);
		$res = $this->testValidateRequest($params);

		$rows = $this->getDb()->dbQuery("cogTest.blocks",['request.action'=>'sign']);
		$dbRows = $this->getDb()->dbQuery("cogTest.blocks",[]);
		cog::set_wallet(null);
		$this->assertTrue(count($rows) == 1,"Failed to find sign transaction in ".print_r($rows,1)."\nDB:\n".print_r($dbRows,1));
	}

	function testUpdateContract($hash = null,$a = null,$b = null,$c = null,$d = null) {
		if(empty($hash)) {
			$hash = $this->testCreateContract();
		}
		if(empty($a)) {
			$a = $this->testWallet(); // Party A
		}
		if(empty($b)) {
			$b = $this->testWallet(); // Party B
		}
		if(empty($c)) {
			$c = $this->testWallet(); // Guarantor
		}
		if(empty($d)) {
			$d = $this->testWallet(); // Arbitrator
		}

		# Update Contract

		$params = [
			'action' => 'addendum',
			'params' => [
				'inputs' => [
					[
						'from' => $a->getAddress(),
						'to' => $b->getAddress(),
						'amount' => 20,
						'message' => 'do a barrel roll',
						'deadline' => date('Y-m-d H:i:s',time()+15)
					],
					[
						'from' => $a->getAddress(),
						'to' => $c->getAddress(),
						'amount' => 2,
						'role' => 'arbitrator',
					],
					[
						'from' => $a->getAddress(),
						'to' => $d->getAddress(),
						'amount' => 2,
						'role' => 'guarantor',
					]
				],
			],
		];
		cog::set_wallet($a);
		$req = new request($params['action']);
		$req->setPrevHash($hash);
		$req->setParams($params['params']);
		$res = $req->submit('localhost','80',false,true);
		$dbRows = $this->getDb()->dbQuery("cogTest.blocks",[]);
		$rows = $this->getDb()->dbQuery("cogTest.blocks",['request.action'=>'addendum']);
		$this->assertTrue(count($rows) == 1,"Failed to find addendum transaction in ".print_r($rows,1)."\nDB:\n".print_r($dbRows,1));
	}


	function testInsert($db = "cogTest.blocks",$data = []) {
		if(empty($data)) {
			$data = [1]; // do something about the rejection of empty objects for this test
		}
		$network = $this->testNetwork();
		$hash = $network->put($data,true);
		$this->assertTrue(strlen($hash) > 0);
		$row = $network->get($hash);
		$this->assertTrue(!empty($hash));
	}
	
	function testQuery($db = "cogTest.blocks",$query = []) {
		$this->testInsert();
		$network = $this->testNetwork();
		$rows = $network->getDbClient()->dbQuery($db,$query);
		$this->assertTrue(count($rows) > 0,"Failed to query rows in '$db'.  Query:\n".print_r($query,1));
		return $rows;
	}

	function getDb() {
		return $this->testNetwork()->getDbClient();
	}

	function testCommentContract($hash = null) {
		# Comment on Contract
		if(empty($hash)) {
			$hash = $this->testCreateContract();
		}
		
		$network = $this->testNetwork();
		$row = $network->get($hash);
		$this->assertTrue(is_array($row));
		$this->assertTrue(count($row) > 0);
		
		$res = $this->testValidateRequest([
			'action' => 'comment',
			'params' => [
				'hash' => $hash,
				'comment' => "test-comment",
			],
		]);
		$rows = $this->getDb()->dbQuery("cogTest.blocks",['request.action'=>'comment']);
		$all_rows = $this->getDb()->dbQuery("cogTest.blocks",[]);
		$this->assertTrue(count($rows) == 1,"Number of contract comments queried does not equal 1 for contract '$hash'. (cogTest.blocks count: ".count($all_rows).")\n".print_r($rows,1)."\n".print_r($all_rows,2));
	}	

	function testSignEmpty() {
		$wallet = $this->testWallet();
		$req = [
			'pkey' => $wallet->getPublicKey(),
			'params' => [1]
		];
		$sig1 = $wallet->sign(json_encode($req));

		$enc1 = json_encode($req);
		$req['signature'] = $sig1;
		$req = json_decode(json_encode($req),true);
		$sig2 = $req['signature'];
		unset($req['signature']);

		$enc2 = json_encode($req);

		$this->assertTrue(sha1($enc1) == sha1($enc2));
		$verify = cog::verify_signature($enc2,$sig1,$req['pkey']);
		$this->assertTrue($verify == 1,"Failed to verify signature.");
	}

	function testSignature() {
		$wallet = $this->testWallet();
		$req = ['pkey'=>$wallet->getPublicKey()];
		$sig1 = $wallet->sign(json_encode($req));

		$enc1 = json_encode($req);
#		$this->testParty($wallet->getParty(),$enc1);

		$req['signature'] = $sig1;
		$req = json_decode(json_encode($req),true);
		$sig2 = $req['signature'];
		unset($req['signature']);

		$enc2 = json_encode($req);

		$this->assertTrue(sha1($enc1) == sha1($enc2));
		$verify = cog::verify_signature($enc2,$sig1,$req['pkey']);
		$this->assertTrue($verify == 1,"Failed to verify signature.");
	}

	function testStandaloneServer() {
		require_once("../lib/future/future.php");

$ctx = stream_context_create(array('http'=>
    array(
        'timeout' => 5, #seconds
    )
));

		$out = [];
		$newProcs = [];
		for($port = 81; $port < 4096; $port++) {
			cog::emit("Attempting to create process for port {$port}...\n");
			$pid = shell_exec("php ".dirname(__FILE__)."/../lib/http-standalone/test2.php {$port} >/dev/null 2>/dev/null & echo $!");
			$proc = [$pid];
			$out['server'] = $proc;
			$out['port'] = $port;
			sleep(1);
			$json = file_get_contents("http://localhost:$port/server.php",false,$ctx);
			$res = json_decode($json,true);
			if(is_array($res)) {
				emit("Created standalone server at port {$port} with process ID {$pid}.");
				$this->forks[] = $proc;
				foreach($newProcs as $rm) {
					\future::kill($rm);
				}
				break;
			}
			$newProcs[] = $proc;
		}
		return $out;
	}

	function testMultiServer() {
		require_once("../lib/future/future.php");
		$a = $this->testStandaloneServer();
		$b = $this->testStandaloneServer();

		/* TODO:
		- simulate multiple databases
		- from there, we can have the servers add each other as peers, do a send request or several for one, and then have them synchronize.
		-- with request to this it may be advisable to create server b after the request.
		*/
	}

	function testStandaloneServerKill() {
	  $srv = $this->testStandaloneServer();
	  \future::kill($srv['server']);
	  $out = shell_exec("ps aux|grep test2");
	  $rows = explode("\n",$out);
	  $success = true;
	  foreach($rows as $row) {
	    if(!preg_match("/test2.php/",$row)) continue;
	    preg_match("/[a-zA-Z0-9_]+\s+(\d+)\s+/",$row,$matches);
	    if(!isset($matches[1])) continue;
	    $pid = $matches[1];
	    if(strlen($pid)) {
	      $res = passthru("kill -9 {$pid}",$rv);
	      if($rv == 0) $success = false;
	    }
	  }
	  $this->assertTrue($success,"Orphan processes remain.");
	}
	
	function testSmoke($terms = array()) {
		$res = $this->nodeRequest();
		$req = $res['_REQUEST'];
		$req['request'] = json_decode($req['node_request']);
		cog::emit($req);
		return;
	}

	// boilerplate for cog smart contracts

function run_contract($script,$contract,$context,$block) {
	$input = "<?php
	  \$contract = json_decode(base64_decode('".base64_encode(json_encode($contract))."'));
	  \$context = json_decode(base64_decode('".base64_encode(json_encode($context))."'));
	  \$block = json_decode(base64_decode('".base64_encode(json_encode($block))."'));
	?>";


	$x = new \PHPSandbox\PHPSandbox();
	$x->setOptions([
	  'capture_output' => true,
	  'allow_closures' => true,
	  'allow_escaping' => true, # for phpunit, at least
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
	  $res = $x->execute("$input\n$script");
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

	function testSmartContract() {
		$network = $this->testNetwork();
		$wallet = $this->testWallet();
				
		# Create Contract
		$res = $this->testValidateRequest([
			'action' => 'contract',
			'params' => [
				'script' =>
'
<?php
/*
*	@name	coin
*	@author	michael lesane <df7c376196ca384ada0cccdd54727b8167c71ad20d3a00f88def200c57a282b7>
*	@desc	a demonstration of blockchain-powered coin functionality within the context of the cog project
*/
$out = [$contract,$context,$block];
echo json_encode($out,JSON_PRETTY_PRINT);
?>
'
			],
		]);

		cog::emit($res);die();

		$results = $network->getDbClient()->dbQuery("cogTest.blocks",[
			'$and' => [
				'request.action' => 'contract',
				'request.params.script' => ['$exists' => true]
			]
		]);
		$this->assertTrue(count($results) == 1);

		# TODO it works, but we're getting warnings.
		
		$contract = reset($results);
		$script = $contract['request']['params']['script'];
		$context = cog::generate_header($contract['hash'],0,$wallet->getAddress(),false,$wallet->getPublicKey());
		$block = [];
		
		$timeout = 1;
		
		$proc = \future::start([$this,'run_contract'],[$script,$contract,$context,$block]);
		$start_time = microtime(true);
		$out = null;
		try {
			while(1) {
				$current_time = microtime(true);
				$check = \future::check($proc);
				if(!$check) {
					$out = \future::wait($proc);
					break;
				}
				$elapsed = $current_time - $start_time;
				if($elapsed > 1) {
#					$status = 
					\future::kill($proc);
					throw new Exception('Execution timeout.');
				}
				usleep(1000);
			}
		} catch (Exception $e) {
			$message = "{$e->getMessage()}\n{$e->getTraceAsString()}";
			$out = ['result' => 0, 'output' => null, 'message' => $message];
			\future::kill($proc);
		}
		$this->assertTrue(is_null($e));
		cog::emit($out);
		$this->assertTrue(!empty($out));
	}
}
