<?php
trait mongoTests {
	public function testMultiDelete() {
		$db = new dbClient();

		// Insert

		$data = [];
		for($i = 0; $i < 3; $i++) {
			$data[] = ['why' => rand()];
		}

		$db->dbInsertMultiple('cogTest.blocks',$data);

		// Verify

		$rows = $db->dbQuery('cogTest.blocks',['why'=>['$ne'=>null]]);
		$this->assertTrue(count($rows) == count($data));
		foreach($rows as $row) {
			$match = false;
			foreach($data as $orig) {
				if($orig['why'] == $row['why']) {
					$match = true;
					break;
				}
			}
			$this->assertTrue($match);
		}

		// Update
		$criteria = [];
		foreach($rows as &$row) {
			$row['because'] = rand();
			$db->dbUpdate("cogTest.blocks",$row,['why'=>$row['why']]);
		}

		// Verify
		$updated_rows = $db->dbQuery('cogTest.blocks',['why'=>['$ne'=>null],'because'=>['$ne'=>null]]);
		$this->assertTrue(count($rows) == count($updated_rows));
		foreach($updated_rows as $row) {
			$match = false;
			foreach($rows as $orig) {
				if(@$orig['why'] == @$row['why'] && @$orig['because'] == @$row['because']) {
					$match = true;
					break;
				}
			}
			$this->assertTrue($match);
		}

		// Delete
		$db->dbDeleteMultiple("cogTest.blocks",$updated_rows);

		// Verify
		$updated_rows = $db->dbQuery('cogTest.blocks',['why'=>['$ne'=>null],'because'=>['$ne'=>null]]);
		$this->assertTrue($db->getCount('cogTest','blocks') == 0,"Collection cogTest.blocks is not empty.");
		$this->assertTrue(!count($updated_rows),"Rows were found after deletion:\n".print_r($updated_rows,1));
	}

