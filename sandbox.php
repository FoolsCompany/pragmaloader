<?php

require_once(__DIR__."/classes/Sandbox.php");

if($argc == 1 || !strlen($url = $argv[1])){
	die("Usage: ". cli_get_process_title()." <url> < <postfile> > <out.pprof>\n");
}

Sandbox::init("http://blez.anthrax","/code/msc/blez/public",$url);
Sandbox::route($url);
Sandbox::dump("sandbox");
