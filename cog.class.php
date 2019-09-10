<?php
class cog {
	static $version = '0.0.4';
	static $wallet = null;
	static $environment = 'cog';
	static $dbClient = null;

	static function hash($x) {
		if(is_array($x)) {
			$x = json_encode($x);
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

	static function generate_header(
		string	$prevHash = null,
		int	$counter = 0,
		string	$address = null,
		bool	$json = true,
		string	$publicKey
	) {
		$header = [
			'version' => self::$version,
			'prevHash' => $prevHash,
			'timestamp' => self::get_timestamp(),
			'counter' => (string)$counter,
			'address' => $address,
			'publicKey' => $publicKey,
		];
		if($json) {
			return json_encode($header,JSON_PRETTY_PRINT);
		} else {
			return $header;
		}
	}

	static function get_binary_key($key) {
		preg_match('~^-----BEGIN ([A-Z ]+)-----\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----END \1-----\s*$~D', $key, $matches);
		$strip = trim($matches[2]);
		$raw = base64_decode($strip);
		return $raw;
	}

	static function simplify_key($key) {
		$raw = self::get_binary_key($key);
		$simple = base64_encode($raw);
		return $simple;
	}
	static function generate_addr($pub) {
		$raw = self::get_binary_key($pub);
		$addr = cog::hash($raw);
		return $addr;
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

		// Extract the private key
		openssl_pkey_export($res, $keypair['priv']);

		// Extract the public key
		$pub = openssl_pkey_get_details($res);
		$keypair['pub'] = $pub["key"];

		return $keypair;
	}

	static function encrypt($pub,$data) {
		if ($res = openssl_public_encrypt($data, $encrypted, $pub)) {
			$data = base64_encode($encrypted);
		} else {
			throw new Exception("Unable to encrypt data. Perhaps it is bigger than the key size?");
		}
		return $data;
	}

	public static $sig_algo = OPENSSL_ALGO_RMD160; # OPENSSL_ALGO_SHA1

	static function decrypt($priv,$data) {
		if (openssl_private_decrypt(base64_decode($data), $decrypted, $priv)) {
			$data = $decrypted;
			return $data;
		} else {
			throw new Exception('Failed to decrypt data with private key.');
		}
	}

	static function decrypt_public($pub,$data) {
		if (openssl_public_decrypt(base64_decode($data), $decrypted, $pub)) {
			$data = $decrypted;
			return $data;
		} else {
			throw new Exception('Failed to decrypt data with public key.');
		}
	}

	static function sign($priv,$data) {
		openssl_sign($data, $binary_signature, $priv, self::$sig_algo);
		$sig = base64_encode($binary_signature);
		return $sig;
	}

	static function verify_signature($data,$signature,$public_key) {
		return openssl_verify($data,base64_decode($signature),$public_key,self::$sig_algo);
	}

	static function get_timestamp() {
		return gmdate('Y-m-d H:i:s\Z');
	}

	static function emit($x) {
		echo '<pre>'.print_r($x,1).'</pre>';
	}

	static function log($message,$type /* INFO|DEBUG|ERROR */) {
		# TODO
	}

	static function set_wallet($x) {
		self::$wallet = $x;
	}

	static function get_wallet() {
		if(!is_object(self::$wallet)) {
			throw new Exception('No wallet object found');
		} else {
			return self::$wallet;
		}
	}

	static function check_hash_format($str) {
		return preg_match("/^[a-f0-9]{64}$/",$str);
	}
}
?>
