<?php
class block {
	private $hash;
	private $prevHash;
	private $timestamp;
	
	# data?

	public function getId() {
		return $this->id;
	}

	public function setId($x) {
		$this->id = $x;
	}

	public function getHash() {
		return $this->hash;
	}

	public function getData() {
		return array (
			'hash' => $this->getHash(),
			'prevHash' => $this->getPrevHash(),
			'timestamp' => $this->getTimestamp()
		);
	}

	public function __toString() {
		return json_encode($this->getData(), JSON_PRETTY_PRINT);
	}
	public function __toArray() {
		return $this->getData();
	}

	public function generateHash() {
		$this->hash = hash("sha256","{$this->prevHash},{$this->timestamp},".json_encode($this->getData(),JSON_PRETTY_PRINT));
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

	public function finalize($prev = null) {
		if(!empty($prev)) {
			$this->setPrevHash($prev);
		}
		$this->generateHash();
	}
}

class contract extends block {
	private $parties = [];
	private $terms;
	private $nonce;
	private $deadline;

	public function __construct($x = [],$y = null) {
		if(!empty($x)) {
			$this->setTerms($x);
		}
		if(!empty($y)) {
			$this->setNonce($y);
		}
	}

	public function getData() {
		return array(
			'parties' => $this->parties,
			'terms' => $this->terms,
			'nonce' => $this->nonce,
			'deadline' => $this->deadline,
			# Guarantors,
			# Arbitrators,
		) + parent::getData();
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
		return $this->terms;
	}

	public function setNonce($nonce) {
		$this->nonce = $nonce;
	}

	public function getNonce() {
		return $this->nonce;
	}

	public function setDeadline($deadline) {
		$this->deadline = $deadline;
	}

	public function getDeadline() {
		return $this->deadline;
	}
}

# for appending contracts
class addendum extends contract {
	
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

	public function buildContract($x = null,$y = null) {
		$out = new contract($x,$y);
		$out->addParty($this);
		$out->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		return $out;
	}

	public function sign($data,$contract = true) {
		$terms = $contract ? $data['data'] : $data;
		openssl_sign($terms, $binary_signature, $this->getPrivateKey(), OPENSSL_ALGO_SHA1);
		$sig = base64_encode($binary_signature);
		return $sig;
	}
}

class network {

	private $contracts = []; // tentative - please use real storage
	private $publicKeys = [];

	private $size = 0;
	private $lastHash = null;
	private $dbClient;

	private $db = null;
	private $collection = null;

	public static function getZeroHash() {
		$hash = hash("sha256",0);
		for($i = 0; $i < strlen($hash); $i++) {
			$hash[$i] = '0';
		}
		return $hash;
	}

	public function getDb() {
		return $this->db;
	}

	public function getCollection() {
		return $this->collection;
	}
	
	public function init($opts) {
		$db = $opts['db'];
		$collection = $opts['collection'];
		$this->db = $db;
		$this->collection = $collection;
		
		# TODO: exception handling
		
		if($this->dbClient->getCount($this->db,$this->collection) == 0) {
			$this->lastHash = self::getZeroHash();
		} else {
			$this->lastHash = $this->getLastHash();
		}
	}
	public function __construct() {
		// We should be able to configure this later.
		$this->dbClient = new dbClient();
	}

	public function register($party) {
		$this->publicKeys[$party->getAddress()] = $party->getPublicKey();
	}

	public function put($contract,$lastHash = null) {
		# nonce - should be at least three, and over threshold of signers

		$contract->finalize($lastHash);
		$res = $this->dbClient->dbInsert("{$this->db}.{$this->collection}",$contract->__toArray());
		if($res->getInsertedCount()) {
			$this->lastHash = $contract->getHash();
			$this->size = $this->length();
			return $contract->getHash();
		}
/*
		$this->contracts[$contract->getHash()] = array(
			# Contract Data
			'data'=>$contract->__toString(),
			# Affirm Contract Terms
			'todo'=>array(),
			# Confirm Contract Completion
			'done'=>array(),
			# Dispute Contract Completion
			'disp'=>array(),
			# Dispute Contract Comments
			'objs'=>array(),
			# Miscellaneous Contract Comments
			'cmts'=>array(),
		);
*/
	}

