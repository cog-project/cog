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
cog::emit([$db,$collection,$query,$opts,$raw]);
}
    $this->filter($raw,$query);
    #emit("Result: ".print_r($raw,1));
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
  public function insert($db,$collection,$data) {
    $hash = sha1(uniqid());
    $data['_id'] = $hash;
    file_put_contents($this->get_collection_path($db,$collection)."/{$hash}",json_encode($data));
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
      $contents = json_decode(file_get_contents($file),true);
      $data[] = $contents;
      self::$cache[$f] = $contents;
    }
    return $data;
  }
  public function update($db,$collection,$row,$filter) {
    $data = $this->query($db,$collection,$filter);
    foreach($data as $i => &$v) {
      $id = $v['_id'];
      $v = $row;
      $v['_id'] = $id;
      file_put_contents($this->get_collection_path($db,$collection)."/{$id}",json_encode($v));
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
      if(file_exists($this->get_collection_path($db,$collection)."/{$id}")) {
        passthru("rm ".$this->get_collection_path($db,$collection)."/{$id}");
        unset($data[$k]);
	unset(self::$cache[$id]);
      } else {
        $matches = $this->query($db,$collection,$v);
        foreach($matches as $kk => $vv) {
          $id = $v['_id'];
          passthru("rm ".$this->get_collection_path($db,$collection)."/{$id}");
	  unset(self::$cache[$id]);
        }
      }
    }
  }
}
?>
