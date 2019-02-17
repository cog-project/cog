<?php
class block {
	protected $hash;
	protected $prevHash;
	protected $timestamp;
	
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

	public function getData($hash = false) {
		$out = array (
			'prevHash' => $this->getPrevHash(),
			'timestamp' => $this->getTimestamp()
		);
		if($hash) {
			$out += array (
				'creatorSignature' => $this->getCreatorSignature(),
				'hash' => $this->getHash(),
			);
		}
		return $out;
	}

	public function toString($pretty = true) {
		return json_encode($this->getData(), $pretty ? JSON_PRETTY_PRINT : null);
	}
	public function __toArray($hash = false) {
		return $this->getData($hash);
	}

	public function generateHash() {
		$this->hash = cog::hash(
			"{$this->prevHash},{$this->timestamp},".json_encode($this->getData(),JSON_PRETTY_PRINT)
		);
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
		$this->setTimestamp(gmdate('Y-m-d H:i:s\Z'));
		$this->generateHash();
	}
}
?>
