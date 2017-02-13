<?php

namespace Autoload;

require_once(__DIR__."/AutoloadException.php");
require_once(__DIR__."/ClassFileOperations.php");
require_once(__DIR__."/../Singleton.php");
require_once(__DIR__."/../Globals.php");

use Autoload\Autoloader;
use Autoload\AutoloadException;
use Autoload\ClassFileOperations;
use Singleton;
use Globals;

class NSObject {
	public $type;
	public $nsname;
	public $name;
	public $code;
	public $ls;
	public $le;
	public $file;
	public $extern;
}

class ArgumentObject {
	public $type;
	public $name;
	public $default;
	public function __construct($t,$n,$d){
		$this->type = $t;
		$this->name = $n;
		$this->default = $d;
	}
	public function toString() {
		return "{$this->type} {$this->name}".($this->default!=""?" = {$this->default}":"");
	}
}

class ClassObject extends NSObject {
	public function toString() {
		$code = strlen($this->extern)?"{$this->extern}\n":"";
		$code .= "class {$this->name}";		
		$code .= substr($this->code,strpos($this->code,"{"))."\n";
		return $code;
	}
}

class FunctionObject extends NSObject {
	public $arguments;
	public function toString() {
		$code = $this->extern;
		$code .= "function {$this->name}";
		$code .= "(".implode(",",array_map(function($arg){return $arg->toString();},$this->arguments)).")\n";
		$code .= substr($this->code,strpos($this->code,"{"))."\n";
		return $code;
	}
}

use Autoload\ArgumentObject;
use Autoload\ClassObject;
use Autoload\FunctionObject;

class ClassFileOperations {

	use Singleton;

	const space = __DIR__."/Space/";
	
	private $file;
	private $code;
	private $mtime;
	private $structure;
	private $pragmas;
	private $nocollect = false;
	
	private function __construct(){}
	
	static public function __callStatic($name,array $arguments){
		if(0 != strcmp("Autoload\Autoloader",get_class($arguments[0]))){
			throw new AutoloadException("Unauthorised access to ".__CLASS__."::_$name");
		}
		array_shift($arguments);
		return call_user_func_array(array(static::getInstance(),"_{$name}"), $arguments);
	}
	
	private function _load($file){
		$this->file = $file;
		if(!file_exists($file)){
			throw new AutoloadException("File not found: {$file}");
		}
		$this->mtime = filemtime($file);
		$this->code = file_get_contents($file);
		$this->structure = false;
		$this->pragmas = [];
	}

	private function _show(){
		echo $this->code;
	}

	private function _setNoCollect($bool) {
		$this->nocollect = !!$bool;
	}
	
	private function _getStructure() {
		if(!$this->structure){
			$this->_readStructure();
		}
		return $this->structure;
	}

	private $idx;
	private $tokens;
	private $lines;
	private $stuff;
	private $namespace;
	private $class;
	private $identifier;
	
	private function look_behind_namespace_name() {
		$o = 1;
		$nsname = "";
		do{
			if(!preg_match("#^[_0-9a-zA-Z]*$#",($tmp = $this->last_token($o)).$nsname) && "\\"!=$tmp)
				return $nsname;
			$nsname = $tmp.$nsname;
		}while(++$o<$this->idx);
	}

	private function last_token($o = 1) {
		if($this->idx == 0)
			return "";
		$token = $this->tokens[$this->idx-$o];
		return is_array($token)?$token[1]:$token;
	}
	
	private function next_token($peek = false) {
		if($peek){
			$peek = $this->idx;
		}
		do{
			if($this->idx+1>=count($this->tokens)){
				$token = null;
				break;
			}
		}while(is_array($token = $this->tokens[++$this->idx])?0==strlen(trim($token[1])):0==strlen(trim($token)));
		if($peek){
			$this->idx = $peek;
			return is_array($token)?$token[1]:$token;
		}
		return $token;
	}
	private function advance($ignore = false) {
		while(!is_array($token = $this->next_token())){
			$ignore || ($this->stuff .= $token);
			if(!$token){
				break;
			}
		}
		return $token;
	}
		
	private function scan() {
		$token = $this->advance();
		do{
			if(in_array($token[0],[null,T_OPEN_TAG,T_COMMENT,T_NAMESPACE,T_NS_SEPARATOR,T_CLASS,T_FUNCTION]))
				return $token;
		}while($token = $this->advance());
		return null;
	}
	
