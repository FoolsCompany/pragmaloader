<?php

class Sandbox {
	
	static $uri_base;
	
	public static function init($domain,$working_dir,$url){
		global $_GET,$_POST,$_REQUEST,$_SERVER,$argv;
		//Thanks to http://stackoverflow.com/questions/9356152/non-blocking-on-stdin-in-php-cli
		function non_blocking_read($fd, &$data) {
			$read = array($fd);
			$write = array();
			$except = array();
			$result = stream_select($read, $write, $except, 0);
			if($result === false) throw new Exception('stream_select failed');
			if($result === 0) return false;
			$data .= stream_get_line($fd, 1);
			return true;
		}
		$data = "";
		while(non_blocking_read(STDIN,$_POST)){}

		//php://input
		if(in_array("--decode",$argv) && ($a = json_decode($_POST))){
			foreach($a as $k => $v){
				$_GET[$k] = $v;
				$_POST[$k] = $v;
				$_REQUEST[$k] = $v;
			}
		}
		//server vars
		if(file_exists($f = __DIR__."/sandbox.json")){
			$conf = json_decode(file_get_contents($f));
			foreach($conf as $k => $v){
				$_SERVER[$k] = $v;
			}
		}

		require_once(__DIR__."/Autoload/Autoloader.php");
		if(!Autoload\Autoloader::$registered)
			Autoload\Autoloader::register(true,false);

		self::$uri_base = "https://{$domain}/";
		chdir($working_dir);
		
		memprof_enable();
		ob_start();
	}
	
	public static function route($relative_url){
		$_SERVER["REQUEST_URI"] = self::$uri_base."{$relative_url}";
		include_once("{$relative_url}");
	}
	
	public static function dump($suffix = "-",$echo = true,$pprof = true){
		if($echo){
			echo ob_get_contents();
		}
		ob_end_clean();
		
		if($suffix=="-"){
			$fh = fopen("php://output", "a");
		}else{
			$fh = fopen("/tmp/sandbox.{$suffix}-".time(), "w");
		}
		
		if($pprof){
			memprof_dump_pprof($fh);
		}else{
			memprof_dump_callgrind($fh);
		}
	}
	
	public static function dumpSql() {
		global $db_conn;
		$db_conn->dump();
	}
}
