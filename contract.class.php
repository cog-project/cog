<?php
class contract extends block {
	private $parties = [];
	private $signatures = [];

	private $arbitrators = [];
	private $guarantors = [];
	private $summary;

	private $terms;
	private $nonce;
	private $deadline;

	private $creator;
	private $creatorSignature;

	public function build($data) {
		$data = (array)$data;
		$headers = $data['headers'];
		unset($data['headers']);
		foreach($headers as $k => $v) {
			if(property_exists($this,$k)) {
				$this->$k = $v;
			}
		}
		$this->terms = $data;
		foreach($data as $k => $v) {
			if(property_exists($this,$k)) {
				$this->$k = $v;
			}
		}
	}
	
	public function __construct($terms = [],$nonce = null) {
		if(!empty($terms)) {
			$this->setTerms($terms);
		}
		if(!empty($nonce)) {
			$this->setNonce($nonce);
		}
	}

	public function getData($hash = false) {
		return array(
			'parties' => $this->parties,
			'arbitrators' => $this->arbitrators,
			'guarantors' => $this->guarantors,
			'summary' => $this->summary,

			'terms' => $this->terms,
			'nonce' => $this->nonce,
			'deadline' => $this->deadline,
			'creator' => $this->creator,
			#'signatures' => $this->signatures,
			# Guarantors,
			# Arbitrators,
		) + parent::getData($hash);
	}

	public function setParties($x) {
		$this->parties = $x;
	}

	public function getParties() {
		return $this->parties;
	}
	
	public function addParty() {
		$args = func_get_args();
		foreach($args as $arg) {
			if(is_string($arg)) {
				$this->parties[] = $arg;
			} else {
				$this->parties[] = $arg->getAddress();
			}
		}
		$this->parties = array_values($this->parties);
	}

	public function setTerms($terms) {
		$this->terms = $terms;
	}

	public function getTerms() {
		return $this->terms;
	}

	public function setNonce($nonce) {
		$this->nonce = $nonce;
	}

	public function getNonce() {
		return $this->nonce;
	}

	public function setDeadline($deadline) {
		$this->deadline = $deadline;
	}

	public function getDeadline() {
		return $this->deadline;
	}
	public function addSignature($signature) {
		$this->signatures[] = $signature;
	}
	public function getSignatures() {
		return $this->signatures;
	}
	public function setCreatorSignature($signature) {
		$this->creatorSignature = $signature;
	}
	public function getCreatorSignature() {
		return $this->creatorSignature;
	}
	public function setCreator($x) {
		$this->creator = $x;
	}
	public function getCreator() {
		return $this->creator;
	}
}
?>
