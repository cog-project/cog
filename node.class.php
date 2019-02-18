<?php
class node {
	private $network;
	private $party;
	
	public function __construct($network = null) {
		if(!is_object($network)) {
			$this->network = new network();
		} else {
			$this->network = $network;
		}
		$this->wallet = new wallet(); #TODO - account for lack of a party
	}
	public function validateHash($params,$key = 'hash') {
		if(!isset($params['params'][$key])) {
			throw new Exception("No hash was specified.");
		}
		if(!preg_match("/^[a-f0-9]{64}$/",$params['params'][$key])) {
			throw new Exception("A malformed hash was provided.");
		}
	}
	public function validateAddress($params,$address_key = 'address') {
		if(!isset($params['params'][$address_key])) {
			throw new Exception("No address was specified.");
		}
		if(!preg_match("/^[a-f0-9]{64}$/",$params['params'][$address_key])) {
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
		if(!cog::verify_signature(json_encode($params,JSON_PRETTY_PRINT),$signature,$params['params']['public_key'])) {
			throw new Exception("Failed to validate signature.\n".json_encode($params,JSON_PRETTY_PRINT));
		}
	}
	public function validateRequest($params) {
		$data = null;
		if(empty($params)) {
			throw new Exception("No information was provided in the request.");
		}
		if(empty($params['action'])) {
			throw new Exception('No action was specified.');
		}
		if(empty($params['params'])) {
			throw new Exception("No params were specified.");
		}
		switch($params['action']) {
			case 'validate_address':
				$this->validateAddress($params);
				$data = $this->network->hasAddress([$params['params']['address']]);
				break;
			case 'invite':
				$this->validateAddress($params);
				$this->validatePublicKey($params);
				if($this->network->hasAddress([$params['params']['address']])) {
					throw new Exception("The specified address has already been registered with the network.");
				}
				$this->validateSignature($params);
				$res = $this->network->put($params);
				break;
			case 'address_count':
				$this->validateAddress($params);
				$data = $this->network->getNumAddresses();
				break;
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
				$data = $this->network->getSummary($params['params']['address']);
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
				$data = $this->network->addNode($params['params']);
				break;
			case 'remove_node':
				// TODO validate params
				// TODO no redundancies
				$data = $this->network->removeNode($params['params']);
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
				break;
			case 'get_endpoints':
				$data = $this->network->getEndpoints();
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
			$data = $this->validateRequest($params);
			$out['message'] = 'OK';
			if($data !== null) {
				$out['data'] = $data;
			}
		} catch (Exception $e) {
			$out['result'] = 0;
			$out['message'] = $e->getMessage()."\nRequest:\n".json_encode($params,JSON_PRETTY_PRINT);
		}
		
		$json = json_encode($out,JSON_PRETTY_PRINT);
		
		echo $json;
	}
}
?>
