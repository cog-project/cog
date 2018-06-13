<?php
trait mongoTests {
	public function testMongoDriver() {
		$this->assertTrue(class_exists("MongoDB\Driver\Manager"));
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
	public function testMongoCreateCollection($dbName = "cogTest", $collection = "testCollection",$attempt2 = false) {
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
	public function testMongoDropCollection($dbName = "cogTest",$collection = "testCollection") {
		$db = $this->testMongoCreateClient();
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