	public function getLastHash() {
		if(!strlen($this->lastHash)) {
			$table = "{$this->db}.{$this->collection}";
			$res = $this->dbClient->dbQuery($table,[
				'limit' => 1,
				'sort' => ['_id' => -1],
			])->toArray();
			$this->lastHash = 'foo';
		}
		return $this->lastHash;
	}

	public function length() {
		return $this->dbClient->getCount("{$this->db}","{$this->collection}");
	}
	
	public function hasNonce($nonce) {
		foreach($this->contracts as $c) {
			if($c->getNonce() == $nonce) return true;
		}
		return false;
	}
	public function getByNonce() {
	}

	public function get($data_addr) {
		if(isset($this->contracts[$data_addr])) {
			return $this->contracts[$data_addr];
		} else {
			throw new Exception("Address '$data_addr' not found.");
		}
	}
	
	public function edit($data_addr,$data) {
		$this->contracts[$data_addr] = $data;
	}
	
	public function sign($hash,$signature,$partyAddr,$type = 'todo') {
		$data = $this->get($hash);

		if(!openssl_verify($data['data'],base64_decode($signature),$this->publicKeys[$partyAddr])) {
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

	public function genComment($hash,$partyAddr,$type = 'comment',$comment) {
		$cmt = array
		(
			'hash' => $hash,
			'party' => $partyAddr,
			'type' => $type,
			'message' => $comment,
			'timestamp' => gmdate('Y-m-d H:i:s\Z'),
		);
		return json_encode($cmt,JSON_PRETTY_PRINT);
	}

	public function comment($hash, $cmt,$signature) {
		$cmt = json_decode($cmt,true);
		$cmt['signature'] = $signature;

		$data = $this->get($hash);
		$data['cmts'][] = json_encode($cmt,JSON_PRETTY_PRINT);
		$this->edit($hash,$data);
	}

	public function getDbClient() {
		return $this->dbClient;
	}
}

class dbClient {
	protected $client = null;

	public function __construct() {
		$this->client = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	}

	public function queryByKey($table,$key = []) {
		$res = $this->dbQuery($table,$key);
		return $res->toArray();
	}

	public function command($cmd) {
		return $this->dbCommand("admin",$cmd);
	}

	public function dbCollection($db,$cmd) {
		return $this->client->executeCommand($db,new \MongoDB\Driver\Collection($cmd));
	}

	public function dbCommand($db,$cmd) {
		return $this->client->executeCommand($db,new \MongoDB\Driver\Command($cmd));
	}

	public function dbQuery($db,$key) {
		return $this->client->executeQuery($db,new \MongoDB\Driver\Query($key));
	}

	public function dbDelete($table,$key) {
		$this->dbDeleteMultiple($table,[$key]);
	}
	public function dbDeleteMultiple($table,$key) {
		$bulk = new MongoDB\Driver\BulkWrite;
		foreach($key as $v) {
			$bulk->delete($v);
		}
		$this->client->executeBulkWrite($table,$bulk);
	}
	public function dbInsert($db,$key) {
		return $this->dbInsertMultiple($db,[$key]);
	}
	public function dbInsertMultiple($db,$key = []) {
		$bulk = new MongoDB\Driver\BulkWrite;
		foreach($key as $v) {
			$bulk->insert($v);
		}
		return $this->client->executeBulkWrite($db,$bulk);
	}
	
	public function showDatabases() {
		$res = $this->dbCommand("admin",["listDatabases"=>1])->toArray();
		return array_shift($res)->databases;
	}
	public function showCollections($db) {
		$res = $this->dbCommand($db,["listCollections"=>1])->toArray();
		$out = array_map(function($x) { return $x->name; },$res);
		return $out;
	}

	public function getCount($db,$table,$query = []) {
		$cmd = ['count'=>$table];
		if(!empty($query)) {
			$cmd += $query;
		}
		$res = $this->dbCommand($db,$cmd);
		$array = $res->toArray();
		$row = reset($array);
		if($row->ok) {
			return $row->n;
		} else {
			print_r($row);
			return null;
		}
	}
	public function collectionDrop($db,$collection) {
		$res = $this->dbCommand("$db",["drop" => $collection]);
	}
	public function collectionCreate($db,$collection) {
		$res = $this->dbCommand("$db",["create" => $collection]);
	}
}
?>
