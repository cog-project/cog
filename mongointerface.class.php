<?php
// I'm going to hell.
class ObjectId {
}
class MongoInterface {
	public function __construct() {
	}
	public function exec($db,$cmd) {
		$safe_db = escapeshellarg($db);
		$safe_cmd = escapeshellarg($cmd);
		$full_cmd = "mongo --quiet --eval {$safe_cmd} {$safe_db}";
		$res = shell_exec($full_cmd);
		$out = json_decode($res,true);
		return $out;
	}
	public function listCollections($db) {
		$cmd = "db.getCollectionInfos()";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function createCollection($db,$collection) {
		$cmd = "db.createCollection(".escapeshellarg($collection).")";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function dropCollection($db,$collection) {
		$cmd = "db.{$collection}.drop()";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function listDatabases($db) {
		$cmd = 'db.adminCommand({listDatabases:1})';
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function countCollection($db,$collection) {
		$cmd = "db.{$collection}.count()";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function executeCommand($db,$cmd) {
		$keys = array_keys($cmd);
		$key = $keys[0];
		$val = $cmd[$key];
		switch($key) {
			case 'count':
				$res = $this->countCollection($db,$val);
				break;
			case 'create':
				$res = $this->createCollection($db,$val);
				break;
			case 'drop':
				$res = $this->dropCollection($db,$val);
				break;
			case 'listDatabases':
				$res = $this->listDatabases($db);
				break;
			case 'listCollections':
				$res = $this->listCollections($db);
				break;
			default:
				throw new Exception("Unsupported MongoDB Command: $key");
				break;
		}
		return $res;
	}
	public function query($db,$collection,$query,$opts = []) {
		if(!empty($query)) {
			$json = json_encode($query);
		} else {
			$json = '{}';
		}
		$cmd = "db.{$collection}.find($json)";
		if(!empty($opts)) {
			foreach($opts as $key => $val) {
				$val = json_encode($val);
				$cmd .= ".{$key}($val)";
			}
		}
		$cmd = "$cmd.map(function(x){x._id = x._id.str; return x});";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function executeQuery($db,$query,$opts = []) {
		$split = explode(".",$db);
		$db = $split[0];
		$collection = $split[1];
		$res = $this->query($db,$collection,$query,$opts);
		return $res;
	}
	public function insertMultiple($db,$data) {
		$split = explode(".",$db);
		$db = $split[0];
		$collection = $split[1];
		$data = json_encode($data);
		$cmd = "db.{$collection}.insert($data)";
		$res = $this->exec($db,$cmd);
	}
	public function updateMultiple($db,$data,$filter) {
		$split = explode(".",$db);
		$db = $split[0];
		$collection = $split[1];

		$args = [];
		foreach($data as $v) {
			unset($v['_id']);
			$args[] = ['updateOne' => ['filter' => $filter, 'update' => $v]];
		}

		$json = json_encode($args);
		$cmd = "db.{$collection}.bulkWrite($json)";
		$res = $this->exec($db,$cmd);
		return $res;
	}
	public function deleteMultiple($table,$data) {
		$split = explode(".",$table);
		$db = $split[0];
		$collection = $split[1];

		$args = [];
		foreach($data as $v) {
			unset($v['_id']);
			$args[] = ['deleteOne' => ['filter' => $v]];
		}

		$json = json_encode($args);
		$cmd = "db.{$collection}.bulkWrite($json)";
		$res = $this->exec($db,$cmd);
		return $res;
	}
}
?>
