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
				if($orig['why'] == $row['why'] && $orig['because'] == $row['because']) {
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
		$this->assertTrue(!count($updated_rows));
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
			$this->assertFalse($attempt2,print_r($e,1));
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
		$this->assertFalse(in_array($collection,$collections));
	}
	public function testMongoCreateDropCollection($dbName = "cogTest",$collection = "blocks",$attempt2 = false) {
		$this->testMongoCreateCollection($dbName,$collection);
		$this->testMongoDropCollection($dbName,$collection);
	}
}
?>
