<?php
trait contractTests {
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

	public function testBlock() {
		$this->assertTrue(class_exists("block"));
		$block = new block();
		return $block;
	}
	public function testContract($terms = [], $nonce = null) {
		$this->assertTrue(class_exists("contract"));
		$c = new contract($terms,$nonce);
		return $c;
	}
	
	public function testBuildContract($party, $terms = [], $nonce = null) {
		$this->testContract();
		$contract = $party->buildContract($terms,$nonce);
		return $contract;
	}
	
	public function testAddendum() {
		$this->assertTrue(class_exists("addendum"));
	}

	public function testCoin() {
		$this->testSmoke(array(
			'party' => 'muh-address',
			'coins' => 10,
			'task' => 'ditch digging',
			'due' => '2038-01-01',
		));
	}
}
?>
