<?php
trait miningTests {
	function testGenerateNonce(&$counter = 1, &$hash = null, $prevHash = null, $address = null) {
	/*
		if(!empty($prevHash) || !empty($address)) {
			$this->assertTrue(!empty($prevHash), "No prevHash (or zero-hash) provided.");
			$this->assertTrue(!empty($address), "No address provided.");
		}
	*/	
		$headers = cog::generate_header($prevHash,$counter,$address,false,$this->getPublicKey());
		$hash = cog::generate_nonce($headers);
		$verifyHash = cog::hash($headers);
		$this->assertTrue($verifyHash == $hash,"Computed hash '$verifyHash', expected '$hash'");
		emit("Nonce generated: $hash",true);
		$counter++;
		return $headers;
	}

	function testVerifyNonce($difficulty = 1, $hash = null, $bool = true, $strict = false) {
		if(empty($hash)) {
			$this->testGenerateNonce($difficulty,$hash);
		}
		emit("Verifying nonce '$hash' at difficulty '$difficulty'",true);
		$success = true;
		for($i = 0; $i < $difficulty; $i++) {
			if($hash[$i] != '0') {
				$success = false;
			}
			if(!$success) {
				break;
			}
		}
		emit("Nonce '$hash' ".($success ? "conforms" : "does not conform")." to difficulty $difficulty.",true);
		if($strict) {
			$this->assertTrue($success == $bool);
		} else {
			return $success == $bool;
		}
	}
	
	function testMineNonce($difficulty = 1, $party = null, $prevHash = null) {
		$out = null;
		$hash = null;
		$prefix = null;
		
		if(is_object($party)) {
			$address = $party->getAddress();
		} else {
			$address = null;
		}
		
		while(1) {
			$headers = $this->testGenerateNonce($this->counter,$hash,$prevHash,$address);
			$success = $this->testVerifyNonce($difficulty,$hash);
			if($success) {
				break;
			}
		}
		$this->assertTrue(!empty($hash));
		return $headers;
	}
	function testMineZeroNonce() {
		$hash = cog::generate_zero_hash();
		for($i = 0; $i < strlen($hash); $i++) {
			$this->assertTrue($hash[$i] == '0');
		}
		return $hash;
	}
}
?>
