<?php

if($argc < 2 || !strlen($url = $argv[1])){
	die("Usage: ". cli_get_process_title()." <url> [<vendor_dir>] < <postfile> > <out.pprof>\n");
}

if($argc >= 3){
	require_once($argv[2]."/autoload.php");
}
require_once(__DIR__."/classes/Sandbox.php");

Sandbox::init("http://blez.anthrax","/code/msc/blez/public",$url);
Sandbox::route($url);
Sandbox::dump("-",false,false);
