<?php

class CountingWrapper {
	
	private $object;
	private $counts;
	private $calls;
	
	public function __construct($object) {
		$this->object = $object;
		$this->counts = [];
		$this->calls = [];
	}
	
	public function __call($name,$arguments) {
		if(!method_exists($this->object,$name)){
			throw new \Exception("$name method called on ".get_class($this->object));
		}
		$this->counts[$name]++;
		$this->calls[$name][] = $arguments;
		return call_user_func_array([$this->object,$name],$arguments);
	}	
	
	public function dump(){
		var_dump($this->counts);
		var_dump($this->calls);
	}
}
