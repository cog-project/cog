<?php
class block {
	private $hash;
	private $prevHash;
	private $timestamp;
	
	# data?

	public function getId() {
		return $this->id;
	}

	public function setId($x) {
		$this->id = $x;
	}

	public function getHash() {
		return $this->hash;
	}

	public function getData() {
		return array (
			'hash' => $this->getHash(),
			'prevHash' => $this->getPrevHash(),
			'timestamp' => $this->getTimestamp()
		);
	}

	public function __toString() {
		return json_encode($this->getData(), JSON_PRETTY_PRINT);
	}
	public function __toArray() {
		return $this->getData();
	}

	public function generateHash() {
		$this->hash = hash("sha256","{$this->prevHash},{$this->timestamp},".json_encode($this->getData(),JSON_PRETTY_PRINT));
	}

	public function getPrevHash() {
		return $this->prevHash;
	}

	public function setPrevHash($x) {
		$this->prevHash = $x;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function setTimestamp($x) {
		$this->timestamp = $x;
	}

	public function finalize($prev = null) {
		if(!empty($prev)) {
			$this->setPrevHash($prev);
		}
		$this->generateHash();
	}
}
?>