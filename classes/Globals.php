<?php

require_once(__DIR__."/Singleton.php");

class Globals {
	use Singleton;
	private $globals;
	private function __construct() {
		$this->globals = [];
		$this->globals["__autoload__"] = [];
	}
	public function __get($name){
		return isset($this->globals[$name])?$this->globals[$name]:false;
	}
	public function __set($name,$value){
		return $this->globals[$name] = $value;
	}
	public function store($namespace,$class,$file){
		$this->globals["__autoload__"][$namespace][$class] = $file;
	}
	public function lookup($namespace,$class){
		if(array_key_exists($namespace,$this->globals["__autoload__"])
		&& array_key_exists($class,$this->globals["__autoload__"][$namespace])){
			return $this->globals["__autoload__"][$namespace][$class];
		}
		return false;
	}
	public function getPragmasNsClass($ns,$class) {
		if(!isset($this->globals["__pragmas__"]) ||
		   !isset($this->globals["__pragmas__"][$ns]) ||
		   !isset($this->globals["__pragmas__"][$ns][$class]))
				return [];
		return $this->globals["__pragmas__"][$ns][$class];
	}
	public static function object_to_array($feed) {
		if(is_object($feed)){
			$feed = (array) $feed;
		}
		if(is_array($feed)) {
			$new = array();
			foreach($feed as $key => $val) {
				$new[$key] = self::object_to_array($val);
			}
		} else {
			$new = $feed;
		}

		return $new;       
	}	
}
