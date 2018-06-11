<?php
trait miningTests {
	function testGenerateNonce(&$counter = 1, &$hash = null) {
		$hash = hash("sha256",$counter);
		$this->assertTrue(hash("sha256",$counter) == $hash);
		emit("Nonce generated: $hash",true);
		$counter++;
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
	
	function testMineNonce($difficulty = 1) {
		$out = null;
		$hash = null;
		while(1) {
			$this->testGenerateNonce($this->counter,$hash);
			$success = $this->testVerifyNonce($difficulty,$hash);
			if($success) {
				break;
			}
		}
		$this->assertTrue(!empty($hash));
		return $hash;
	}
}
?>
