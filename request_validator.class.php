<?php
class requestValidator {
	protected $request = [];
	protected $message = null;
	protected $result = 0;
	
	public function __construct($req) {
		$this->setRequest($req);
	}
	
	public function setRequest($req) {
		$this->request = $req;
	}

	public function getRequest() {
		return $this->request;
	}
	public function setError($str) {
		$this->setMessage($str);
		throw new Exception($str);
	}
	public function setMessage($str) {
		$this->message = $str;
	}
	public function getMessage() {
		return $this->message;
	}

	public function validateEnvironment() {
		if(empty($this->request['environment'])) {
			$this->setError('No environment was specified.');
		}
	}

	public function setResult($result) {
		$this->result = $result;
	}

	public function getResult() {
		return $this->result;
	}

	public function validateRequest() {
		$params = $this->getRequest();
		if(empty($params)) {
			$this->setError("No information was provided in the request.");
		}
	}

	public function getHeaders() {
		$params = $this->getRequest();
		if(empty($params['headers'])) {
			$this->setError('No headers were included.');
		}
		return $params['headers'];
	}

	public function validateVersion() {
		$params = $this->getHeaders();
		$headers = $params['headers'];
	}
	
	public function validateHeaders() {
		$this->validateVersion();
#		$this->validatePrevHash();
#		$this->validateTimestamp();
#		$this->validateAddress();
	}

	public function validateAction() {
		$params = $this->getRequest();
		if(empty($params['action'])) {
			$this->setError('No action was specified.');
		}
	}
	
	public function validate() {
		try {
			$this->validateRequest();
			$this->validateEnvironment();
			$this->validateHeaders();
			$this->validateAction();
		} catch (Exception $e) {
			return 0;
		}

		$this->setMessage('OK');
		$this->setResult(1);
		return 1;
	}
}
?>