<?php
include dirname(__FILE__).'/block.class.php';
include dirname(__FILE__).'/cog.class.php';

class contract extends block {
	private $parties = [];
	private $signatures = [];

	private $arbitrators = [];
	private $guarantors = [];
	private $summary;

	private $terms;
	private $nonce;
	private $deadline;

	private $creator;
	private $creatorSignature;

	public function build($data) {
		$data = (array)$data;
		foreach($data as $k => $v) {
			if(property_exists($this,$k)) {
				$this->$k = $v;
			}
		}
	}
	
	public function __construct($terms = [],$nonce = null) {
		if(!empty($terms)) {
			$this->setTerms($terms);
		}
		if(!empty($nonce)) {
			$this->setNonce($nonce);
		}
	}

	public function getData($hash = false) {
		return array(
			'parties' => $this->parties,
			'arbitrators' => $this->arbitrators,
			'guarantors' => $this->guarantors,
			'summary' => $this->summary,

			'terms' => $this->terms,
			'nonce' => $this->nonce,
			'deadline' => $this->deadline,
			'creator' => $this->creator,
			#'signatures' => $this->signatures,
			# Guarantors,
			# Arbitrators,
		) + parent::getData($hash);
	}

	public function setParties($x) {
		$this->parties = $x;
	}

