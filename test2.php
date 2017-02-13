#!/usr/local/bin/php
<?php
$usec = microtime(1);
require_once(__DIR__."/classes/Autoload/Autoloader.php");
//use Types\Object;
require_once(__DIR__."/classes/Autoload/ClassFileOperations.php");
require_once(__DIR__."/classes/Singleton.php");
class Test {//extends Object {
	use Singleton;
	
	private function __construct(){}
	
	public function bar(){
		echo "bar\n";
	}
}
//Object::foo();
echo "foo\n";
Test::getInstance()->bar();
Autoload\Autoloader::register($argc);
echo (microtime(1) - $usec)."\n";
