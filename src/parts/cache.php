<?php
namespace nx\parts\model;

trait cache{
	protected static array $_cache_model=[];
	/**
	 * 共享，不要在create中缓存single，或者加限定名 如id
	 * @param string $ModelNameSpace
	 * @param        ...$args
	 * @return mixed
	 */
	protected function cacheModel(string $ModelNameSpace, ...$args): mixed{
		if(!array_key_exists($ModelNameSpace, self::$_cache_model)){
			self::$_cache_model[$ModelNameSpace]=new $ModelNameSpace(...$args);
		}
		return self::$_cache_model[$ModelNameSpace];
	}
}