	private function _readStructure(){
		$this->lines = explode("\n",$this->code);
		$this->idx = -1;
		$this->tokens = token_get_all($this->code);
		$this->tokens = array_combine(range(0,count($this->tokens)-1),array_values($this->tokens));
		$this->namespace = "";
		$this->identifier = "";
		$this->stuff = "";
		$arguments = [];
		while($token = $this->next_token()){
			$add = false;
			do{
				if(is_array($token)){
					if(in_array($token[0],[T_NAMESPACE,T_NS_SEPARATOR,T_COMMENT,T_OPEN_TAG,T_FUNCTION,T_CLASS])){
						switch($token[0]){
							case T_NAMESPACE:
								if($this->next_token(true) == '{')
									throw new AutoloadException("Anonymous namespace not supported");
								$this->namespace = $this->advance()[1];
								break 2;
							case T_NS_SEPARATOR:
								$this->namespace .= "\\".$this->advance()[1];
								break 2;
							case T_COMMENT:
								if(preg_match_all("#/\*\s+@(\w+)\s*=\s*(\w+)@\s+\*/#",$token[1],$matches,PREG_SET_ORDER)){
									$matches = $matches[0];
									array_shift($matches);
									do{
										$this->pragmas[$matches[0]] = next($matches);
									}while(next($matches));
								}
								break 2;
							case T_OPEN_TAG:
								break 2;
							case T_FUNCTION:
							case T_CLASS:
								$this->identifier = $this->advance()[1];
								break 2;
							default:
								$add = true;
								break 2;
						}
					}else{
						$add = true;
					}
				}else{
					switch($token){
						case "(":
							$identifier = $this->look_behind_namespace_name();
							if(!$this->nocollect && strlen($identifier)){
								$this->stuff = substr($this->stuff,0,strrpos($this->stuff,str_replace("\\","",$identifier)));
								$this->stuff .= $identifier;
							}
							break;
					}
					$add = true;
				}
			}while(false);
			$add && !$this->nocollect && ($this->stuff .= is_array($token)?$token[1]:$token);
			if(0<strlen($this->identifier)){
				switch($token[0]){
					case T_FUNCTION:
						$class = "Autoload\FunctionObject";
						break;
					case T_CLASS:
						$class = "Autoload\ClassObject";
						break;
				}
				$c = new $class();
				$c->ln = $token[2]-1;
				$c->name = $this->identifier;
				$this->identifier = "";
				switch($token[0]){
					case T_FUNCTION:
						$c->type = "Function";
						$c->nsname = $this->namespace."\\".$c->name;
						$this->structure[$this->namespace][$c->name] = $c;
						$this->function =& $c;
						$arguments = [];
						$arglist = "";
						while($this->next_token(true) != "("){$this->idx++;}
						$this->idx++;
						while(($tmp = $this->next_token(true)) !== ")"){
							$this->idx++;
							if(is_array($tmp)){
								$arglist .= $tmp[1];
							}else{
								$arglist .= $tmp;
							}
						}
						$arguments = explode(",",$arglist);
						$c->arguments = array_map(function($a){
							$parts = explode("=",$a);
							$type_name = $parts[0];
							$default = 1<count($parts)?$parts[1]:"";
							$parts = explode(" ",$type_name);
							$type = 1<count($parts)?$parts[0]:null;
							$name = 1<count($parts)?$parts[1]:$parts[0];
							return new ArgumentObject($type,$name,$default);
						},$arguments);
						break;
					case T_CLASS:
						$c->nsname = $this->namespace."\\".$c->name;
						$this->structure[$this->namespace][$c->name] = $c;
						$this->class =& $c;
						$c->type = "Class";
						break;
				}
				while(0==$this->count("{",$this->lines[$c->ln])){$c->ln++;}
				$this->mine($c);
			}
		}
	}
	
	private function mine(&$reference) {
		$depth = 0;
		$l = $reference->ln;
		$reference->code = "";
		do{
			$line = $this->lines[$l];
			$depth += $this->count("{",$line);
			if(0<$depth && !$this->nocollect)$reference->code .= "\n".$line;
			$depth -= $this->count("}",$line);
			if(0>=$depth && !is_null($reference)){
				!$this->nocollect && ($reference->extern = str_replace("die;","",$this->stuff));//preg_replace("#\n+#","\n",preg_replace("#(\n;)#","\n",
				break;
			}
		}while(++$l<count($this->lines));
		while($token = $this->advance(true)){
			if($token[2]>$l){
				break;
			}
		}
	}

	private function _output($namespace,$classname) {
		$code = "";
		//functions first
		$code .= implode("\n",array_map(function($c){
			return $c->type=="Class"?"":$c->toString();
		},$this->structure[$namespace]));		
		//class
		$code .= $this->structure[$namespace][$classname]->toString();
		//
		return $code;
	}
	
	private function count($c,$l){
		$i = -1;
		$n = 0;
		while(++$i<strlen($l)){if($l[$i]==$c)$n++;};
		return $n;
	}
	
	private function _transpile($namespace,$classname,$prefix){
		$class = $this->structure[$namespace][$classname];
		$code = "<?php\n".(strlen($namespace)?"namespace $namespace;\n":"").$this->_output($namespace,$classname);
		$pragmas = Globals::getInstance()->__pragmas__;
		is_null($pragmas) && ($pragmas = []);
		$pragmas[$namespace][$class->name] = $this->pragmas;
		Globals::getInstance()->__pragmas__ = $pragmas;
		$hash = sha1(openssl_random_pseudo_bytes(129));
		file_put_contents($fname = self::space.$prefix.$hash,$code);
		return $fname;
	}	
	
	private function _misenpile($prefix){
		$hash = sha1(openssl_random_pseudo_bytes(129));
		file_put_contents($fname = self::space.$prefix.$hash,$this->code);
		return $fname;		
	}
}
