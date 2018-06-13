<?php
trait networkTests {
	public function testNetwork() {
		$this->assertTrue(class_exists("network"));
		$n = new network();
		$n->init([
			'db' => 'cogTest',
			'collection' => 'blocks']
		);
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
		$this->assertTrue(!empty($hash));
		
		$db = $network->getDbClient();
		$this->assertTrue(is_object($db));
		$verify = $db->queryByKey("{$network->getDb()}.{$network->getCollection()}",['hash'=>$hash]);
		emit($verify);

		$this->assertTrue($network->length() == $len + 1);
		return $hash;
	}

}
?>
