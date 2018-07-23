<?php
trait miningTests {
	function testGenerateNonce(&$counter = 1, &$hash = null, $prefix = 'cog') {
		$workingCounter = "{$prefix}!!{$counter}";
		cog::generate_nonce($workingCounter,$hash,$prefix);
		$verifyHash = hash("sha256",$workingCounter);
		$this->assertTrue($verifyHash == $hash,"Computed hash '$verifyHash', expected '$hash'");
		emit("Nonce generated: $hash",true);
		$counter++;
	}

	public function generateNewNonce($version = 0,&$prevHash = null,&$counter = 1,$pregix = 'cog') {
		$header = [
			$version,
			$prevHash,
			gmdate('Y-m-d H:i:s\Z'),
			cog::generate_nonce($counter),
			$prefix,
		];
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
	
	function testMineNonce($difficulty = 1, $party = null) {
		$out = null;
		$hash = null;
		$prefix = null;
		
		if(is_object($party)) {
			$prefix = $party->getAddress();
		}
		
		while(1) {
			$this->testGenerateNonce($this->counter,$hash,$prefix);
			$success = $this->testVerifyNonce($difficulty,$hash);
			if($success) {
				break;
			}
		}
		$this->assertTrue(!empty($hash));
		return $hash;
	}
	function testMineZeroNonce() {
		$hash = $this->testMineNonce(0);
		for($i = 0; $i < strlen($hash); $i++) {
			$hash[$i] = '0';
		}
		return $hash;
	}
}
?>
