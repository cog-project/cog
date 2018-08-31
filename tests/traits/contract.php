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
	
	public function testBuildContract($party = null, $terms = [], $nonce = null) {
		if(empty($party)) {
			$party = $this->testParty();
		}
		$this->testContract();
		$contract = $party->buildContract($terms,$nonce);
		return $contract;
	}
	
	public function testAddendum() {
		$this->assertTrue(class_exists("addendum"));
	}

	public function testCoin() {
	/*
		$this->testSmoke(array(
			'party' => 'muh-address',
			'coins' => 10,
			'task' => 'ditch digging',
			'due' => '2038-01-01',
		));
	*/
	}
	public function testGenesisBlock($master = null) {
		if(empty($master)) {
			$master = $this->testParty();
		}

		$network = $this->testNetwork(true);

		$zeroHash = $this->testMineZeroNonce();

		$genesisTerms = [
			'action' => 'invite',
			'params' => [
				'headers' => $this->testMineNonce(0,$master,$zeroHash),
				'address' => $master->getAddress(),
				'public_key' => $master->getPublicKey(),
			],
		];
		
		$nonce = $this->testMineNonce(0,$master,$zeroHash);
		$genesis = $this->testContract($genesisTerms,$nonce);
		$genesis->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		$genesis->setPrevHash($zeroHash);
		$sig = $master->sign($genesis);
		$genesis->addSignature($sig);

		emit($genesis,true);
		emit($genesis->toString(),true);

		$hash = $this->testNetworkAdd($network,$genesis);

		$network2 = $this->testNetwork();
		$this->assertTrue($network2->getLastHash() != $zeroHash);
		$this->assertTrue($network2->getLastHash() == $hash);

		return $hash;
	}
}
?>
