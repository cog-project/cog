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
	if($verbose && !defined('VERBOSE')) return;

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
	}
	
	function testSmoke($terms = array()) {
		// Initliaze Database
		$db = $this->testMongoCreate();

		// Initialize Collection
		try {
			$this->testMongoCreateCollection("cogTest","blocks");
		} catch (Exception $e) {
			emit($e->getMessage());
		}
		$this->assertTrue(empty($e));

		// Delete Collection
		try {
			$this->testMongoDropCollection("cogTest","blocks");
		} catch (Exception $e) {
			emit($e->getMessage());
		}
		$this->assertTrue(empty($e));

		return; #architectural overhaul
		$n = $this->testNetwork();

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
