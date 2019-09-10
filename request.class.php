<?php
class request {
	protected $headers = [];
	protected $action = null;
	protected $params = [];

	protected $generated_params = [];
	protected $generated_signature = [];

	protected $server = null;
	protected $port = null;

	protected $nodes = [];

	protected $resultArray = [];

	public static $request = null;

	public function setNodes($nodes) {
		$this->nodes = $nodes;
	}
	public function setAction($action) {
		$this->action = $action;
	}
	public function getAction() {
		return $this->action;
	}	
	public function __construct($action) {
		$this->setAction($action);
	}
	
	public function toArray() {
		$ar = [
			'headers' => $this->getHeaders(),
			'action' => $this->getAction(),
			'params' => $this->getParams(),
		];
		if(empty($ar['headers'])) {
			unset($ar['headers']);
		}
		return $ar;
	}
	public function toString() {
		return json_encode($this->toArray());
	}
	public function getParams() {
		if(empty($this->params)) return ["1"];
		else return $this->params;
	}
	public function setParams($data) {
		$this->params = $data;
	}
	public function submitLocal() {
		return $this->submit("localhost","80");
	}
	static function request($server,$port,$params,$return_raw = false) {
		$scheme = ($port == 443 ? "https" : "http"); # TODO force ssl or something
		$url = "{$scheme}://{$server}/cog/server.php";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST,true);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$request_str = http_build_query($params);
		self::$request = json_encode($params);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request_str);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_PORT, (int)$port);

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

		if($return_raw) {
			return $res;
		}

		$assoc = json_decode($res,true);
		
		if(!is_array($assoc)) {
			cog::emit("Failed to decode response ({$url}).");
			cog::emit("Request:\n".print_r($params,1));
			cog::emit("Response:\n".$res);
			return;
		}

		$resp = json_decode($assoc['server_response'],true);

		if(!cog::verify_signature($assoc['server_response'],$assoc['signature'],$resp['headers']['publicKey'])) {
			cog::emit("Failed to verify response signature.");
		}

		if(!empty($resp['misc'])) {
			cog::emit($resp['misc']);
		}
		$resp['time'] = $time;
		return $resp;
	}
	public function setHeaders($headers) {
		$this->headers = array_merge($this->headers,$headers);
	}
	public function setPrevHash($x) {
		$this->headers['prevHash'] = $x;
	}
	public function getPrevHash() {
		if(isset($this->headers['prevHash'])) {
			return $this->headers['prevHash'];
		} else return null;
	}
	public function getHeaders() {
		return cog::generate_header(
			($this->getPrevHash() ? : cog::generate_zero_hash()),
			0,
			cog::get_wallet()->getAddress(),
			false,
			cog::get_wallet()->getPublicKey()
		);
	}
	public function broadcast() {
		$wallet = cog::get_wallet();
		$nodes = $wallet->listNodes();

		$this->generateParams();
		
		$params = $this->generated_params;
		$sig = $this->generated_signature;

		$results = [];
		foreach($nodes as $node) {
			$server = $node['ip_address'];
			$port = $node['ip_port'];
			$res = self::request($server,$port,['node_request'=>json_encode($params),'signature'=>$sig],$return_raw);
			$results["{$server}:{$port}"] = $res;
		}
		return $results;
	}
	public function generateParams() {
		$params = $this->toArray();
		$params['environment'] = network::getInstance()->getDb();
		$wallet = cog::get_wallet();
		$sig = $wallet->sign($params);

		$this->generated_params = $params;
		$this->generated_signature = $sig;
	}
	public function submit($server = null,$port = null,$use_nodes = false,$return_raw = false) {
		if(empty($server)) {
			$server = $this->server;
		}
		if(empty($port)) {
			$port = $this->port;
		}

		$wallet = cog::get_wallet();
		if($use_nodes) {
			$nodes = $wallet->listNodes();
		} else {
			if(empty($server)) {
				throw new Exception("No server was specified.");
			}
			if(empty($port)) {
				throw new Exception("No port was specified.");
			}
			$nodes = [['ip_address'=>$server,'ip_port'=>$port]];
		}

		$this->generateParams();

		$params = $this->generated_params;
		$sig = $this->generated_signature;

		foreach($nodes as $node) {
			$server = $node['ip_address'];
			$port = $node['ip_port'];
			$res = self::request($server,$port,['node_request'=>json_encode($params),'signature'=>$sig],$return_raw);
			if(is_array($res) && !$return_raw) {
				return $res;
			} elseif (is_object(json_decode($res)) && $return_raw) {
				return $res;
			}
		}
		// bad bad not good
	}
}
?>
