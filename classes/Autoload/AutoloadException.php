<?php

namespace Autoload;

class AutoloadException extends \Exception {
	public function __construct($message = "", $code = 0 , \Exception $previous = null) {
		print_r("{$message} {$code}");
		!is_null($previous) && var_dump($previous);
		foreach(debug_backtrace() as $trace){
			if(isset($trace["file"]))echo "{$trace['file']}";
			if(isset($trace["line"]))echo "{$trace['line']}";
			if(function_exists("cli_get_process_title")){
				echo "\n";
			}else{
				echo "<br/>";
			}
		}
		die;
	}
}
