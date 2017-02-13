<?php

if($argc == 0 || !strlen($url = $argv[1])){
	die("Usage: ". cli_get_process_title()." <url> < <postfile> > <out.pprof>");
}

Sandbox::init("http://lincus.anthrax",$working_dir,$url);
Sandbox::route($url);
Sandbox::dump("out");
Sandbox::dumpSql();