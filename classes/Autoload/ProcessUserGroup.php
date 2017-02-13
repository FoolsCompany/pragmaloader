<?php

namespace Autoload;

require_once(__DIR__."/../Singleton.php");

use Singleton;

class ProcessUserGroup {

	use Singleton;

	private function __construct(){}

	static public function __callStatic($name,array $arguments){
		if(0 != strcmp("Autoload\Autoloader",get_class($arguments[0]))){
			throw new AutoloadException("Unauthorised access to ".__CLASS__);
		}
		array_shift($arguments);
		return call_user_func_array(array(static::getInstance(),"_{$name}"), $arguments);
	}

	private function _process_title(){
		if(false==($process_name = cli_get_process_title())){
			$process_name = basename($_SERVER["SCRIPT_FILENAME"], '.php');
		}
		return $process_name;
	}
	
	private function _getUser(){
		$userinfo = posix_getpwuid(posix_geteuid());
		return $userinfo["name"];
	}

	private function _getGroup(){
		$groupinfo = posix_getgrgid(posix_getegid());
		return $groupinfo["name"];
	}
}
