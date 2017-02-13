<?php

trait Singleton {

    static $__instance = null;

    public static function getInstance($parameters = null){
        if(is_object(self::$__instance))return self::$__instance;
        if(!is_null($parameters)){
			if(1==count($parameters))
				return self::$__instance = new static($parameters);
			else if(2==count($parameters))
				return self::$__instance = new static($parameters[0],$parameters[1]);
			else
				throw new \Exception(__CLASS__." faced with too many parameters.");
        }else{
            return self::$__instance = new static();
        }
    }
}
