<?php
chdir(dirname(__FILE__));

require '../main.php';

require 'traits/mining.php';
require 'traits/network.php';
require 'traits/party.php';
require 'traits/contract.php';
require 'traits/mongo.php';

require 'cogtestbase.php';

require 'phpunit/vendor/autoload.php';

function emit($message,$verbose = false) {
	if($verbose && (!defined('VERBOSE') || !VERBOSE)) return;

	$str = "[".date("Y-m-d H:i:s")."]\t";
	if(is_array($message) || is_object($message)) {
		$str .= print_r($message,1);
	} else {
		$str .= $message;
	}
	echo "$str\n";
}

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

class CogTest extends CogTestBase {
	
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

	function testSmoke($terms = array()) {
		// Initliaze Database
		$db = $this->testMongoCreateClient();
		
		/* TESTS/RULES/CAVEATS:
		- network's first prevHash should be zeroHash.
		-- this should be assigned during object creation, retrieved from the database iff the working collection is empty.
		-- thereafter, during initialization, network's first prevHash should :not: be zeroHash, nor should it be ever again.
		- network "invite" command must involve one party: the inviting party.
		-- the address, however, must be included in the terms.
		-- we may also want to include the public key.
		- To prevent nonce reuse, we should precede the string used to generate the hash with 'cog' or something like that;
		- With the exception of the genesis block, party::buildContract() should be utilized.
		*/

		$network = $this->testNetwork();
		$zeroHash = $this->testMineZeroNonce();
		$this->assertTrue($network->getLastHash() == $zeroHash);
		$this->assertTrue($network->length() == 0);
		
		$nonce = $this->testMineNonce(0);
		# how does verification of this work in bitcoin?

		$master = $this->testParty();

		$genesisTerms = [
			'action' => 'invite',
			'params' => [
				'address' => $master->getAddress(),
				'public_key' => $master->getPublicKey(),
			],
		];
		
		$genesis = $this->testContract($genesisTerms,$nonce);
		$genesis->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		$genesis->setPrevHash($zeroHash);

		emit($genesis,true);

		emit($genesis->__toString(),true);

		$this->testNetworkAdd($network,$genesis);

		return; #architectural overhaul

		$a = $this->testParty();
		$b = $this->testParty();

		# should registrations also occur on the blockchain?  perhaps to account for constraints on / policy affecting new users?

		$cnt = $n->length();
		$n->register($a);
		$this->assertTrue($n->length() == $cnt+1);

		$cnt = $n->length();
		$n->register($b);
		$this->assertTrue($n->length() == $cnt+1);

		$nonce = $this->testMineContract();

		$c = $a->buildContract($terms ? : array(
			'You will keep your word.',
			'You will not initiate aggression.',
			'You will respect private property.'
		),$nonce['nonce']);
		$c->addParty($b);

		$cnt = $n->length();
		$caddr = $this->testNetworkAdd($n,$c);
		$this->assertTrue($n->length() == $cnt+1);

		$sig1 = $a->sign($n->get($caddr));
		$sig2 = $b->sign($n->get($caddr));

		$this->sign_contract_verify_increment($n,$sig1,$a,$caddr);
		$this->sign_contract_verify_increment($n,$sig2,$b,$caddr);
		$this->sign_contract_verify_increment($n,$sig1,$a,$caddr,'done');
		$this->sign_contract_verify_increment($n,$sig2,$b,$caddr);

		$cmt = $n->genComment($caddr,$a->getAddress(),'comment','how do i mark a user as fraudulent');
		# we should probably turn this into a static factory - so without further ado, TDD:
		$this->assertTrue(is_object(json_decode($cmt)));
		# needs to be a block containing the comment.

		$sig3 = $a->sign($cmt,false);
		$cnt = $n->length();
		$n->comment($caddr,$cmt,$sig3);

		# should pass after moving to more granular architecture
		$this->assertTrue($n->length() == $cnt + 1);

		# btw we could benefit from more signatures and more atomized transactions
		## alas, the imperative for scarcity

		$this->emit($c);
		$this->emit($n);
	}
}