	public function testMongoDriver() {
		$this->assertTrue(class_exists("MongoInterface"));
		emit("Mongo driver class found.",true);
	}
	public function testMongoCreateClient() {
		$this->testMongoDriver();
		$this->assertTrue(class_exists("dbClient"));
		emit("Creating MongoDB client...",true);
		$db = new dbClient();
		emit("Done.",true);
		return $db;
	}
	public function testMongoDbList($db = null) {
		if(empty($db)) {
			$db = $this->testMongoCreateClient();
		}
		$list = $db->showDatabases();
		$this->assertTrue(is_array($list));
		$this->assertTrue(count($list) > 0);
		return $list;
	}
	public function testMongoCreateCollection($dbName = "cogTest", $collection = "blocks",$attempt2 = false) {
		$db = $this->testMongoCreateClient();
		try {
			$db->collectionCreate($dbName,$collection);
		} catch (Exception $e) {
			$this->assertFalse($attempt2,"Failed first and second attempts to create Mongo collection '{$dbName}.{$collection}'.");
			$db->collectionDrop($dbName,$collection);
			$this->testMongoCreateCollection($dbName,$collection,true);
		}
		$collections = $db->showCollections($dbName);
		$this->assertTrue(in_array($collection,$collections));
	}
	public function testMongoDropCollection($dbName = "cogTest",$collection = "blocks") {
		$list = array_map(function($x){
			return $x['name'];
		}, $this->testMongoDbList());
		if(!in_array($dbName,$list)) {
			$this->testMongoCreateCollection($dbName,$collection);
		}
		$db = $this->testMongoCreateClient();
		$db->collectionDrop("$dbName","$collection");
		$network = $this->testNetwork();
		$this->assertTrue($network->length() == 0);
		$collections = $db->showCollections("$dbName");
		$this->assertFalse(in_array($collection,$collections),"Collection '{$collection}' found in collection list ".print_r($collections,1).".");
	}
	public function testMongoCreateDropCollection($dbName = "cogTest",$collection = "blocks",$attempt2 = false) {
		$this->testMongoCreateCollection($dbName,$collection);
		$this->testMongoDropCollection($dbName,$collection);
	}
	public function testCreditInfo() {
		$network = $this->testNetwork();
		$wallet = $this->testWallet();
		
		$this->testUpdateEndpoints($network,$wallet->getAddress());

		$db = $network->getDbClient();
		$data = $db->dbQuery("cogTest.blocks",['request.action' => ['$exists'=>true]]);
		$this->assertTrue(count($data) > 0);
		$empty = $db->dbQuery("cogTest.blocks",['request.action' => ['$exists'=>false]]);
		$this->assertTrue(count($empty) == 0);
		
		$creditInfo = $network->getCreditInfo($wallet->getAddress());
		cog::emit($creditInfo);
		$this->assertTrue(!empty($creditInfo));
	}
	public function testUpdateEndpoints($network = null,$addr = null) {
		$data = [];
		for($i = 0; $i < 10; $i++) {
			$rand0 = rand();
			$rand1 = rand();
			$rand2 = rand();
			$rand1a = rand(0,1);
			$rand3 = rand();
			$rand3a = rand(0,1);
			
			$add = [
				# for $or
				'action' => 'send',
				'key_a' => $rand0,
				'key_b' => $rand0,
				# for nested
				'params' => [
					'recipient' => $rand1a ? $rand2 : $rand1,
				]
			];
			# for $exists;
			if($rand3a) {
				$add['key_c'] = $rand3;
			} else {
				$add['key_d'] = $rand3;
			}
			$headers = cog::generate_header(null,0,$addr ? : $rand1a ? $rand1 : $rand2,false);
			$add = ['request' => $add];
			$add['request']['headers'] = $headers;
			$add['hash'] = cog::hash($add);
			$data[$add['hash']] = $add;
		}
		if(!is_object($network)) {
			$network = $this->testNetwork();
		}
		$db = $network->getDbClient();
		$db->dbInsertMultiple("cogTest.blocks",$data);

		### TEST 1 - QUERY ALL ###
		$rows = $db->dbQuery("cogTest.blocks",[]);
		$matches = 0;
		foreach($data as $item) {
			foreach($rows as $row) {
				if($item['hash'] == $row['hash']) $matches++;
			}
		}
		$this->assertTrue($matches == count($data),"Row hashes do not match.");

		### TEST 2 - by hash ###
		foreach($data as $row) {
			$res = $db->dbQuery("cogTest.blocks",['hash' => $row['hash']]);
			$this->assertTrue(count($res) == 1);
			$this->assertTrue(reset($res)['hash'] == $row['hash'],"Hash mismatch:\n".print_r([$row,reset($res)],1));
		}

		### TEST 3 - $exists ###
		$queries = [
			['request.key_c'=>['$exists' => true]],
			['request.key_c'=>['$exists' => false]],
			['request.key_d'=>['$exists' => true]],
			['request.key_d'=>['$exists' => false]],
		];
		foreach($queries as $q) {
			# query result
			$res = $db->dbQuery("cogTest.blocks",$q);

			$keys = array_keys($q);
			$k = reset($keys);
			$ksplit = explode(".",$k);
			$check_key = array_pop($ksplit);
			$v = $q[$k]['$exists'];

			# validate results against query
			foreach($res as $row) {
				if($v) {
					$this->assertTrue(isset($row['request'][$check_key]),"Failed to find '$check_key' in ".print_r($row,1));
				} else {
					$this->assertFalse(isset($row['request'][$check_key]),"Found '$check_key' in ".print_r($row,1));
				}
			}
		}

		// query transactions that have not been factored into endpoints
		$new_endpoints = $db->queryByKey("cogTest.blocks",['processed'=>['$exists'=>false]]);
		$this->assertTrue(count($new_endpoints) == count($data));
		/*
		foreach($new_endpoints as $t) {
			// preprocessing, may not be necessary
			unset($t['_id']);
			$exists = $network->endpointExists($t['hash']);
			$this->assertFalse($exists);
			if(!$exists) {
				// add transaction to endpoints
				$res = $db->dbInsert("cogTest.endpoints",$t);
				// remove referenced transaction from endpoints
				$res = $db->dbDelete("cogTest.endpoints",['hash' =>$t['request']['headers']['prevHash']]);
				// mark transasction as processed
				$t['processed'] = true;
				$res = $db->dbUpdate("cogTest.blocks",$t,['hash'=>$t['hash']]);
			}
		}
		*/

		### SEMIFINAL TEST 1 - UPDATE ENDPOINTS ###
		$network->updateEndpoints();
		$endpoints = $db->dbQuery("cogTest.endpoints",[]);
		$this->assertTrue(count($endpoints) == count($data));
		$blocks = $db->dbQuery("cogTest.blocks",['processed'=>['$exists' => 1]]);
		$this->assertTrue(count($blocks) == count($data));

	#	cog::emit($endpoints);
	#	cog::emit($blocks);
	}
}
?>
