<?php
class network {

	private $publicKeys = [];

	private $size = 0;
	private $lastHash = null;
	private $dbClient;

	private $db = null;
	private $collection = null;

	public static function getZeroHash() {
		return cog::generate_zero_hash();
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

	# obsolete?
	public function register($party) {
		$this->publicKeys[$party->getAddress()] = $party->getPublicKey();
	}

	public function getSummary($address) {
		$res = $this->dbClient->queryByKey(
			"{$this->db}.{$this->collection}",
			[ '$or' =>
				[
					# Tentative - Transactions created by user
					['request.headers.address' => $address],

					# Append this with other message types please
					
					# Messages
					['request.params.recipient' => $address]
					# Disputed
					# Outstanding
					# Requests
					# Pending
					# Active
					# Completed
				]
			]
		);
		$out = $this->groupForSummary($res);
		return $out;
	}

	public function groupForSummary($data) {
		$out = [
			'completed' => [],
		];
		foreach($data as $v) {
			# Messages
			# Disputed
			# Outstanding
			# Requests
			# Pending
			# Active
			# Completed
			$out['completed'][$v->hash] = $v;
		}
		return $out;
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
		return array();
	}
	public function getNumAddresses() {
	# TODO test
		return $this->dbClient->getCount("{$this->db}","{$this->collection}",['terms.action'=>'invite']);
	}
	public function addNode($data) {
		$res = $this->dbClient->queryByKey("{$this->db}.nodes",['ip_address' => $data['ip_address'], 'ip_port' => $data['ip_port']]);
		if(count($res)) {
			$data['_id'] = $res[0]->_id;
			$res = $this->dbClient->dbUpdate("{$this->db}.nodes",$data);
		} else {
			$res = $this->dbClient->dbInsert("{$this->db}.nodes",$data);
		}
		return $res;
	}
	public function listNodes() {
		$out = [];
		$res = $this->dbClient->queryByKey("{$this->db}.nodes",[]);
		return $res;
	}
	public function hasAddress($parties = []) {
		if(!is_array($parties)) {
			$parties = [$parties];
		}
		$res = $this->dbClient->queryByKey(
			"{$this->db}.{$this->collection}",
			['request.action'=>'invite','request.params.address'=>['$in' => $parties]]
		);
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
				if(!isset($terms['headers']) || empty($terms['headers'])) {
					throw new Exception("No public key has been specified.");
				}
				$headers = json_decode($terms['headers'],true);
				if(!is_array($headers)) {
					throw new Exception("Failed to decode headers.");
				}
				if($headers['version'] != cog::$version) {
					throw new Exception("Running version '{$headers[0]}'; contract version is '".cog::$version."'");
				}
				if(!$this->hasHash($headers['prevHash'])) {
					throw new Exception("Previous hash '{$headers[1]}' not found.");
				}
				# [2] - verify gmdate
				if(empty($headers['timestamp'])) {
					throw new Exception("No timestamp has been specified.");
				}
				# [3] - not sure this needs to be verified (counter, previously nonce, optional maybe)
				if(!isset($headers['counter']) || is_null($headers['counter'])) {
					throw new Exception("No counter has been provided.");
				}
				# [4] - verify address
				if(empty($headers['address'])) {
					throw new Exception("No address has been provided.");
				}
				if(!$this->hasAddress($headers['address']) && $headers['prevHash'] != cog::generate_zero_hash()) {
					throw new Exception("Address '{$headers['address']}' was not found.");
				}
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
	public static function isZeroHash($hash) {
		return ($hash == cog::generate_zero_hash());
	}
	public function isInitialized() {
		$table = "{$this->db}.{$this->collection}";
		$res = $this->dbClient->dbQuery(
			$table,
			['request.headers.version' => "0.0.0"]#cog::generate_zero_hash()]
		)->toArray();
		return count($res) > 0;
	}
	public function validateContract($contract) {
		$this->validateContractParams($contract);
		$this->validateContractAction($contract);
		return true;
	}

	public function put($data) {
		$insert = [
			'hash' => cog::hash($data),
			'request' => $data,
		];
		$res = $this->dbClient->dbInsert("{$this->db}.{$this->collection}",$insert);
		return $res;
	}
	public function put_old($contract) {
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

	public function getMessagesByAddress($address) {
		$res = $this->dbClient->queryByKey("{$this->getDb()}.{$this->getCollection()}",[
			'request.action' => 'message',
			'request.params.recipient' => $address,
		]);
		if(count($res)) {
			return array_shift($res);
		} else {
			throw new Exception("No addresses found for address '$address'.");
		}
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
		if(self::isZeroHash($hash)) {
			return true;
		}
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
?>
