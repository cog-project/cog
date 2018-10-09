<?php
chdir(dirname(__FILE__));

require '../main.php';

require 'traits/mining.php';
require 'traits/network.php';
require 'traits/party.php';
require 'traits/contract.php';
require 'traits/mongo.php';
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
	
	// No heavy mining in testing, so we can just use integers.
	protected $counter = 0;

	function setUp() {	
		emit("Running CogTest::{$this->getName()}");
		// Initialize Collection
		$this->initialize_collection();
	}

	function tearDown() {
		// Delete Collection
		$this->delete_collection();
	}

	function initialize_collection() {
		emit("Initializing db collection",true);

		try {
			$this->testMongoCreateCollection("cogTest","blocks");
		} catch (Exception $e) {
			emit($e->getMessage());
		}
		$this->assertTrue(empty($e));
	}

	function delete_collection() {
		emit("Deleting db collection",true);
		try {
			$this->testMongoDropCollection("cogTest","blocks");
		} catch (Exception $e) {
			emit($e->getMessage());
		}
		$this->assertTrue(empty($e));
	}

	function testInitialize($params = array()) {
		$out = [];
		foreach($params as $i => &$v) {
			$name_type = explode(":",$i);
			$name = $name_type[0];
			$type = $name_type[1];
			if(empty($v)) {
				$f = "test".ucfirst($type);
				$out[$name] = $this->$f();
			} else {
				$out[$name] = $v;
			}
			unset($params[$i]);
		}
		return $out;
	}

	function nodeRequest($params = []) {
		$url = 'http://localhost/cog/server.php';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		$res = curl_exec($ch);
		curl_close($ch);
		$this->assertTrue(strlen($res) > 0);
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

	function testValidateRequest($params = [], $bool = true, $verbose = true /* should be false by default */) {
		$this->testServEmpty();
		if(!empty($params)) {
			$params = array_merge($params,['environment' => 'cogTest']);
			$res = $this->nodeRequest($params);
			if($verbose) {
				emit($res);
			}
			$this->assertTrue(!empty($res['result']) == $bool);
			return $res;
		}
	}

	function testValidateAddrRequest() {
		# validate_address - no action
		$this->testValidateRequest([
			'blah',
		],false);

		# validate_address - no params
		$this->testValidateRequest([
			'action' => 'validate_address'
		],false);

		# validate_address - invalid action
		$this->testValidateRequest([
			'action' => 'blah',
			'params' => '909090909',
		],false);

		# validate_address - valid action, invalid address
		$this->testValidateRequest([
			'action' => 'validate_address',
			'params' => ['address' => '909090909'],
		],false);

		# register - valid action, invalid address
		$this->testValidateRequest([
			'action' => 'register',
			'params' => ['address' => '909090909'],
		],false);

		# register - valid action, valid address
		$party = $this->testParty();
		$res = $this->testValidateRequest([
			'action' => 'register',
			'params' => ['address' => $party->getAddress()],
		],true);
		$this->assertTrue(isset($res['data']));
		$this->assertTrue($res['data'] === true);

		# validate_address - valid action, valid address
		$res = $this->testValidateRequest([
			'action' => 'validate_address',
			'params' => ['address' => $party->getAddress()],
		],true);
		$this->assertTrue(isset($res['data']));
		$this->assertTrue(strlen($res['data']) > 0);
	}
	
	function testSmoke($terms = array()) {		
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
