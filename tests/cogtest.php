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
			\future::kill($fork);
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
		$this->assertTrue(is_array($assoc), $res);
		$this->assertTrue(isset($assoc['result']));
		$this->assertTrue(isset($assoc['message']));
		return $assoc;
	}
	
	function testServEmpty() {
		$res = $this->nodeRequest();
		$this->assertTrue(!$res['result']);
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
			$this->assertTrue(!empty($res['result']) == $bool,"Result flag does not match expected bool '$bool'.  Result:".print_r($res,1));
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

	function testSignContract() {
		$a = $this->testWallet(); // Party A
		$b = $this->testWallet(); // Party B
		$c = $this->testWallet(); // Guarantor
		$d = $this->testWallet(); // Arbitrator

		$network = $this->testNetwork();
		$db = $network->getDbClient();
		
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
		$out = [];
		for($port = 81; $port < 4096; $port++) {
			cog::emit("Attempting to create process for port {$port}...\n");
			$proc = \future::start(
				"passthru",
				["php ".dirname(__FILE__)."/../lib/http-standalone/test2.php {$port} 2>/dev/null"]
			);
			$this->forks[] = $proc;
			$out['server'] = $proc;
			$out['port'] = $port;
			sleep(1);
			$json = @file_get_contents("http://localhost:$port/server.php");
			$res = json_decode($json,true);
			if(is_array($res)) break;
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
	
	function testSmoke($terms = array()) {
		return;

		// Begin here if we're not debugging and delete everything above, thanks.
	
		/* TESTS/RULES/CAVEATS:
		+ network's first prevHash should be zeroHash.
		++ this should be assigned during object creation, retrieved from the database iff the working collection is empty.
		++ thereafter, during initialization, network's first prevHash should :not: be zeroHash, nor should it be ever again.
		- network "invite" command must involve one party: the inviting party.
		++ the address, however, must be included in the terms.
		++ we may also want to include the public key.
		- To prevent nonce reuse/theft, we should precede the string used to generate the hash with 'cog' or something like that;
		-- We also need to validate this.
		- With the exception of the genesis block, party::buildContract() should be utilized.

		- invite verification
		-- may want to include public key
		-- absolutely need to invite address
		-- may wnat to verify count increment
		-- and of course verify previous block is appropriate
		--- also do this after adding blocks/contracts/whatever to the network
		--- and signatures - absolutely positively must be verified
		- comments
		- messages
		- dispute messages and the resolution process
		- the whole granular architecture thing - should be able to dynamically update contracts, with approval of appropriate parties
		*/
				
		# how does verification of this work in bitcoin?

		$wallet = new wallet();

		// Initliaze Database
		$db = $this->testMongoCreateClient();
		
		$network = $this->testNetwork();
		$master = $this->testParty();

		$genesis = $this->testCreateGenesisBlock($master);
		$this->testGenesisBlock($master,$genesis);

		return; #architectural overhaul
	}
}
