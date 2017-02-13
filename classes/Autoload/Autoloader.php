<?php

namespace Autoload;

require_once(__DIR__."/AutoloadException.php");
require_once(__DIR__."/OtherAutoloader.php");
require_once(__DIR__."/ClassFileOperations.php");
require_once(__DIR__."/ProcessUserGroup.php");
require_once(__DIR__."/../Globals.php");

use Autoload\AutoloadException;
use Autoload\OtherAutoloader;
use Autoload\ClassFileOperations;
use Autoload\ProcessUserGroup;
use Globals;

class Autoloader {
    const dir = __DIR__."/../";
	const space = __DIR__."/Space/";
	const chit = __DIR__."/../../chit.json";
	const classmap = __DIR__."/../../classmap.json";
	const timestamp = __DIR__."/../../timestamp.autoload";
	
	private function others(){ 
		return [
//			"OAuth2" => new OtherAutoloader($this,__DIR__."/../OAuth2/Autoloader.php","OAuth2"),
//			"phpFastCache" => new OtherAutoloader($this,__DIR__."/FastCacheAutoloader.php","phpFastCache"),
		];
	}
	
	private $permissions;
	private $prefix;
	private $fast;
	static $registered = false;
	
	/*
	 * Loads the security chit.
	 */
    private function __construct($misenpile = false)
    {
		$this->fast = !!$misenpile;
		$this->permissions = file_exists(self::chit)?json_decode(file_get_contents(self::chit))
				:[
					"policy" => [],
					"spec"	 => [],
				];
		if(file_exists(self::classmap)){
			$classmap = (array)Globals::object_to_array(json_decode(file_get_contents(self::classmap)));
			Globals::getInstance()->__autoload__ = $classmap["autoload"];
			Globals::getInstance()->__pragmas__ = $classmap["pragmas"];
		}
		$this->prefix = "a";
		if(file_exists(self::timestamp)){
			$contents = file_get_contents(self::timestamp);
			$parts = explode(",",$contents);
			$timestamp = (int)$parts[0];
			$this->prefix = $parts[1];
			if(time()-(int)$timestamp > 60*60){
				$str = "abcdefghijklmnopqrstuvwxyz";
				$rot = "bcdefghijklmnopqrstuvwxyza";
				$this->prefix = strtr($this->prefix,$str,$rot);
				$this->randomize();
			}
		}
    }

	/**
	 * Looks in the chit for a given permission
	 */
	private function findGivenPermission($namespace,$class) {
		$namespace_star = $namespace."\*";
		if(isset($this->permissions->spec->$namespace_star)){
			$value = $this->permissions->spec->$namespace_star;
			if(!is_array($value))
				return [$value];
			return $value;
		}
		$fullname = $namespace.(strlen($namespace)?"\\":"").$class;
		if(isset($this->permissions->spec->$fullname)){
			$value = $this->permissions->spec->$fullname;
			if(!is_array($value))
				return [$value];
			return $value;
		}
		return null;
	}

	/**
	 * Looks in the chit policy for a given setting
	 */
	private function findGivenSetting($setting,$default = null) {
		return isset($this->permissions->policy->$setting)?$this->permissions->policy->$setting:$default;
	}

	/*
	 * Saves the current class mappings
	 */
	public function save(){
		file_put_contents(self::classmap,json_encode([
			"autoload" => Globals::getInstance()->__autoload__,
			"pragmas" => Globals::getInstance()->__pragmas__,
		]));
		if(!file_exists(self::timestamp)){
			file_put_contents(self::timestamp,time().",{$this->prefix}");
		}
	}

	public function randomize(){
		$classmap = Globals::getInstance()->__autoload__;
		$map = [];
		foreach($classmap as $namespace => $classes){
			foreach($classes as $class => $filename){
				rename($filename,$file = $this->prefix.sha1(openssl_random_pseudo_bytes(129)));
				$map[$namespace][$class] = $file;
			}
		}
		unlink(self::timestamp);
		Globals::getInstance()->__autoload__ = $map;
	}
	
