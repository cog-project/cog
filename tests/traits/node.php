<?php
trait nodeTests {
	function testNode($network = null) {
		if(!is_object($network)) {
			$network = $this->testNetwork();
		}
		$node = new node($network,true);
		$node->setWallet($this->testWallet());
		$wallet = $node->getWallet();
		$this->assertTrue($wallet->getEnvironment() == 'cogTest',"Wallet environment is not cogTest.");
		return $node;
	}
	
	function testValidateAddress() {
		$node = $this->testNode();
		$req = new request('validate_address');
		$req->setParams(['address' => $node->getWallet()->getAddress()]);
		$res = $req->submitLocal();
	}

	function testNodeSummary() {
		$node = $this->testNode();
		$req = new request('summary');
		$req->setParams(['address' => $node->getWallet()->getAddress()]);
		$res = $req->submitLocal();
		$this->assertTrue(isset($res['data']));
		$data = $res['data'];
		$this->assertTrue(count($data) > 0);
		$this->assertTrue(isset($data['completed']));
		$this->assertTrue(is_array($data['completed']));
		return $data;
	}

	function testNodeView($hash = null) {
		$node = $this->testNode();
		if(empty($hash)) {
			$hash = $node->getNetwork()->put(['foo'=>'bar'],true);
			$this->assertTrue(strlen($hash) > 0);
		}
		$req = new request('view');
		$req->setParams(['hash'=>$hash]);
		$res = $req->submitLocal();
		$this->assertTrue(isset($res['data']));
		$this->assertTrue(is_array($res['data']));
		$data = $res['data'];
		$this->assertTrue($data['hash'] == $hash);
		$this->assertTrue(isset($data['request']));
		return $data;
	}

	function testNodeListNodes($empty = true) {
		$node = $this->testNode();
		if($empty) {
			$this->testAddNode();
		}
		$req = new request('list_nodes');
		$res = $req->submitLocal();
		$this->assertTrue(isset($res['data']));
		$data = $res['data'];
		$this->assertTrue(count($data) > 0);
	}

	# add node
	# remove node
	# ping

	function testGetEndpoints($empty = true) {
		$node = $this->testNode();
		$hash;
		if($empty) {
			$hash = $node->getNetwork()->getDbClient()->dbInsert("cogTest.endpoints",['foo'=>'bar']);
		}
		$req = new request('get_endpoints');
		$res = $req->submitLocal();
		$this->assertTrue(isset($res['data']),"No data field found in response:\n".print_r($res,1)."\nfor request:\n".request::$request);
		$data = $res['data'];
		$this->assertTrue(count($data) > 0,"Empty data field. Result:\n".print_r($res,1));
		return $res;
	}

	# get_hash_history

	# insert_transactions

	# config

	# get_config

	# probably no longer relevant

	function testInvite() {
	}

	function testAddressCount() {
	}

	function testBlocksCount() {
	}

	function testMessage() {
	}

	function testSummary() {
	}
}
?>
