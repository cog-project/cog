<?php
class contract {
	private $parties = [];
	private $terms;

	private $id;
	private $hash;
	private $prevHash;
	private $timestamp;

	public function __construct($x = null) {
		if(!empty($x)) {
			$this->setTerms($x);
		}
	}

	public function __toString() {
		return json_encode(
			$this->getData()
			+ array (
				'id' => $this->getId(),
				'hash' => $this->getHash(),
				'prevHash' => $this->getPrevHash(),
				'timestamp' => $this->getTimestamp()
			)
		, JSON_PRETTY_PRINT);
	}

	public function getData() {
		return array(
			'parties' => $this->parties,
			'terms' => $this->terms
		);
	}

	public function setParties($x) {
		$this->parties = $x;
	}
	
	public function addParty() {
		$args = func_get_args();
		foreach($args as $arg) {
			if(is_string($arg)) {
				$this->parties[] = $arg;
			} else {
				$this->parties[] = $arg->getAddress();
			}
		}
		$this->parties = array_values($this->parties);
	}

	public function setTerms($terms) {
		$this->terms = $terms;
	}

	public function getTerms() {
		return $this->terms();
	}

	public function getId() {
		return $this->id;
	}

	public function setId($x) {
		$this->id = $x;
	}

	public function getHash() {
		return $this->hash;
	}

	public function generateHash() {
		$this->hash = hash("sha256","{$this->id},{$this->prevHash},{$this->timestamp},".json_encode($this->getData(),JSON_PRETTY_PRINT));
	}

	public function getPrevHash() {
		return $this->prevHash;
	}

	public function setPrevHash($x) {
		$this->prevHash = $x;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function setTimestamp($x) {
		$this->timestamp = $x;
	}

	public function finalize($id,$prev) {
		$this->setId($id);
		$this->setPrevHash($prev);
		$this->generateHash();
	}
}

class party {
	private $pub;
	private $priv;
	private $addr;

	public function getAddress() {
		return $this->addr;
	}

	public function getPublicKey() {
		return $this->pub;
	}

	public function getPrivateKey() {
		return $this->priv;
	}

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

		// Use public key to generate uniqid/address
		$this->addr = hash("sha256",$this->pub);
	}

	public function encrypt($data)
	{
		if (openssl_public_encrypt($data, $encrypted, $this->pub)) {
			$data = base64_encode($encrypted);
		} else {
			throw new Exception('Unable to encrypt data. Perhaps it is bigger than the key size?');
		}
		return $data;
	}

	public function decrypt($data)
	{
		if (openssl_private_decrypt(base64_decode($data), $decrypted, $this->priv)) {
			$data = $decrypted;
			return $data;
		} else {
			$data = '';
			throw new Exception('Failed to decrypt data.');
		}
	}

	public function buildContract($x = null) {
		$out = new contract($x);
		$out->addParty($this);
		$out->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		return $out;
	}

	public function sign($data) {
		$terms = $data['data'];
		openssl_sign($terms, $binary_signature, $this->getPrivateKey(), OPENSSL_ALGO_SHA1);
		$sig = hash("sha256",$binary_signature);
		return $sig;
	}
}

class network {

	private $contracts = []; // tentative - please use real storage
	private $publicKeys = [];

	private $size = 0;
	private $lastHash = null;

	public function register($party) {
		$this->publicKeys[$party->getAddress()] = $party->getPublicKey();
	}

	public function put($contract) {
		$contract->finalize($this->size,$this->lastHash);
		$this->contracts[$contract->getHash()] = array(
			'data'=>$contract->__toString(),
			'todo'=>array(),
			'done'=>array(),
		);
		$this->lastHash = $contract->getHash();
		$this->size++;
		return $contract->getHash();
	}
	
	public function get($data_addr) {
		if(isset($this->contracts[$data_addr])) {
			return $this->contracts[$data_addr];
		} else {
			throw new Exception('Address not found.');
		}
	}
	
	public function edit($data_addr,$data) {
		$this->contracts[$data_addr] = $data;
	}
	
	public function sign($hash,$signature,$partyAddr,$type = 'todo') {
		$data = $this->contracts[$hash];

		if(!openssl_verify($data['data'],$signature,$this->publicKeys[$partyAddr])) {
			error_log("Failed to verify signature '$signature' for address '$partyAddr' in hash '$hash'");
			return false;
		} elseif (!isset($data[$type]) || $type == 'data') {
			error_log("Invalid index for signature");
			return false;
		}

		$data[$type][$partyAddr] = $signature;
		$this->edit($hash,$data);

		return true;
	}
}
?>
