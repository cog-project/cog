<?php
chdir(dirname(__FILE__));

include '../main.php';
include 'traits/mining.php';

function emit($message) {
	$str = "[".date("Y-m-d H:i:s")."]\t";
	if(is_array($message) || is_object($message)) {
		$str .= print_r($message,1);
	} else {
		$str .= $message;
	}
	echo "$str\n";
}

// TODO: Convert "fallback" FZPL phpunit test case class into library for reuse elsewhere

if(!class_exists("phpunit_framework_testcase")){
	error_log("WARNING: phpunit_framework_testcase does not exist.  Creating placeholder.");

	class phpunit_framework_testcase {
		public function assertTrue($bool,$desc = '') {
		}
		public function assertFalse($bool,$desc = '') {
		}
	}
}

class CogTest extends phpunit_framework_testcase {
	// No heavy mining in testing, so we can just use integers.
	private $counter = 0;

	function setUp() {
		emit("Running CogTest::{$this->getName()}");
	}

	function testGenerateNonce(&$counter, &$hash) {
		$counter = $this->counter++;
		$hash = hash("sha256",$counter);
		$this->assertTrue(hash("sha256",$counter) == $hash);
	}

	function testVerifyNonce($difficulty = 1, $hash = null, $bool = true, $strict = false) {
		if(empty($hash)) {
			$hash = $this->testGenerateNonce($difficulty);
		}
		$success = true;
		for($i = 0; $i < $difficulty; $i++) {
			if($hash[$i] != '0') {
				$success = false;
			}
			if(!$success) {
				break;
			}
		}
		if($strict) {
			$this->assertTrue($success == $bool);
		} else {
			return $success == bool;
		}
	}
	
	function testMineNonce($difficulty = 1) {
		$out = null;
		$hash = null;
		while(1) {
			$this->testGenerateNonce($counter,$hash);
			$success = $this->testVerifyNonce($difficulty,$hash);
			if($success) {
				break;
			}
		}
		$this->assertTrue(!empty($hash));
		return $hash;
	}
	
	function sign_contact_verify_increment($network,$signature,$party,$contract_addr,$flag = null) {
		$cnt = $network->length();
		$this->sign_contract($network,$signature,$party,$contract_addr,$flag);
		$this->assertTrue($network->length() == $cnt + 1);
	}
	function sign_contract($network,$signature,$party,$contract_addr,$flag = null) {
		$this->assertTrue(
			$network->sign($contract_addr,$signature,$party->getAddress(),$flag)
		);
	}

	function testSmoke($terms = array()) {
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

		print_r($c);
		print_r($n);
	}

	public function testCoin() {
		$this->testSmoke(array(
			'party' => 'muh-address',
			'coins' => 10,
			'task' => 'ditch digging',
			'due' => '2038-01-01',
		));
	}

	public function testParty() {
		# smoke testing keypairs so we don't suffer debugging if these ever fail
		$this->assertTrue(class_exists("party"));
		$p = new party();
		$this->assertTrue($p->decrypt($p->encrypt("f00borx!!")) == "f00borx!!");
		return $p;
	}
	public function testContract() {
		$this->assertTrue(class_exists("contract"));
		$c = new contract();
		return $c;
	}
	public function testNetwork() {
		$this->assertTrue(class_exists("network"));
		$n = new network();
		return $n;
	}
	public function testNetworkAdd($network = null,$block = null) {
		if($network == null) {
			$network = $this->testNetwork();
		}
		if($block == null) {
			$block = $this->testBlock();
		}

		$len = $network->length();
		$hash = $network->put($block);
		# todo verify hash
		$this->assertTrue($network->length() == $len + 1);
		return $hash;
	}
	public function testBlock() {
		$this->assertTrue(class_exists("block"));
		$block = new block();
		return $block;
	}
	public function testAddendum() {
		$this->assertTrue(class_exists("addendum"));
	}

}
