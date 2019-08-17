<?php
class dbClient {
	protected $client = null;

	public function __construct() {
		$check = trim(shell_exec("mongo --version"));
		if(strlen($check)) {
			$this->client = new MongoInterface();
		} else {
			$this->client = new FlatInterface();
		}
		# override
		$this->client = new FlatInterface();
	}

	public function getDbClient() {
		if(empty(cog::$dbClient)) {
			return $this->client;
		} elseif (cog::$dbClient == 'mongo') {
			return new MongoInferface(); # TODO static
		} elseif (cog::$dbClient == 'flat') {
			return new FlatInterface(); #TODO static
		}
	}

	public function queryByKey($table,$key = []) {
		$res = $this->dbQuery($table,$key);
		return $res;
	}

	public function command($cmd) {
		return $this->dbCommand("admin",$cmd);
	}

	public function dbCollection($db,$cmd) {
		return $this->getDbClient()->executeCommand($db,$cmd);
	}

	public function dbCommand($db,$cmd) {
		return $this->getDbClient()->executeCommand($db,$cmd);
	}

	public function dbQuery($db,$key,$opts = []) {
		$res = $this->getDbClient()->executeQuery($db,$key,$opts);
		return $res;
	}

	public function dbDelete($table,$key) {
		$this->dbDeleteMultiple($table,[$key]);
	}
	public function dbDeleteMultiple($table,$data) {
		$this->getDbClient()->deleteMultiple($table,$data);
	}
	public function dbInsert($db,$key) {
		return $this->dbInsertMultiple($db,[$key]);
	}
	public function dbInsertMultiple($db,$key = []) {
		$res = $this->getDbClient()->insertMultiple($db,$key);
		return $res;
	}
	
	public function dbUpdate($db,$key,$filter) {
		return $this->dbUpdateMultiple($db,[$key],$filter);
	}
	public function dbUpdateMultiple($db,$key = [], $filter = []) {
		$res = $this->getDbClient()->updateMultiple($db,$key,$filter);
		return $res;
	}

	public function showDatabases() {
		$res = $this->dbCommand("admin",["listDatabases"=>1]);
		return $res['databases'];
	}
	public function showCollections($db) {
		$res = $this->dbCommand($db,["listCollections"=>1]);
		if(is_array($res)) {
			$out = array_map(function($x) { return $x['name']; },$res);
		} else {
			$out = []; # may want to throw an exception depending on the nature of null res
		}
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
