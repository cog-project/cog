<?php
trait networkTests {
	public function testNetwork($new = false) {
		$this->assertTrue(class_exists("network"));
		$n = new network();
		$n->init([
			'db' => 'cogTest',
			'collection' => 'blocks']
		);
		if($new) {
			$zeroHash = $this->testMineZeroNonce();
			$this->assertTrue($n->getLastHash() == $zeroHash,"Expected: {$zeroHash}; Got: {$n->getLastHash()}");
			$this->assertTrue($n->length() == 0);
		}
		return $n;
	}

	public function testNetworkAdd($network = null,$block = null,$party = null, $lastHash = null) {
		if($network == null) {
			$network = $this->testNetwork();
		}
		if($block == null) {
			$len = $network->length();
			$block = $this->testContract();
			$block->setTerms([
				'action' => 'comment',
				'params' => ['body' => 'this is a test message.'],
			]);
			$block->setPrevHash($len ? $lastHash : $this->testMineZeroNonce());
			$block->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
			$block->setNonce($this->testMineNonce(0,$party));
			if($len) {
				$block->setCreator($party->getAddress());
				$block->addParty($party->getAddress());
				$sig = $party->sign($block);
				$block->setCreatorSignature($sig);
			}
		}

		$len = $network->length();
		$hash = $network->put($block);
		$this->assertTrue(!empty($hash));
		$last = $network->getLastHash();
		
		$db = $network->getDbClient();
		$this->assertTrue(is_object($db));
		$verify = $db->queryByKey("{$network->getDb()}.{$network->getCollection()}",['hash'=>$hash]);
		$this->assertTrue(count($verify) > 0,"Failed to verify existence of block with hash '$hash'");
		$row = array_pop($verify);
		$this->assertTrue($row->timestamp == $block->getTimestamp());

		# we'll want to $add strict arg when we work out conflicts / double-spending attacks.

		$this->assertTrue($network->length() == $len + 1);
		return $hash;
	}
}
?>