	public function getParties() {
		return $this->parties;
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
	public function addSignature($signature) {
		$this->signatures[] = $signature;
	}
	public function getSignatures() {
		return $this->signatures;
	}
	public function setCreatorSignature($signature) {
		$this->creatorSignature = $signature;
	}
	public function getCreatorSignature() {
		return $this->creatorSignature;
	}
	public function setCreator($x) {
		$this->creator = $x;
	}
	public function getCreator() {
		return $this->creator;
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

	public function setPublicKey($x) {
		$this->pub = $x;
	}

	public function setAddress($x) {
		$this->addr = $x;
	}

	public function __construct($populate = true) {
		if(!$populate) return;

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

	public function buildContract($terms = null,$nonce = null) {
		$out = new contract($terms,$nonce);
		$out->setCreator($this->getAddress());
		return $out;
	}

	public function sign($contract) {
		$terms = $contract->toString(false);
		openssl_sign($terms, $binary_signature, $this->getPrivateKey(), OPENSSL_ALGO_SHA1);
		$sig = base64_encode($binary_signature);
		return $sig;
	}
}

class network {

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

		$this->getLastHash();
	}
	public function __construct() {
		// We should be able to configure this later.
		$this->dbClient = new dbClient();
	}

	public function register($party) {
		$this->publicKeys[$party->getAddress()] = $party->getPublicKey();
	}

	public function validateContractParams($contract) {
		$lastHash = $contract->getPrevHash();
		$len = $this->length();
		// Reject Empty PrevHashes
		if(!strlen($lastHash)) {
			throw new Exception("A previous hash has not been included.");
		}
		if(!$this->hasHash($lastHash) &&
		    ($len)
		) {
			throw new Exception("There were no blocks found with hash '{$lastHash}'");
		}
		if(empty($contract->getTimestamp())) {
			throw new Exception("No timestamp was included.");
		}
		if(empty($contract->getCreator()) && $len) {
			throw new Exception("No creator was specified.");
		}
		if(empty($contract->getCreatorSignature()) && $len) {
			throw new Exception("No creator signature was specified.");
		}
		if($this->hasHash($contract->getHash())) {
			throw new Exception("A block with this hash already exists.");
		}
		if($this->hasNonce($contract->getNonce())) {
			throw new Exception("A nonce with this hash already exists.");
		}
		if(!count($contract->getParties()) && $len) {
			throw new Exception("There are no parties associated with this contract.");
		}
		$invalid = $this->getInvalidAddresses($contract->getParties());
		if(count($invalid)) {
			throw new Exception("This contract contains invalid addresses listed as parties:\n'".implode("'\n",$invalid));
		}
		if($invalid = !$this->hasAddress($contract->getCreator()) && $len) {
			throw new Exception("This contract contains an invalid creator address:\n'".$contract->getCreator()."'");
		}
		if($len) {
			$ver = $this->verifySignature($contract,$contract->getCreatorSignature(),$contract->getCreator());
			if(!$ver) {
				throw new Exception("Failed to verify signature for contract.\nContract: {$contract->toString()}\nHash: {$contract->getHash()}");
			}
		}
	}
	public function getInvalidAddresses($parties = []) {
		if(!is_array($parties)) {
			$parties = [$parties];
		}
		$res = $this->dbClient->queryByKey("{$this->db}.{$this->collection}",['terms.action'=>'invite','terms.params.address'=>$parties]);
		emit($res);
return array();
	}
	public function hasAddress($parties = []) {
		if(!is_array($parties)) {
			$parties = [$parties];
		}
		$res = $this->dbClient->queryByKey("{$this->db}.{$this->collection}",['terms.action'=>'invite','terms.params.address'=>$parties]);
return true;
		return count($res) == count($parties) ? true : false;
	}
	public function validateContractAction($contract) {
		$data = $contract->__toArray();
		if(!isset($data['terms']) || empty($data['terms'])) {
			throw new Exception("Block terms are empty.");
		}
		$terms = $data['terms'];
		if(!isset($data['terms']) || empty($terms['action'])) {
			throw new Exception("Please specify an action.");
		}
		switch($terms['action']) {
			case 'invite':
				if($this->length() && empty($data['parties'])) {
					throw new Exception("No inviting party has been specified.");
				}
				$params = $terms['params'];
				if(!isset($params['address']) || empty($params['address'])) {
					throw new Exception("No address has been specified.");
				}
				if(!isset($params['public_key']) || empty($params['public_key'])) {
					throw new Exception("No public key has been specified.");
				}
				# todo - validate address and public key
				break;
			case 'comment':
				if(empty($terms['params']) || empty($terms['params']['body'])) {
					throw new Exception("No message body has been specified.");
				}
				break;
			default:
				throw new Exception("Action '{$terms['action']}' not found.");
				break;
		}
	}
	public function validateContract($contract) {
		$this->validateContractParams($contract);
		$this->validateContractAction($contract);
		return true;
	}

	public function put($contract) {
		if(!$this->validateContract($contract)) {
			return null;
		}
		$lastHash = $contract->getPrevHash();
		
		# nonce - should be at least three, and over threshold of signers

		$contract->finalize($lastHash);

		$res = $this->dbClient->dbInsert("{$this->db}.{$this->collection}",$contract->__toArray(true));
		if($res->getInsertedCount()) {
			$this->lastHash = $contract->getHash();
			$this->size = $this->length();
			return $contract->getHash();
		}
		/*
		Old Params:
		- data
		- todo
		- done
		- disp
		- objs
		- cmts
		*/
	}

	public function getLastHash($refresh = false) {
		if(!strlen($this->lastHash) || $refresh) {
			$table = "{$this->db}.{$this->collection}";
			$res = $this->dbClient->dbQuery(
				$table,
				[],
				[
					'sort' => ['timestamp' => -1],
					'limit' => 1,
				]
			)->toArray();
			if(count($res)) {
				$latest = array_pop($res);
				$this->lastHash = $latest->hash;
			} else {
				$this->lastHash = $this->getZeroHash();
			}
		}
		return $this->lastHash;
	}

	public function size() {
		return $this->length();
	}
	
	public function length() {
		return $this->dbClient->getCount("{$this->db}","{$this->collection}");
	}
	
	public function getByNonce($nonce) {
		$res = $this->dbClient->queryByKey("{$this->getDb()}.{$this->getCollection()}",['nonce'=>$nonce]);
		if(count($res)) {
			return array_shift($res);
		} else {
			throw new Exception("Nonce '$hash' not found.");
		}
	}
	public function hasNonce($nonce) {
		$res = $this->dbClient->queryByKey("{$this->getDb()}.{$this->getCollection()}",['nonce'=>$nonce]);
		if(count($res)) {
			return true;
		} else {
			return false;
		}
	}

	public function hasHash($hash) {
		$res = $this->dbClient->queryByKey("{$this->getDb()}.{$this->getCollection()}",['hash'=>$hash]);
		if(count($res)) {
			return true;
		} else {
			return false;
		}
	}
	public function get($hash) {
		$res = $this->dbClient->queryByKey("{$this->getDb()}.{$this->getCollection()}",['hash'=>$hash]);
		if(count($res)) {
			return array_shift($res);
		} else {
			throw new Exception("Hash '$hash' not found.");
		}
	}
	
	public function edit($data_addr,$data) {
		$this->contracts[$data_addr] = $data;
	}

	public function getParty($partyAddr) {
		$rows = $this->dbClient->queryByKey("{$this->db}.{$this->collection}",[
			"terms.action" => "invite",
			"terms.params.address" => "$partyAddr",
		]);
		if(empty($rows)) {
			throw new Exception("No address registration for '{$partyAddr}' was found.");
		}
		$row = array_shift($rows);
		$pkey = $row->terms->params->public_key;

		$party = new Party(false);
		$party->setAddress($partyAddr);
		$party->setPublicKey($pkey);
		return $party;
	}

	public function getContract($hash) {
		$contract = new contract();
		$contract->build($this->get($hash));
		return $contract;
	}

	public function verifySignature($contract,$signature,$partyAddr) {
		$party = $this->getParty($partyAddr);
		$verification = openssl_verify($contract->toString(false),base64_decode($signature),$party->getPublicKey());
		return $verification;
	}
	public function sign($hash,$signature,$partyAddr,$type = 'todo') {
		$contract = $this->getContract($hash);
		$verification = $this->verifySignature($contract,$signature,$partyAddr);

		if(!$verification) {
			throw new Exception("Failed to verify signature '$signature' for address '$partyAddr' in hash '$hash'");
			return false;
		}

		$contract = new contract();
		$contract->setTerms([
			'action' => 'sign',
			'params' => [
				'address' => $partyAddr,
				'signature' => $signature,
			],
		]);
		$contract->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		$contract->setPrevHash($hash);
		$contract->setNonce("{$partyAddr}!!"); #todo
		# no signature required on this signature i guess

		#this function is obsolete

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
