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
		$this->markTestSkipped();
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

	public function verifyRequest($request,$action) {
		$this->assertTrue($request->getAction() == $action);
		$array = $request->toArray();
		$this->assertTrue($array['action'] == $action);
		$this->assertTrue(strlen($array['id']) == 64);
		$json = $request->toString();
		$ob = json_decode($json);
		$this->assertTrue($ob->action == $action);
		$this->assertTrue(strlen($ob->id) == 64);		
	}

	public function verifyInvite($invite) {
		$this->verifyRequest($invite,"invite");
	}

	public function testRequest($action = 'invite',$params = null) {
		$this->assertTrue(class_exists('request'));
		$request = new request($action);
		$this->verifyRequest($request,$action);
		return $request;
	}

	public function testInviteRequest() {
		$this->testRequest();
		$this->assertTrue(class_exists('inviteRequest'));
		$invite = new inviteRequest();
		$this->verifyInvite($invite);
	}
	
	public function testCreateGenesisBlock($master = null) {
		$init = $this->testInitialize([
			'master:party'=> $master,
			'network:network' => null,
		]);
		foreach($init as $i=>$v) $$i = $v;

		$zeroHash = $this->testMineZeroNonce();

		$genesisTerms = [
			'headers' => $this->testMineNonce(0,$master,$zeroHash),
			'action' => 'invite',
			'params' => [
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

#		emit($genesis);
#		emit($genesis->toString(),true);

		return $genesis;
	}
	public function testGenesisBlock($master = null, $genesis = null) {
		$init = $this->testInitialize([
			'party:party'=> $master,
			'network:network' => null,
			'zeroHash:mineZeroNonce' => null,
			'genesis:createGenesisBlock' => $genesis,
		]);
		foreach($init as $i=>$v) $$i = $v;
		
		$hash = $this->testNetworkAdd($network,$genesis);

		$network2 = $this->testNetwork();
		$this->assertTrue($network2->getLastHash() != $zeroHash);
		$this->assertTrue($network2->getLastHash() == $hash);

		return $hash;
	}
}
?>
