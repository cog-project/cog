<?php
class wallet {
	private $party;
	private $environment = 'cog';
	
	public function __construct() {
		$path = dirname(__FILE__).'/client_data/wallet.dat';
		if(file_exists($path)) {
			$this->party = unserialize(file_get_contents($path));
		}
	}

	public function getConfig() {
		$data = $this->localRequest([
			'action' => 'get_config',
			'params' => [
				'address' => $this->getAddress()
			]
		]);
		return $data['data'];
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

	public function getAddress() {
		return $this->party->getAddress();
	}

	public function getPublicKey() {
		return $this->party->getPublicKey();
	}

	public function getNumAddresses() {
		$res = $this->nodeRequest([
			'action' => 'address_count',
			'params' => []
		]);
	}

	public function getNumBlocks() {
		$res = $this->nodeRequest([
			'action' => 'blocks_count',
			'params' => []
		]);
	}

	public function localRequest($params = []) {
		return $this->nodeRequest($params);
	}

	public function addNode($data) {
		$res = $this->request([
			'action' => 'add_node',
			'params' => $data
		],'localhost',80);

		return $res;
	}

	public function sync($data) {
		// add node if it isn't already listed
		$this->addNode($data);

		// condense db to endpoints and retrieve current
		$response = $this->localRequest([
			'action' => 'get_endpoints',
			'params' => [1]
		]);

		$local_endpoints = $response['data'];

		// build request - retrieve remote endpoints
		// TODO all we actually need are hashes for the first stage
		$ip = $data['ip_address'];
		$port = $data['ip_port'];

		$request = [
			'action' => 'get_endpoints',
			'params' => [1],
		];
		$response = $this->request($request,$ip,$port);
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
			$response = $this->request([
				'action' => 'get_hash_history',
				'params' => [
					'endpoints' => $remote_endpoint_hashes,
					'startpoints' => $local_endpoint_hashes,
				]
			],$ip,$port);

			// validate and store transactions, and update endpoints
			$res = $this->localRequest([
				'action' => 'insert_transactions',
				'params' => ['data' => $response['data']]
			]);
		}
	}

	public function removeNode($data) {
		$res = $this->request([
			'action' => 'remove_node',
			'params' => $data
		],'localhost',80);

		return $res;
	}

	public function config($data) {
		$res = $this->localRequest([
			'action' => 'config',
			'params' => $data
		]);
		return $res;
	}

	public function listNodes() {
		$res = $this->request([
			'action' => 'list_nodes',
			'params' => [1]
		],'localhost',80);
		return $res['data'];
	}

	public function ping($data) {
		$timestamp = cog::get_timestamp();
		$request = [
			'action' => 'ping',
			'params' => [
				'ip_address' => $_SERVER['SERVER_HOST'],
				'ip_port' => $_SERVER['SERVER_PORT']
			]
		];
		$config = $this->getConfig();
		if(!empty($config)) {
			$request['params']['remote'] = [
				'ip_address' => $config['ip_address'],
				'ip_port' => $config['ip_port'],
				'address' => $config['address'],
				'public_key' => $config['public_key'],
				'nickname' => $config['nickname'],
			];
		}
		$res = $this->request($request,$data['ip_address'],$data['ip_port']);
		$time = $res['time'];
		$data = $res['data'];

		$data['ping_datetime'] = $timestamp;
		$data['request_time'] = $time;

		$this->addNode($data);
	}

	public function nodeRequest($params = [], $server = 'localhost', $port = 80) {
		$nodes = $this->listNodes();

		$params['signature'] = $this->sign($params);
		$params['environment'] = $this->getEnvironment();
		$res = $this->request($params,$server, $port);
		return $res;
	}

	public function request($params = [], $server, $port) {
		$scheme = ($port == 443 ? "https" : "http"); # TODO force ssl or something

		$url = "{$scheme}://{$server}/cog/server.php";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_PORT, $port);

		$res = curl_exec($ch);
		$info = curl_getinfo($ch);
		$time = [
			'total_time' => $info['total_time'],
			'namelookup_time' => $info['namelookup_time'],
			'connect_time' => $info['connect_time'],
			'pretransfer_time' => $info['pretransfer_time'],
			'starttransfer_time' => $info['starttransfer_time'],
			'redirect_time' => $info['redirect_time'],
		];
		curl_close($ch);

		$assoc = json_decode($res,true);
		if(!is_array($assoc)) {
			cog::print("Failed to decode response ({$url}).");
			cog::print("Request:\n".print_r($params,1));
			cog::print("Response:\n".$res);
		}
		if(!empty($assoc['misc'])) {
			cog::print($assoc['misc']);
		}
		$assoc['time'] = $time;
		return $assoc;
	}

	public function getIsRegistered() {
		$res = $this->nodeRequest([
			'action' => 'validate_address',
			'params' => [
				'address' => $this->getAddress()
			]
		]);
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
			cog::print("There is no party associated with this client."); #TODO logging
			return;
		}

		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);
		$req = [
			'headers' => $headers,
			'action' => 'invite',
			'params' => [
				'database' => $database,
				'address' => $address,
				'public_key' => $publicKey,
			]
		];
		$res = $this->nodeRequest($req);
		if(!$res['result']) {
			cog::print($res['message']); #TODO logging
		}
	}

	public function sign($data) {
		if(!$this->hasParty()) {
			return;
		}
		$sig = $this->party->sign($data);
		return $sig;
	}

	public function getTransaction($env,$hash) {
		if(!$this->hasParty()) {
			return;
		}
		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);
		$req = [
			'headers' => $headers,
			'action' => 'view',
			'params' => [
				'database' => $database,
				'hash' => $hash,
			]
		];
		$res = $this->nodeRequest($req);
		return $res['data'];
	}

	public function getSummary($database,$address) {
		if(!$this->hasParty()) {
			return;
		}
		$headers = cog::generate_header(cog::generate_zero_hash(),rand(),$address,false);
		
		$req = [
			'headers' => $headers,
			'action' => 'summary',
			'params' => [
				'database' => $database,
				'address' => $address,
				'public_key' => $publicKey
			]
		];
		$res = $this->nodeRequest($req);
		return $res['data'];
	}
}
?>
