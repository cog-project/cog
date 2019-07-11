<?php
class flat {
  public function get_path() {
    return dirname(__FILE__)."/data";
  }
  public function __construct() {
  }
  public function query($db,$collection,$query,$opts) {
	if(!$this->collection_exists($db,$collection)) {
	  return [];
	}
	cog::emit(func_get_args());
	$stack = debug_print_backtrace();
	cog::emit($stack);
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
    if($this->db_exists($db)) return false;
    $path = $this->get_db_path($db);
    return mkdir($path);
  }
  public function create_collection($db,$collection) {
    $this->create_db($db);
    if(!$this->collection_exists($db,$collection)) {
      return mkdir("{$this->get_db_path($db)}/{$collection}");
    }
    return false;
  }
  public function drop_collection($db,$collection) {
    if(!$this->collection_exists($db,$collection)) {
      return true;
    }
    passthru("rm -rf ".dirname(__FILE__)."/{$this->get_db_path($db)}/{$collection}");
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
    $fullpath = $this->get_collection_path($db,$collection);
    $res = scandir($fullpath);
    foreach($res as $i => $f) {
      if(!preg_match("/[a-zA-Z0-9_]+/",$f)) {
        unset($res[$i]);
      }
    }
    return count($res);
  }
  public function insert($db,$collection,$data) {
    $data['_id'] = sha1(uniqid());
    $
  }
}
?>
