<?php
class cog {
	static $version = 0;

	static function hash($x) {
		return hash("sha256",$x);
	}
	
	static function generate_nonce($counter) {
		# todo - verify this before network storage - and make sure the original unhashed string is visible.
		return self::hash($counter);
	}

	static function generate_zero_hash() {
		$nonce = self::generate_nonce("");
		for($i = 0; $i < strlen($nonce); $i++) {
			$nonce[$i] = '0';
		}
		return $nonce;
	}

	static function generate_header($prevHash = null,$counter = 0,$address = null) {
		$header = [
			'version' => self::$version,
			'prevHash' => $prevHash,
			'timestamp' => gmdate('Y-m-d H:i:s\Z'),
			'counter' => $counter,
			'address' => $address,
		];
		return json_encode($header,JSON_PRETTY_PRINT);
	}
}
?>