<?php
class cog
{
	public $pub;
	public $priv;

	public function __construct() {
		// Configuration settings for the key
		$config = array(
		    "digest_alg" => "sha512",
		    "private_key_bits" => 4096,
		    "private_key_type" => OPENSSL_KEYTYPE_RSA,
		);

		// Create the private and public key
		$res = openssl_pkey_new($config);

		// Extract the private key into $private_key
		openssl_pkey_export($res, $this->priv);

		// Extract the public key into $public_key
		$pub = openssl_pkey_get_details($res);
		$this->pub = $pub["key"];
	}

	public function encrypt($data)
	{
		if (openssl_public_encrypt($data, $encrypted, $this->pub))
			$data = base64_encode($encrypted);
		else
			throw new Exception('Unable to encrypt data. Perhaps it is bigger than the key size?');

		return $data;
	}

	public function decrypt($data)
	{
		if (openssl_private_decrypt(base64_decode($data), $decrypted, $this->priv))
			$data = $decrypted;
		else
			$data = '';

		return $data;
	}
}

$x = new cog();
$y = new cog();

$a = $x->encrypt("blah");
$b = $x->decrypt($a);
echo $b;
