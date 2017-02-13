#!/usr/local/bin/php
<?php
$usec = microtime(1);
require_once(__DIR__."/classes/Autoload/Autoloader.php");
Autoload\Autoloader::register(true,false);
use Types\Object;

class Test extends Object {
	use Singleton;
	
	private function __construct(){}
	
	public function bar(){
		echo "bar\n";
	}
}
Object::foo();
Test::getInstance()->bar();
echo (microtime(1) - $usec)."\n";
