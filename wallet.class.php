<?php
class wallet {
	private $party;
	private $environment = 'cog';
	
	public function __construct($new = false) {
		$path = dirname(__FILE__).'/client_data/wallet.dat';
		if(file_exists($path) && !$new) {
			$this->party = unserialize(file_get_contents($path));
		}
		cog::set_wallet($this);
	}

	public function getConfig() {
		$req = new request('get_config');
		$req->setParams(['address' => $this->getAddress()]);
		$data = $req->submitLocal();
		# TODO account for lack of data after all requests
		if(isset($data['data'])) {
			return $data['data'];
		} else {
			return [];
		}
	}

	public static function init() {
		return new wallet();
	}

	public function hasParty() {
		return is_object($this->party) && get_class($this->party) == 'party';
	}

	public function createParty() {
		$p = new party();
		file_put_contents(dirname(__FILE__).'/client_data/wallet.dat',serialize($p));
		$this->party = $p;
	}

	public function setParty($party) {
		$this->party = $party;
	}

	public function getAddress() {
		return $this->party->getAddress();
	}

	public function getPublicKey() {
		return $this->party->getPublicKey();
	}

	public function getNumAddresses() {
		$req = new request('address_count');
		$res = $req->submit(null,null,true);
	}

	public function getNumBlocks() {
		$req = new request('blocks_count');
		$res = $req->submit(null,null,true);
	}

	public function addNode($data) {
		$req = new request('add_node');
		$req->setParams($data);
		$res = $req->submitLocal();
		return $res;
	}

	public function sync($data) {
		// condense db to endpoints and retrieve current
		$req = new request('get_endpoints');
		$response = $req->submitLocal();

		$local_endpoints = $response['data'];

		// build request - retrieve remote endpoints
		// TODO all we actually need are hashes for the first stage
		$ip = $data['ip_address'];
		$port = $data['ip_port'];

		$response = $req->submit($ip,$port);

		// add node if it isn't already listed
		# TODO please begin to return all responses with address, pkey, signature, timestamp
		if(isset($response['time']) && !empty($response['data'])) {
			$data = [
				'ip_address' => $ip,
				'ip_port' => $port,
				# address
				# public_key
				'ping_datetime' => cog::get_timestamp(),
				# local_datetime
				'request_time' => $response['time']
			];
			$this->addNode($data);
		}

		$remote_endpoints = $response['data'];

		// convert remote endpoints to hashes
		$remote_endpoint_hashes = [];
		foreach($remote_endpoints as $e) {
			$remote_endpoint_hashes[$e['hash']] = true;
		}

		// filter redundant endpoints
		foreach($local_endpoints as $e) {
			if(isset($remote_endpoint_hashes[$e['hash']])) {
				unset($remote_endpoint_hashes[$e['hash']]);
			}
		}

		// filter redundant local endpoints
		$local_endpoint_hashes = [];
		foreach($local_endpoints as $e) {
			if(!isset($remote_endpoint_hashes[$e['hash']])) {
				$local_endpoint_hashes[$e['hash']] = true;
			}
		}

		if(!empty($remote_endpoint_hashes)) {
			// request histories of new endpoints, stop at local endpoints
			$req = new request('get_hash_history');
			$req->setParams([
				'endpoints' => $remote_endpoint_hashes,
				'startpoints' => $local_endpoint_hashes,
			]);
			$response = $req->submit($ip,$port);

			// validate and store transactions, and update endpoints
			$req = new request('insert_transactions');
			$req->setParams(['data' => $response['data']]);
			$res = $req->submitLocal();
		}
	}

	public function removeNode($data) {
		$req = new request('remove_node');
		$req->setParams($data);
		$res = $req->submitLocal();
		return $res;
	}

	public function config($data) {
		$req = new request('config');
		$req->setParams($data);
		$res = $req->submitLocal();
		return $res;
	}

	public function listNodes() {
		$req = new request('list_nodes');
		$res = $req->submit('localhost',80);
		return $res['data'];
	}

	public function requestPeers($data) {
		$req = new request('list_nodes');
		$res = $req->submit($data['ip_address'],$data['ip_port']);
		
		$data = $res['data'];

		$existing = $this->listNodes();
		$exclude = [];
		foreach($existing as $peer) {
			$exclude["{$peer['ip_address']}:{$peer['ip_port']}:{$peer['address']}"] = true;
		}

		foreach($data as &$peer) {
			if($peer['ip_address'] == '127.0.0.1'
			|| $peer['ip_address'] == 'localhost'
			|| isset($exclude["{$peer['ip_address']}:{$peer['ip_port']}:{$peer['address']}"])
			) {
				continue;
			}
			unset($peer['_id']);
			$peer['local_datetime'] = null;
			$peer['ping_datetime'] = null;
			$peer['request_time'] = null;
			$this->addNode($peer);
		}
		return $data;
	}

