<?php
trait mongoTests {
	public function testMongoDriver() {
		$this->assertTrue(class_exists("MongoDB\Driver\Manager"));
		emit("Mongo driver class found.",true);
	}
	public function testMongoCreate() {
		$this->testMongoDriver();
		$this->assertTrue(class_exists("dbClient"));
		emit("Creating MongoDB client...",true);
		$db = new dbClient();
		emit("Done.",true);
		return $db;
	}
	public function testMongoDbList($db = null) {
		if(empty($db)) {
			$db = $this->testMongoCreate();
		}
		$list = $db->showDatabases();
		$this->assertTrue(is_array($list));
		$this->assertTrue(count($list) > 0);
		return $list;
	}
	public function testMongoCreateCollection($dbName = "cogTest", $collection = "testCollection",$attempt2 = false) {
		$db = $this->testMongoCreate();
		try {
			$db->collectionCreate($dbName,$collection);
		} catch (Exception $e) {
			$this->assertFalse($attempt2,print_r($e,1));
			$db->collectionDrop($dbName,$collection);
			$this->testMongCreateCollection($dbName,$collection,true);
		}
		$collections = $db->showCollections($dbName);
		$this->assertTrue(in_array($collection,$collections));
	}
	public function testMongoDropCollection($dbName = "cogTest",$collection = "testCollection") {
		$db = $this->testMongoCreate();
		$db->collectionDrop($dbName,$collection);
		$collections = $db->showCollections($dbName);
		$this->assertFalse(in_array($collection,$collections));
	}
	public function testMongoCreateDropCollection($dbName = "cogTest",$collection = "testCollection",$attempt2 = false) {
		$this->testMongoCreateCollection($dbName,$collection);
		$this->testMongoDropCollection($dbName,$collection);
	}
}
?>
