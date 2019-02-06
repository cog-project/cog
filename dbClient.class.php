<?php
class dbClient {
	protected $client = null;

	public function __construct() {
		$this->client = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	}

	public function queryByKey($table,$key = []) {
		$res = $this->dbQuery($table,$key);
		return $res->toArray();
	}

	public function command($cmd) {
		return $this->dbCommand("admin",$cmd);
	}

	public function dbCollection($db,$cmd) {
		return $this->client->executeCommand($db,new \MongoDB\Driver\Collection($cmd));
	}

	public function dbCommand($db,$cmd) {
		return $this->client->executeCommand($db,new \MongoDB\Driver\Command($cmd));
	}

	public function dbQuery($db,$key) {
		return $this->client->executeQuery($db,new \MongoDB\Driver\Query($key));
	}

	public function dbDelete($table,$key) {
		$this->dbDeleteMultiple($table,[$key]);
	}
	public function dbDeleteMultiple($table,$key) {
		$bulk = new MongoDB\Driver\BulkWrite;
		foreach($key as $v) {
			$bulk->delete($v);
		}
		$this->client->executeBulkWrite($table,$bulk);
	}
	public function dbInsert($db,$key) {
		return $this->dbInsertMultiple($db,[$key]);
	}
	public function dbInsertMultiple($db,$key = []) {
		$bulk = new MongoDB\Driver\BulkWrite;
		foreach($key as $v) {
			$bulk->insert($v);
		}
		$res = $this->client->executeBulkWrite($db,$bulk);
		return $res;
	}
	
	public function dbUpdate($db,$key) {
		return $this->dbUpdateMultiple($db,[$key]);
	}
	public function dbUpdateMultiple($db,$key = []) {
		$bulk = new MongoDB\Driver\BulkWrite;
		foreach($key as $v) {
			$bulk->update(
				['ip_address' => $v['ip_address'], 'ip_port' => $v['ip_port']], //filter
				$v //replacement
			);
		}
		$res = $this->client->executeBulkWrite($db,$bulk);
		return $res;
	}

	public function showDatabases() {
		$res = $this->dbCommand("admin",["listDatabases"=>1])->toArray();
		return array_shift($res)->databases;
	}
	public function showCollections($db) {
		$res = $this->dbCommand($db,["listCollections"=>1])->toArray();
		$out = array_map(function($x) { return $x->name; },$res);
		return $out;
	}

	public function getCount($db,$table,$query = []) {
		$cmd = ['count'=>$table];
		if(!empty($query)) {
			$cmd = array_merge($cmd, $query);
		}
		$res = $this->dbCommand($db,$cmd);
		$array = $res->toArray();
		$row = reset($array);
		if($row->ok) {
			return $row->n;
		} else {
			return null;
		}
	}
	public function collectionDrop($db,$collection) {
		$res = $this->dbCommand("$db",["drop" => $collection]);
	}
	public function collectionCreate($db,$collection) {
		$res = $this->dbCommand("$db",["create" => $collection]);
	}
}
?>
