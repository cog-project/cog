<?php
class wallet {
	private $party;
	
	public function __construct() {
		
	}

	public static function init() {
		return new wallet();
	}

	public function hasParty() {
		return is_object($this->party) && get_class($this->party) == 'party';
	}

	public function createParty() {
		$p = new party();
		$this->party = $p;
	}

	public function getAddress() {
		return $this->party->getAddress();
	}

	public function nodeRequest($params = [], $server = 'localhost') {
		$url = "http://{$server}/cog/server.php";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		$res = curl_exec($ch);
		curl_close($ch);
		$assoc = json_decode($res,true);
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
}
?>
