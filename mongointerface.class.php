<?php
// I'm going to hell.
class ObjectId {
}

interface DatabaseInterface {
	public function exec($db,$cmd);
	public function listCollections($db);
	public function createCollection($db,$collection);
	public function dropCollection($db,$collection);
	public function listDatabases($db);
	public function countCollection($db,$collection);
	public function executeCommand($db,$cmd);
	public function query($db,$collection,$query,$opts = []);
	public function executeQuery($db,$query,$opts = []);
	public function insertMultiple($db,$data);
	public function updateMultiple($db,$data,$filter);
	public function deleteMultiple($table,$data);
}

class MongoInterface implements DatabaseInterface {
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

class SleekInterface implements DatabaseInterface {
	protected $dbs = [];
	public function __construct() {
	}
	public function &getDb($db,$new = false) {
		if(isset($this->dbs[$db])) {
			$db = &$this->dbs[$db];
		}
		elseif ($new) {
			$this->db[$db] = [];
			$db = &$this->db[$db];
		}
		return $db;
	}
	public function getCollection($db,$col) {
		$database = $this->getDb($db);
		if(is_array($database) && isset($database[$col])) {
			$collection = $database[$col];
			#cog::emit(['a',$collection]);
			return $collection;
		} elseif (file_exists(dirname(__FILE__)."/data/{$db}.{$col}")) {
			$collection = \SleekDB\SleekDB::store("{$db}.{$col}",dirname(__FILE__)."/data");
			$this->db[$db][$col] = $collection;
			#cog::emit(['b',$collection]);
			return $collection;
		}
	}
	public function exec($db,$cmd) {
		cog::emit(__FUNCTION__);
		cog::emit(func_get_args());
	}
	public function listCollections($db) {
		$collections = scandir(dirname(__FILE__).'/data/');
		$res = [];
		foreach($collections as $i => $f) {
			if(!preg_match("/{$db}.[a-zA-Z0-9]+/",$f)) {
				unset($collections[$i]);
			} else {
				$split = explode(".",$f);
				$res[$split[1]] = ['name' => $split[1]];
			}
		}
		return $res;
	}
	public function createCollection($db,$collection) {
		$database = $this->getDb($db,true);
		if (is_array($database)) {
			$database[$collection] = \SleekDB\SleekDB::store("{$db}.{$collection}",dirname(__FILE__)."/data");
		}
	}
	public function dropCollection($db,$col) {
		$collection = $this->getCollection($db,$col);
		if (is_object($collection)) {
			$collection->deleteStore();
			unset($this->db[$db][$col]);
		}
	}
	public function listDatabases($db = null) {
		$scan = scandir(dirname(__FILE__).'/data');
		$dbs = [];
		foreach($scan as $i => $f) {
			if(!preg_match("/[a-zA-Z0-9]+.[a-zA-Z0-9]+/",$f)) {
				unset($scan[$i]);
			} else {
				$split = explode(".",$f);
				$dbName = $split[0];
				if(!isset($dbs[$dbName])) {
					$dbs[$dbName] = ['name' => $dbName];
				}
			}
		}
		return ['databases' => $dbs];
	}
	public function countCollection($db,$col) {
		$collection = $this->getCollection($db,$col);
		if(!is_object($collection)) {
			return 0;
		}
		$scan = scandir(dirname(__FILE__).'/data/{$db}.{$col}/data');
		foreach($scan as $i => $f) {
			if(!preg_match("/.json/",$f)) {
				unset($scan[$i]);
			}
		}
		return count($scan);
	}
	public function executeCommand($db,$cmd) {
		$keys = array_keys($cmd);
		$key = $keys[0];
		$val = $cmd[$key];

		switch ($key) {
			case 'count':
				return $this->countCollection($db,$val);
			case 'create':
				return $this->createCollection($db,$val);
				break;
			case 'drop':
				return $this->dropCollection($db,$val);
				break;
			case 'listCollections':
				return $this->listCollections($db);
				break;
			case 'listDatabases':
				return $this->listDatabases();
				break;
			default:
				cog::emit(__FUNCTION__);
				cog::emit(func_get_args());				
				break;
		}
	}
	public function query($db,$collection,$query,$opts = []) {
		cog::emit(__FUNCTION__);
		cog::emit(func_get_args());
	}
	public function executeQuery($db,$query = [],$opts = []) {
		$split = explode(".",$db);
		$db = $split[0];
		$col = $split[1];

		$collection = $this->getCollection($db,$col);

		#cog::emit([$db,$col,$collection]);

		if(is_object($collection)) {
			$q = $collection;
			foreach($query as $k => $v) {
				if(is_array($v)) {
					$keys = array_keys($v);
					$key = reset($keys);
					$val = $v[$key];
					switch($key) {
						case '$ne':
							$q = $q->where($k,'!=',$val);
							cog::emit($q);
							break;
						default:
							cog::emit([$k,$v]);
							break;
					}
				} else {
					$q = $q->search($k,$v);
				}
			}
			/*
			$q = $collection->search("{$db}.{$col}",$query);
		cog::emit(__FUNCTION__);
		cog::emit(func_get_args());
		cog::emit($query);
		cog::emit($q);
		*/
			foreach($opts as $opt) {
				foreach($opts as $op => $crit) {
					switch($op) {
						case 'sort':
							$keys = array_keys($crit);
							$key = reset($keys);
							$val = $crit[$key];
							$q->orderBy($val == '-1' ? 'desc' : 'asc',$key);
							break;
						case 'limit':
							$q->limit($crit);
							break;
						default:
							cog::emit($opts);
							break;
					}
				}
			}
			$res = $q->fetch();
		cog::emit(["{$db}.{$col}",$query,$res]);
			return $res;
		} else {
			return [];
		}
	}
	public function insertMultiple($db,$data) {
		$split = explode(".",$db);
		$db = $split[0];
		$col = $split[1];

		$collection = $this->getCollection($db,$col);
		$collection->insertMany($data);
	}
	public function updateMultiple($db,$data,$filter) {
		cog::emit(__FUNCTION__);
		cog::emit(func_get_args());
	}
	public function deleteMultiple($table,$data) {
		cog::emit(__FUNCTION__);
		cog::emit(func_get_args());
	}
}
?>
