<?php
class dbClient {
	protected $client = null;

	public function __construct() {
		$this->client = new MongoInterface();
	}

	public function queryByKey($table,$key = []) {
		$res = $this->dbQuery($table,$key);
		return $res;
	}

	public function command($cmd) {
		return $this->dbCommand("admin",$cmd);
	}

	public function dbCollection($db,$cmd) {
		return $this->client->executeCommand($db,$cmd);
	}

	public function dbCommand($db,$cmd) {
		return $this->client->executeCommand($db,$cmd);
	}

	public function dbQuery($db,$key,$opts = []) {
		$res = $this->client->executeQuery($db,$key,$opts);
		return $res;
	}

	public function dbDelete($table,$key) {
		$this->dbDeleteMultiple($table,[$key]);
	}
	public function dbDeleteMultiple($table,$data) {
		$this->client->deleteMultiple($table,$data);
	}
	public function dbInsert($db,$key) {
		return $this->dbInsertMultiple($db,[$key]);
	}
	public function dbInsertMultiple($db,$key = []) {
		$res = $this->client->insertMultiple($db,$key);
		return $res;
	}
	
	public function dbUpdate($db,$key,$filter) {
		return $this->dbUpdateMultiple($db,[$key],$filter);
	}
	public function dbUpdateMultiple($db,$key = [], $filter = []) {
		$res = $this->client->updateMultiple($db,$key,$filter);
		return $res;
	}

	public function showDatabases() {
		$res = $this->dbCommand("admin",["listDatabases"=>1]);
		return $res['databases'];
	}
	public function showCollections($db) {
		$res = $this->dbCommand($db,["listCollections"=>1]);
		$out = array_map(function($x) { return $x['name']; },$res);
		return $out;
	}

	public function getCount($db,$table,$query = []) {
		$cmd = ['count'=>$table];
		if(!empty($query)) {
			$cmd = array_merge($cmd, $query);
		}
		$res = $this->dbCommand($db,$cmd);
		return $res;
	}
	public function collectionDrop($db,$collection) {
		$res = $this->dbCommand("$db",["drop" => $collection]);
	}
	public function collectionCreate($db,$collection) {
		$res = $this->dbCommand("$db",["create" => $collection]);
	}
}
?>
