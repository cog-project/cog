<?php
include 'main.php';

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
	function testSmoke($terms = array()) {
		$n = $this->testNetwork();

		$a = $this->testParty();
		$b = $this->testParty();

		$n->register($a);
		$n->register($b);

		$c = $a->buildContract($terms ? : array(
			'You will keep your word.',
			'You will not initiate aggression.',
			'You will respect private property.'
		));
		$c->addParty($b);
		$caddr = $n->put($c);

		$sig1 = $a->sign($n->get($caddr));
		$sig2 = $b->sign($n->get($caddr));

		$this->assertTrue(
			$n->sign($caddr,$sig1,$a->getAddress())
		);
		$this->assertTrue(
			$n->sign($caddr,$sig2,$b->getAddress())
		);

		$this->assertTrue(
			$n->sign($caddr,$sig1,$a->getAddress(),'done')
		);
		$this->assertTrue(
			$n->sign($caddr,$sig2,$b->getAddress())
		);

		$cmt = $n->genComment($caddr,$a->getAddress(),'comment','how do i mark a user as fraudulent');
		$sig3 = $a->sign($cmt,false);
		$n->comment($caddr,$cmt,$sig3);

		# should pass after moving to more granular architecture
		$this->assertTrue($n->length() == 2);

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