	public function ping($data) {
		$timestamp = cog::get_timestamp();
		$config = $this->getConfig();

		// Generate Request

		$req = new request('ping');
		
		$params = [
			'ip_address' => @$_SERVER['SERVER_HOST'] ? : 'localhost',
			'ip_port' => @$_SERVER['SERVER_PORT'] ? : '80',
		];

		// Optionally Pass Local Details

		if(!empty($config)) {
			$params['remote'] = [
				'ip_address' => $config['ip_address'],
				'ip_port' => $config['ip_port'],
				'address' => $config['address'],
				'public_key' => $config['public_key'],
				'nickname' => $config['nickname'],
				'local_datetime' => $timestamp
			];
		}

		// Submit Request

		$req->setParams($params);
		$res = $req->submit($data['ip_address'],$data['ip_port']);

		if(isset($res['data'])) {
			// Response Data
			$data = $res['data'];

			// Request Time Data
			$time = $res['time'];
			$data['request_time'] = $time;

			// Include Time of Last Request
			$data['ping_datetime'] = $timestamp;

			// Store Data		
			$this->addNode($data);

			return true;
		} else {
			// exception / alert
		}
	}

	public function getIsRegistered() {
		$req = new request('validate_address');
		$req->setParams([
			'address' => $this->getAddress()
		]);
		$res = $req->submit(null,null,true);
		return $res['data'];
	}

	public function getEnvironment() {
		return $this->environment;
	}

	public function setEnvironment($x) {
		$this->environment = $x;
	}

	public function register($database,$address,$publicKey) {
		if(!$this->hasParty()) {
			cog::emit("There is no party associated with this client."); #TODO logging
			return;
		}

		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);

		$req = new request('invite');
		$req->setHeaders($headers);
		$req->setParams([
			'database' => $database,
			'address' => $address,
			'public_key' => $publicKey,
		]);
		
		$res = $req->submitLocal();

		/* TODO:
		- make this known to the network, or better, just stop doing it altogether.
		-- in which case this entire part of the functionality should be eliminated.
		*/
		
		if(!$res['result']) {
			cog::emit($res['message']); #TODO logging
		}
	}

	public function sign($data) {
		if(!$this->hasParty()) {
			throw new Exception('No party found.');
		}
		$sig = $this->party->sign($data);
		return $sig;
	}

	public function getTransaction($env,$hash) {
		if(!$this->hasParty()) {
			return;
		}
		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$this->getAddress(),false);
		$req = new request('view');
		$req->setHeaders($headers);
		$req->setParams([
			'database' => $this->getEnvironment(),
			'hash' => $hash,
		]);
		$res = $req->submit(null,null,true);
		return $res['data'];
	}

	public function getSummary($database,$address) {
		if(!$this->hasParty()) {
			return;
		}
		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);
		$req = new request('summary');
		$req->setHeaders($headers);
		$req->setParams([
			'address' => $this->party->getAddress(),
			'public_key' => $this->party->getPublicKey()
		]);
		#$res = $req->submit(null,null,true);
		$res = $req->submitLocal();
		return $res['data'];
	}

	public function getCreditInfo($env,$address) {
		if(!$this->hasParty()) {
			return;
		}
		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);
		$req = new request('credit_info');
		$req->setHeaders($headers);
		$req->setParams([
			'address' => $this->party->getAddress(),
			'public_key' => $this->party->getPublicKey()
		]);
		$res = $req->submitLocal();
		return $res['data'] ? : [];
	}

	public function getCreditSummary($env,$address) {
		$creditInfo = $this->getCreditInfo($env,$address);

		$creditSummary = [];
		$agg = [];

		foreach($creditInfo as $transaction) {
		  foreach($transaction['request']['params']['inputs'] as $input) {
		    $agg[] = $input + ['timestamp' => $transaction['request']['headers']['timestamp']];
		  }
		}

		uasort($agg,function($a,$b) {
		  # -1 first for asc; 1 first for desc
		  if(strtotime($a['timestamp'] == strtotime($b['timestamp']))) {
		    return 0;
		  } elseif (strtotime($a['timestamp'] < strtotime($b['timestamp']))) {
		    return 1;
		  } else {
		    return -1;
		  }
		});

		foreach($agg as $input) {
		  $other = null;
		  if ($input['to'] == $this->getAddress()) {
		    $other = trim($input['from']);
		    $amt = $input['amount'];
		  } elseif ($input['from'] == $this->getAddress()) {
		    $other = trim($input['to']);
		    $amt = -$input['amount'];
		  } else {
		    continue; // why
		  }
		  if(!isset($creditSummary[$other])) {
		    $creditSummary[$other] = $input;
		    $creditSummary[$other]['amount'] = $amt;
		    unset($creditSummary['to']);
		    unset($creditSummary['from']);
		  } else {
		    $creditSummary[$other]['amount'] += $amt;
		    if(!empty($input['message'])) {
		      $creditSummary[$other]['message'] = $input['message'];
		    }
		    $creditSummary[$other]['timestamp'] = $input['timestamp'];
		  }
		}
		return $creditSummary;
	}

	public function getParty() {
		return $this->party;
	}

	public function contract($data) {
		$req = new request('contract');
		$inputs = [];
		foreach($data as $role => $input) {
			$input['role'] = $role;
			$inputs[] = $input;
		}
		$req->setParams(['inputs' => [$inputs]]);
		// 1. validate.
		// 2. broadcast
		$res = $req->broadcast();
		// 3. process response.
		cog::emit($res);
	}

	public function send($data) {
		$req = new request('send');
		$inputs = [
			'from' => (string)$data['from'],
			'to' => (string)$data['to'],
			'amount' => (float)$data['amount'],
			'message' => (string)$data['message'],
		];
		$req->setParams(['inputs' => [$inputs]]);
		// 1. validate.
		if($data['from'] != $this->getAddress()) {
		}
		// 2. broadcast
		$res = $req->broadcast();
		// 3. process response.
		cog::emit($res);
	}
}
?>
