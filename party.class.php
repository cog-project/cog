<?php
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

		$keypair = cog::generate_keypair();
		foreach($keypair as $k => $v) {
			$this->$k = $v;
		}

		// Use public key to generate uniqid/address
		# WARNING: likely not intrinsically valid - hashes the string, not the binary
		$this->addr = cog::generate_addr($this->pub);
	}

	public function encrypt($data) {
		return cog::encrypt($this->pub,$data);
	}

	public function decrypt($data) {
		return cog::decrypt($this->priv,$data);
	}

	public function decrypt_public($data) {
		return cog::decrypt_public($this->pub,$data);
	}

	public function buildContract($terms = null,$nonce = null) {
		$out = new contract($terms,$nonce);
		$out->setCreator($this->getAddress());
		return $out;
	}

	public function sign($contract) {
		if(is_object($contract)) {
			$contract = $contract->toString(false);
		} elseif (is_array($contract)) {
			$contract = json_encode($contract);
		}
		$res = cog::sign(
			$this->priv,
			$contract
		);
		return $res;
	}
}
?>
