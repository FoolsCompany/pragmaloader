<?php

/* @process=test@ */
die;

namespace Types;

function stupid_code(){
	
}
stupid_code();//current problem is namespacing this one.

class Object {	
	public static function foo(){
		echo "foo\n";
	}
}