	/**
     * Registers Autoload\Autoloader as an SPL autoloader.
     */
    public static function register($reset = false,$fast = false)
    {
		if(self::$registered)
			return;
		if($reset){
			file_exists(self::timestamp) && unlink(self::timestamp);
			file_exists(self::classmap) && unlink(self::classmap);
			foreach(glob(self::space."*") as $fname){
				unlink($fname);
			}	
		}
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array($self = new self($fast), 'autoload'));
		register_shutdown_function (function()use($self){
			$self->save();
		});
		self::$registered = true;
    }
	
    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     *
     * @return boolean Returns true if the class has been loaded
     */
    public function autoload($classname)
    {
		if(class_exists($classname))
			return true;
		/*
		 * Hand off to other autoloaders
		 */
		foreach($this->others() as $ns => $other){
			if (0 === strpos($classname, $ns)) {
				if($other->autoload($classname))
					return true;
			}
		}

		$namespace = substr($classname,0,$tmp = strrpos($classname,'\\'));
		$class = substr($classname,$tmp+1);
		if (!file_exists($sourcefile = self::dir.strtr($namespace,"\\","/")."/".$class.'.php')) {
			throw new AutoloadException("Could not find source {$sourcefile}");
		}
		$objectfile = Globals::getInstance()->lookup($namespace,$class);
		if(false===$objectfile || filemtime($objectfile) < filemtime($sourcefile)){
			ClassFileOperations::load($this,$sourcefile);
			ClassFileOperations::setNoCollect($this,$this->fast);
			$structure = ClassFileOperations::getStructure($this);
			if(!array_key_exists($namespace,$structure) || !array_key_exists($class,$structure[$namespace])){
				throw new AutoloadException("{$sourcefile} does not contain $namespace".'\\'."$class");
			}
		}
		$pragmas = Globals::getInstance()->__pragmas__[$namespace][$class];
		is_null($pragmas) && ($pragmas = []);
		foreach($pragmas as $pragma => $value){
			switch($pragma){
				/*
				 * @process = php@
				 */
				case "process":
					if(($process_title = ProcessUserGroup::process_title($this))!=$value)
						throw new AutoloadException("Pragma forbids access to process {$process_title}");
					break;
				/*
				 * @posixuid = http@
				 */
				case "posixuid":
					if(($user = ProcessUserGroup::getUser($this))!=$value)
						throw new AutoloadException("Pragma forbids access to user {$user}");
					break;
				/*
				 * @posixgid = http@
				 */
				case "posixgid":
					if(($group = ProcessUserGroup::getGroup($this))!=$value)
						throw new AutoloadException("Pragma forbids access to group {$user}");
					break;
				/*
				 * @nocollect = bool@
				 */
				case "nocollect":
					ClassFileOperations::setNoCollect($this,$value);
					break;
			}
		}
		/*
		 * In the chit, policy is dictated by the default
		 */
		$allow = "allow" == $this->findGivenSetting("default","deny");
		$deny = "deny" == $this->findGivenSetting("default","deny");
		/*
		 * Check given permissions, from the chit
		 */
		$value = $this->findGivenPermission($namespace, $class);
		if(in_array("*",$value) || in_array(ProcessUserGroup::getGroup($this),$value)){
			if(false===$objectfile){
				$objectfile = $this->allow($sourcefile,$namespace,$class);				
			}
			require_once($objectfile);
			return true;
		}
		/*
		 * Follow policy
		 */
		if($allow){
			if(false===$objectfile){
				$objectfile = $this->allow($sourcefile,$namespace,$class);				
			}
			require_once($objectfile);
			return true;
		}
		if($deny){
			throw new AutoloadException("Denied access to {$class}");
		}
		return false;
    }

	private function allow($file,$namespace,$class) {
		try{
			if($this->fast){
				$objectfile = ClassFileOperations::misenpile($this,$this->prefix);
			}else{
				$objectfile = ClassFileOperations::transpile($this,$namespace,$class,$this->prefix);
			}
		}catch(\Exception $e){
			throw new AutoloadException("During compilation ({$file})",0,$e);
		}
		Globals::getInstance()->store($namespace,$class,$objectfile);
		return $objectfile;
	}
}
