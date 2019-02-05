<?php
class cog {
	static $version = '0.0.0';

	static function hash($x) {
		if(is_array($x)) {
			$x = json_encode($x,JSON_PRETTY_PRINT);
		}
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

	static function generate_header($prevHash = null,$counter = 0,$address = null,$json = true) {
		$header = [
			'version' => self::$version,
			'prevHash' => $prevHash,
			'timestamp' => gmdate('Y-m-d H:i:s\Z'),
			'counter' => (string)$counter,
			'address' => $address,
		];
		if($json) {
			return json_encode($header,JSON_PRETTY_PRINT);
		} else {
			return $header;
		}
	}

	static function generate_keypair() {
		$keypair = [
			'pub' => null,
			'priv' => null,
		];
		
		// Configuration settings for the key
		$config = array(
		    "digest_alg" => "sha512",
		    "private_key_bits" => 4096,
		    "private_key_type" => OPENSSL_KEYTYPE_RSA,
		);

		// Create the private and public key
		$res = openssl_pkey_new($config);

		// Extract the private key into $private_key
		openssl_pkey_export($res, $keypair['priv']);

		// Extract the public key into $public_key
		$pub = openssl_pkey_get_details($res);
		$keypair['pub'] = $pub["key"];

		return $keypair;
	}

	static function encrypt($pub,$data) {
		if (openssl_public_encrypt($data, $encrypted, $pub)) {
			$data = base64_encode($encrypted);
		} else {
			throw new Exception('Unable to encrypt data. Perhaps it is bigger than the key size?');
		}
		return $data;
	}

	static function decrypt($priv,$data) {
		if (openssl_private_decrypt(base64_decode($data), $decrypted, $priv)) {
			$data = $decrypted;
			return $data;
		} else {
			throw new Exception('Failed to decrypt data.');
		}
	}

	static function sign($priv,$data) {
		openssl_sign($data, $binary_signature, $priv, OPENSSL_ALGO_SHA1);
		$sig = base64_encode($binary_signature);
		return $sig;
	}

	static function verify_signature($data,$signature,$public_key) {
		return openssl_verify($data,base64_decode($signature),$public_key);
	}

	static function get_timestamp() {
		return gmdate('Y-m-d H:i:s\Z');
	}

	static function print($x) {
		echo '<pre>'.print_r($x,1).'</pre>';
	}
}
?>
