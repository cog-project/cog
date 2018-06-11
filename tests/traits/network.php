<?php
trait networkTests {
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

}
?>
