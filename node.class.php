<?php
class node {
	private $network;
	
	public function __construct($network = null) {
		if(!is_object($network)) {
			$this->network = new network();
		} else {
			$this->network = $network;
		}
	}
	public function validateAddress($params) {
		if(!isset($params['params']['address'])) {
			throw new Exception("No address was specified.");
		}
		if(!preg_match("/^[a-f0-9]{64}$/",$params['params']['address'])) {
			throw new Exception("A malformed address was provided.");
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
			case 'register':
				$this->validateAddress($params);
				if($this->network->hasAddress([$params['params']['address']])) {
					throw new Exception("The specified address has already been registered with the network.");
				}
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
			$out['message'] = $e->getMessage();
		}
		
		$json = json_encode($out,JSON_PRETTY_PRINT);
		
		echo $json;
	}
}
?>
