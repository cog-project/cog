<?php
trait walletTests {
	function testGetConfig($wallet = null) {
		if(!is_object($wallet)) {
			$wallet = $this->testWallet();
		}
		$cfg = $wallet->getConfig();
		$this->assertTrue(is_array($cfg));
		if(count($cfg)) {
			$this->assertTrue(isset($cfg['_id']));
		}
		return $cfg;
	}

	function testListNodes() {
		$wallet = $this->testWallet();
		$nodes = $wallet->listNodes();
		$this->assertTrue(is_array($nodes));
		foreach($nodes as $node) {
			$this->assertTrue(isset($node['_id']));
			$this->assertTrue(strlen($node['_id']) > 0);
			$this->assertTrue(isset($node['ip_address']));
			$this->assertTrue(strlen($node['ip_address']) > 0);
			$this->assertTrue(isset($node['ip_port']));
			$this->assertTrue(strlen($node['ip_port']) > 0);
			$this->assertTrue(is_numeric($node['ip_port']));
		}
		return $nodes;
	}
	function testInitialize($params = array()) {
		$out = [];
		foreach($params as $i => &$v) {
			$name_type = explode(":",$i);
			$name = $name_type[0];
			$type = $name_type[1];
			if(empty($v)) {
				$f = "test".ucfirst($type);
				$out[$name] = $this->$f();
			} else {
				$out[$name] = $v;
			}
			unset($params[$i]);
		}
		return $out;
	}

	function testWallet() {
		$this->assertTrue(class_exists('wallet'));
		$wallet = new wallet(true);
		$wallet->setEnvironment('cogTest');
		$party = $this->testParty();
		$wallet->setParty($party);
		return $wallet;
	}

	function addNode($wallet = null,$ip,$port) {
		if(!is_object($wallet)) {
			$wallet = $this->testWallet();
		}
		$res = $wallet->addNode([
			'ip_address' => $ip,
			'ip_port' => $port
		]);
		return $res;
	}
	function testAddNode($ip = 'localhost',$port = 80) {
		$res = $this->addNode(null,$ip,$port);
		
		$this->assertTrue(is_array($res));
		$this->assertTrue($res['result'] == 1,"Result is not 1 in response:\n".print_r($res,1));
		$this->assertTrue($res['message'] == 'OK');
		$this->assertTrue(isset($res['time']));
		$this->assertTrue(is_array($res['time']));
		$this->assertTrue(count($res['time']) > 0);

		$nodes = $this->testListNodes();
		$this->assertTrue(count($nodes) > 0);
	}

	function testAddInvalidNode() {
		$res = $this->addNode(null,'localhost','eighty');
		$nodes = $this->testListNodes();
		$this->assertTrue(count($nodes) == 0);		
	}

	function testSync($ip = 'localhost', $port = 80) {
		$wallet = $this->testWallet();
		$wallet->sync(['ip_address'=>$ip,'ip_port'=>$port]);
		$nodes = $this->testListNodes();
		$req = new request('get_endpoints');
		$response = $req->submitLocal();
		$this->assertTrue($response['result'] == 1);
		# TODO verify endpoints
	}

	function testRemoveNode() {
		$port = rand(444,9001);
		$this->testAddNode('localhost',$port);
		$wallet = $this->testWallet();
		$res = $wallet->removeNode([
			'ip_address' => 'localhost',
			'ip_port' => $port
		]);
		$nodes = $this->testListNodes();
		$success = true;
		cog::emit($nodes);
		foreach($nodes as $node) {
			if($node['ip_address'] == 'localhost' && $node['ip_port'] == $port) {
				$success = false;
				break;
			}
		}
		$this->assertTrue($success,"Failed to remove node localhost:{$port}");
	}

	function testConfig() {
		$wallet = $this->testWallet();
		$cfg = [
			'ip_address' => 'localhost',
			'ip_port' => rand(444,9001),
			'address' => $wallet->getAddress(),
			'public_key' => $wallet->getPublicKey(),
			'nickname' => md5(rand()),
		];
		$res = $wallet->config($cfg);
		
		$this->assertTrue($res['result'] == 1);
		/* what was this
		$this->assertTrue(isset($res['data']));
		$this->assertTrue(is_array($res['data']));
		$this->assertTrue(!empty($res['data']));
		*/
		
		$cfgp = $this->testGetConfig($wallet);
		foreach($cfg as $k => $v) {
			if(isset($cfgp[$k])) {
				 $this->assertTrue($v == $cfgp[$k]);
			} else {
				$this->assertTrue($v == null);
			}
		}
	}

	function testRequestPeers($wallet = null) {
		if(!is_object($wallet)) {
			$wallet = $this->testWallet();
		}
		$this->testAddNode();
		$this->testPing();
		$peers = $wallet->requestPeers(['ip_address'=>'localhost','ip_port'=>80]);
		$this->assertTrue(count($peers) > 0);
		return $peers;
	}

	function testPing($ip = 'localhost', $port = 80) {
		$wallet = $this->testWallet();
		$wallet->ping([
			'ip_address' => $ip,
			'ip_port' => $port
		]);
		$nodes = $this->testListNodes();
		$this->assertTrue(count($nodes) > 0, 'No nodes were found.');
		
		foreach($nodes as $node) {
			if($node['ip_address'] == $ip && $node['ip_port'] == $port) break;
		}
		$this->assertTrue(is_array($node),'Node found does not match ping credentials.');
		$this->assertTrue(count($node) > 0,'There was an error with the node data returned.');
	}

	function testGetIsRegistered() {
	}

	function testRegister() {
	}

	function testGetTransaction() {
		$hash = md5(rand());
		$wallet = $this->testWallet();
		
		$network = network::getInstance();
		$res1 = $network->getDbClient()->dbInsert("cogTest.blocks",['hash' => $hash, 'foo' => 'bar','abc'=>'def']);
		$res2 = $network->getDbClient()->dbQuery("cogTest.blocks",['hash' => $hash]);
		
		$t = $wallet->getTransaction('cogTest',$hash);
		emit($t);
		# TODO no output
	}

	function testGetSummary() {
		$wallet = $this->testWallet();
		$network = network::getInstance();
		$hash = md5(rand());
		$res1 = $network->getDbClient()->dbInsert("cogTest.endpoints",[
			'hash' => $hash,
			'headers' => [
				'address' => $wallet->getParty()->getAddress()
			],
			'request' => [
				'action' => 'send',
				'params' => [
					'inputs' => [
						'from' => $wallet->getAddress(),
						'to' => '909090909'
					],
				]
			],
			'foo' => 'bar'
		]);
		$summary = $wallet->getSummary('cogTest',$wallet->getParty()->getAddress());
		$res = $network->getDbClient()->dbQuery("cogTest.endpoints",[]);
		$this->assertTrue(count($res) > 0,"No transactions found in database.");
		$this->assertTrue(count($summary) > 0,"Completed transactions list is empty:\n".print_r($summary,1));
	}
}
?>
