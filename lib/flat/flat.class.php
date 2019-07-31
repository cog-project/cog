<?php
class flat {
  public static $cache = [];
  public function get_path() {
    return dirname(__FILE__)."/data";
  }
  public function __construct() {
  }
  public function query($db,$collection,$query = [],$opts = []) {
    if(!$this->collection_exists($db,$collection)) {
      return [];
    }

    #emit("Querying, {$db}.{$collection}: ".print_r([$query,$opts],1));
    $raw = $this->get_collection_data($db,$collection);
if(!empty($raw)) {
#cog::emit("Queried:\n".print_r([$db,$collection,$query,$opts,$raw],1));
}
cog::emit($query);
    foreach($query as $cond) {
      $this->filter($raw,$cond);
    }
    if($collection == 'endpoints') {
    #cog::emit("Query: ".print_r($query,1)."\nResult: ".print_r($raw,1)."\n".print_r(debug_backtrace(),1));
    }
    return $raw;
  }
  public function should_filter($val,$criteria) {
    if(!is_array($criteria)) {
      if($val != $criteria) {
        return true;
      } else {
        return false;
      }
    }
    foreach($criteria as $k=>$v) {
      switch($k) {
        case '$ne':
          if($val == $v) {
	    return true;
          }
          break;
        default:
	  if($val != $v) {
	    return true;
          }
	  break;
      }
    }
    return false;
  }
  public function filter_for_key(&$data,$key,$val) {
    foreach($data as $i => $row) {
      if(!isset($row[$key])) {
        continue;
      }
      if($this->should_filter($row[$key],$val)) {
        unset($data[$i]);
      }
    }
  }
  public function filter(&$data,$query) {
    foreach($query as $k => $v) {
    cog::emit([$k,$v,reset($data)]);
      switch($k) {
        default:
          $this->filter_for_key($data,$k,$v);
          break;
      }
    }
  }
  public function get_db_path($db = '.') {
    $path = "{$this->get_path()}/{$db}";
    return $path;
  }
  public function get_collection_path($db,$collection) {
    $path = $this->get_db_path($db)."/{$collection}";
    return $path;
  }
  public function db_exists($db) {
    $path = $this->get_db_path($db);
    return (file_exists($path) && is_dir($path));
  }
  public function collection_exists($db,$collection) {
    $path = $this->get_collection_path($db,$collection);
    return ($this->db_exists($db) && file_exists($path) && is_dir($path));
  }
  public function create_db($db) {
    if(!file_exists(dirname(__FILE__)."/data")) {
      mkdir(dirname(__FILE__)."/data",0777);
    }
    if($this->db_exists($db)) return false;
    $path = $this->get_db_path($db);
    return mkdir($path,0777) && chown($path,"www-data");
  }
  public function create_collection($db,$collection) {
    $this->create_db($db);
    if(!$this->collection_exists($db,$collection)) {
      return mkdir("{$this->get_db_path($db)}/{$collection}",0777) && chown("{$this->get_db_path($db)}/{$collection}","www-data");
    }
    return false;
  }
  public function drop_collection($db,$collection) {
    if(!$this->collection_exists($db,$collection)) {
      return true;
    }
    passthru("rm -rf ".$this->get_collection_path($db,$collection));
  }
  public function list_databases() {
    $fullpath = $this->get_path();
    $res = scandir($fullpath);
    foreach($res as $i => $f) {
      if(!preg_match("/[a-zA-Z0-9_]+/",$f)) {
        unset($res[$i]);
      } else {
        $res[$i] = ['name' => $f];
      }
    }
    $out = ['databases' => array_values($res)];
    return $out;
  }
  public function list_collections($db) {
    $fullpath = $this->get_db_path($db);
    $res = scandir($fullpath);
    $out = [];
    foreach($res as $i => $f) {
      if(!preg_match("/[a-zA-Z0-9_]+/",$f)) {
        unset($res[$i]);
      } else {
        $res[$i] = ['name' => $f];
      }
    }
    return array_values($res);
  }
  
  public function count_collection($db,$collection) {
    return count($this->list_collection_records($db,$collection));
  }
  
  public function read_from_file($path) {
    if(!is_readable($path)) {
      throw new Exception("File '$path' is not readable.");
    }
    return file_get_contents($path);
  }
  
  public function insert($db,$collection,$data) {
    $this->create_collection($db,$collection);
    
    $hash = sha1(uniqid());
    $data['_id'] = $hash;
    $res = file_put_contents(
      $this->get_collection_path($db,$collection)."/{$hash}",
      json_encode($data)
    );
    if(!$res) {
      throw new Exception("File '".$this->get_collection_path($db,$collection)."/{$hash}' is not writable.");
    }
    chmod($this->get_collection_path($db,$collection)."/{$hash}",0777);
    self::$cache[$hash] = $data;
  }
  public function insert_multiple($db,$collection,$data) {
    foreach($data as $row) {
      $this->insert($db,$collection,$row);
    }
  }
  public function list_collection_records($db,$collection) {
    if(!$this->collection_exists($db,$collection)) {
      return [];
    }
    $fullpath = $this->get_collection_path($db,$collection);
    $res = scandir($fullpath);
    foreach($res as $i => $f) {
      if(!preg_match("/[a-zA-Z0-9_]+/",$f)) {
        unset($res[$i]);
      }
    }
    return array_values($res);
  }
  public function get_collection_data($db,$collection) {
    if(!$this->collection_exists($db,$collection)) {
      return [];
    }
    $data = [];
    $fullpath = $this->get_collection_path($db,$collection);
    $res = $this->list_collection_records($db,$collection);
    foreach($res as $f) {
      $file = "{$fullpath}/{$f}";
      $r = $this->read_from_file($file);
      $contents = json_decode($r,true);
      $data[$contents['_id']] = $contents;
      self::$cache[$f] = $contents;
    }
    return $data;
  }
  public function update($db,$collection,$row,$filter) {
    $data = $this->query($db,$collection,$filter);
    foreach($data as $i => $v) {
      $id = $v['_id'];
      $v = $row;
      $v['_id'] = $id;
      $data[$i] = $v;
      $writepath = $this->get_collection_path($db,$collection)."/{$id}";
      $success = file_put_contents(
        $writepath,
	json_encode($v)
      );
      chmod($this->get_collection_path($db,$collection)."/{$id}",0777);
      if(!$success) {
        throw new Exception('Failed to write to file: '.$writepath);
      }
      self::$cache[$id] = $v;
    }
  }
  public function update_multiple($db,$collection,$data,$filter) {
    foreach($data as $row) {
      $this->update($db,$collection,$row,$filter);
    }
  }
  public function delete_multiple($db,$collection,$data) {
    foreach($data as $k => $v) {
      $id = $v['_id'];
      if(!empty($id)) {
        $this->delete_one($db,$collection,$id);
      } else {
        $rows = $this->query($db,$collection,$data);
	foreach($rows as $row) {
	  $this->delete_one($db,$collection,$row['_id']);
	}
      }
    }
  }
  public function delete_one($db,$collection,$hash,$data = []) {
    if(file_exists($this->get_collection_path($db,$collection)."/{$hash}")) {
      passthru("rm ".$this->get_collection_path($db,$collection)."/{$hash}");
      unset(self::$cache[$hash]);
    } else {
      throw new Exception("Failed to find item corresponding to hash '{$hash}'");
    }
  }
}
?>