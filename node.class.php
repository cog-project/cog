<?php
class node {
	private $network;
	private $party;
	
	public function __construct($network = null,$new = false) {
		if(!is_object($network)) {
			$this->network = new network();
		} else {
			$this->network = $network;
		}
		$this->wallet = new wallet($new); #TODO - account for lack of a party
	}
	public function getWallet() {
		return $this->wallet;
	}
	public function setWallet($x) {
		$this->wallet = $x;
	}
	public function getNetwork() {
		return $this->network;
	}
	public function validateHash($params,$key = 'hash') {
		if(!isset($params['params'][$key])) {
			throw new Exception("No hash was specified.");
		}
		if(!cog::check_hash_format($params['params'][$key])) {
			throw new Exception("A malformed hash was provided.");
		}
	}
	public function validateAddress($params,$address_key = 'address') {
		if(!isset($params['params'][$address_key])) {
			throw new Exception("No address was specified.");
		}
		if(!cog::check_hash_format($params['params'][$address_key])) {
			throw new Exception("A malformed address was provided.");
		}
	}
	public function validatePublicKey($params) {
		if(!isset($params['params']['public_key'])) {
			throw new Exception("No public key was specified.");
		}
	}
	public function validateSignature($params) {
		if(!isset($params['signature']) || empty($params['signature'])) {
			throw new Exception("No signature was provided.");
		}
		$signature = $params['signature'];
		unset($params['signature']);
		if(!cog::verify_signature(json_encode($params),$signature,$params['params']['public_key'])) {
			throw new Exception("Failed to validate signature.\n".json_encode($params));
		}
	}
	public function processAction($params) {
		$data = null;

		# TODO when the daemon becomes a thing, this will need to be reset to the default, probably
		$this->network->setDb($params['environment']);
		$this->validateSignature($params);
		
		switch($params['action']) {
			case 'blocks_count':
				$data = $this->network->length();
				break;
			case 'message':
				# public key or at least signature should match header address btw
				$this->validateAddress($params,'sender');
				$this->validateAddress($params,'recipient');
				if($params['params']['sender'] != $params['headers']['address']) {
					throw new Exception('Sender address does not match header address.');
				}
				$data = $this->network->getMessagesByAddress($params['headers']['address']);
				# We're probably going to want to encrypt messages, because otherwise it's transparent to :anyone:
				break;
			case 'summary':
				$this->validateAddress($params,'address');
				$data = $this->network->getSummary($params['params']['address'],$params['headers']['address']);
				break;
			case 'view':
				$this->validateHash($params,'hash');
				$data = $this->network->get($params['params']['hash']);
				break;
			case 'list_nodes':
				$data = $this->network->listNodes();
				break;
			case 'add_node':
				// TODO validate params
				// TODO no redundancies
				$data = $params['params'];
				$data['ip_port'] = (int) $data['ip_port'];
				if(empty($data['ip_address'])) {
					throw new Exception("add_node: An invalid address was provided. ({$data['ip_address']})");
				}
				if(empty($data['ip_port'])) {
					throw new Exception("add_node: An invalid port was provided. ({$data['ip_port']})");
				}
				$data = $this->network->addNode($data);
				break;
			case 'remove_node':
				// TODO validate params
				// TODO no redundancies
				$data = $params['params'];
				$data['ip_port'] = (int) $data['ip_port'];
				if(empty($data['ip_address'])) {
					throw new Exception("remove_node: An invalid address was provided. ({$data['ip_address']})");
				}
				if(empty($data['ip_port'])) {
					throw new Exception("remove_node: An invalid port was provided. ({$data['ip_port']})");
				}
				$data = $this->network->removeNode($data);
				break;
			case 'ping':
				$data = [
					'ip_address' => $_SERVER['SERVER_NAME'],
					'ip_port' => $_SERVER['SERVER_PORT'],
					'address' => $this->wallet->getAddress(),
					'public_key' => $this->wallet->getPublicKey(),
					'ping_datetime' => '',
					'local_datetime' => cog::get_timestamp(),
					'request_time' => []
				];

				$config = $this->wallet->getConfig();
				if(!empty($config['nickname'])) {
					$data['nickname'] = $config['nickname'];
				}

				if(!empty($params['params']['remote'])) {
					// TODO validate fields, signature
					$params['remote']['ping_datetime'] = cog::get_timestamp();
					$this->network->addNode($params['params']['remote']);
				}
				break;
			case 'get_endpoints':
				$data = $this->network->getEndpoints();
				break;
			case 'get_hash_history':
				// TODO validate endpoints
				if(!isset($params['params']['endpoints'])) {
					throw new Exception("No endpoints were provided.");
				}
				// TODO validate startpoints
				if(!isset($params['params']['startpoints'])) {
					$params['params']['startpoints'] = [];
				}
				$end = $params['params']['endpoints'];
				$start = $params['params']['startpoints'];
				$data = $this->network->getHistory($start,$end);
				break;
			case 'insert_transactions':
				$info = $this->network->updateTransactions($params['params']['data']);
				$data = $info;
				break;
			case 'config':
				// TODO validate
				$data = $this->network->setConfig($params['params']);
				break;
			case 'get_config':
				// TODO validate
				$data = $this->network->getConfig($params['params']['address']);
				break;
			case 'credit_info':
				$data = $this->network->getCreditInfo($params['params']['address']);
				break;
			case 'contract':
				// validate
				// put
				$hash = cog::hash($params);
				if(!$this->network->hasHash($hash)) {
					$res = $this->network->put($params);
					// TODO re-broadcast recommended
					// TODO validate against existing data and endpoints, update endpoints
				} else {
					$res = null;
				}
				// 5. Update State - recalculate (safe) or just update incrementally (unsafe)
				$data = $res;				
				// TODO addendums for signatures, revisions, etc.
				break;
			case 'send':
				// 1. Validate Address x Public Key - use RequestValidator
				// 2. Validate Signature x Public Key - use ReqeustValidator
				// 3. Validate Transaction
				if($params['params']['from'] != $params['headers']['address']) {
				}
				// 4. Store Transaction
				$hash = cog::hash($params);
				if(!$this->network->hasHash($hash)) {
					$res = $this->network->put($params);
					// TODO re-broadcast recommended
					// TODO validate against existing data and endpoints, update endpoints
				} else {
					$res = null;
				}
				// 5. Update State - recalculate (safe) or just update incrementally (unsafe)
				$data = $res;
				break;
			default:
				throw new Exception("Action '{$params['action']}' was not found.");
				break;
		}
		return $data;
	}
	public function processRequest() {
		header('Content-Type: application/json');
		
		// TODO POST preferred, but we can worry about that later
		$params = $_REQUEST;
		
		$out = [			
			'result' => 1,
		];

		try {
			$rv = new requestValidator($params);
			$result = $rv->validate();
			if(!$result) {
				throw new Exception($rv->getMessage());
			}
			$data = $this->processAction($params);
			$out['message'] = 'OK';
			if($data !== null) {
				$out['data'] = $data;
			}
		} catch (Exception $e) {
			$out['result'] = 0;
			$out['message'] = $e->getMessage()."\nRequest:\n".json_encode($params,JSON_PRETTY_PRINT);
			$out['trace'] = $e->getTrace();
			$out['_REQUEST'] = $_REQUEST;
		}

		if(!empty($GLOBALS['misc'])) {
			$out['misc'] = $GLOBALS['misc'];
		}
		
		$json = json_encode($out);
		
		echo $json;
	}
}
?>
