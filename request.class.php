<?php
class request {
	protected $id;
	protected $action;
	
	public function setAction($action) {
		$this->action = $action;
	}
	public function getAction() {
		return $this->action;
	}

	public function setId($x) {
		$this->id = $x;
	}
	public function getId() {
		return $this->id;
	}
	public function generateId() {
		$id = bin2hex(random_bytes(32)); #64
		$this->setId($id);
	}
	
	public function __construct($action) {
		$this->generateId();
		$this->setAction($action);
	}
	
	public function toArray() {
		return [
			'id' => $this->getId(),
			'action' => $this->getAction(),
		];
	}
	public function toString() {
		return json_encode($this->toArray(),JSON_PRETTY_PRINT);
	}
}

class inviteRequest extends request {
	public function __construct() {
		parent::__construct("invite");
	}
	public function toArray() {
		$array = [
		];
		return array_merge(parent::toArray(),$array);
	}
}
?>