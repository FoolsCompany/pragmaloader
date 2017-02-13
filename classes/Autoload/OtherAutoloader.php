<?php

namespace Autoload;

require_once(__DIR__."/ClassFileOperations.php");

use Autoload\AutoloadException;
use Autoload\ClassFileOperations;

class OtherAutoloader {

	private $autoloader = [];
	
	public function __construct(\Autoload\Autoloader $autoloader,$file,$ns = false){
		ClassFileOperations::load($autoloader,$file);
		$structure = ClassFileOperations::getStructure($autoloader);
		if(0==count($structure)){
			throw new AutoloadException("Empty?");
		}
			
		if(!$ns){
			if(1<count($structure)){
				throw new AutoloadException("More than one namespace in ".__METHOD__);
			}
			foreach($structure as $ns => $classes){
				foreach($classes as $class){
					$name = $class->classname;
					$this->autoloader[$ns] = new $name(basename($dir));
				}
			}
		}else{
			$cn = array_keys($structure[$ns]);
			$c = array_shift($cn);
			$class = $ns."\\".$structure[$ns][$c]->name;
			require_once($file);
			try{
				$this->autoloader = [new $class(dirname($file))];
			}catch(Exception $e){
				echo get_class($e);
			}
		}
	}
	
	public function autoload($class) {
		foreach($this->autoloader as $autoloader){
			return $autoloader->autoload($class);
		}
	}
}
