<?php

namespace phpFastCache;

class FastCacheAutoloader {

	public function __construct($dir){}
	
	public function autoload($entity) {
        // Explode is faster than substr & strstr also more control
        $module = explode('\\',$entity,2);
        if ($module[0] !== 'phpFastCache') {
            return false;
        }

        $entity = str_replace('\\', '/', $module[1]);
        $path = __DIR__ . '/../phpFastCache/' . $entity . '.' . PHP_EXT;
        if (is_readable($path)) {
            require_once $path;
			return true;
        }		

		return false;
	}

}
