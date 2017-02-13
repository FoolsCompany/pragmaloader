<?php die;
namespace Types;

/* @process=test@ */

function stupid_code(){
	
}
stupid_code();//current problem is namespacing this one.

class Object {	
	public static function foo(){
		echo "foo\n";
	}
}
