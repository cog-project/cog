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
	function testSmoke() {
		$n = new network();

		$a = new party();
		$b = new party();

		$n->register($a);
		$n->register($b);

		print_r($a);
		print_r($b);

		$c = $a->buildContract(array(
			'You will keep your word.',
			'You will not initiate aggression.',
			'You will respect private property.'
		));
		$c->addParty($b);
		$caddr = $n->put($c);

		$sig1 = $a->sign($n->get($caddr));
		$sig2 = $b->sign($n->get($caddr));

		$n->sign($caddr,$sig1,$a->getAddress());
		$n->sign($caddr,$sig2,$b->getAddress());

		$n->sign($caddr,$sig1,$a->getAddress(),'complete');
		$n->sign($caddr,$sig2,$b->getAddress());

		print_r($c);
		print_r($n);
	}

	public function testParty() {
		$this->assertTrue(class_exists("party"));
	}
	public function testContract() {
		$this->assertTrue(class_exists("contract"));
	}
	public function testNetwork() {
		$this->assertTrue(class_exists("network"));
	}
}
