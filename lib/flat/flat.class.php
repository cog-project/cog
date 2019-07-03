<?php
class flat {
  public $path = 'lib/flat/data';
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
  public function get_db_path($db) {
    $path = "{$this->path}/{$db}";
    return $path;
  }
  public function get_collection_path($db,$collection) {
    $path = $this->get_db_path($db)."/{$collection}";
    return $path;
  }
  public function db_exists($db) {
    $path = $this->get_db_path($db);;
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
      return mkdir("{$this->path}/{$db}/{$collection}");
    }
    return false;
  }
}
?>