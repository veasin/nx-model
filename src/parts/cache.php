<?php
namespace nx\parts\model;

trait cache{
	protected static array $_cache_model=[];
	/**
	 * 共享，不要在create中缓存single，或者加限定名 如id
	 * @param string $ModelNameSpace 缓存名字同命名空间名，不要缓存single类的
	 * @param        ...$args
	 * @return mixed
	 */
	protected function cacheModel(string $ModelNameSpace, ...$args): mixed{
		if(!array_key_exists($ModelNameSpace, self::$_cache_model)){
			self::$_cache_model[$ModelNameSpace]=new $ModelNameSpace(...$args);
		}
		return self::$_cache_model[$ModelNameSpace];
	}
	/**
	 * 缓存实例用，会自动在缓存名后加入实例名，如 user~1 user~2，需自己指定实例名称
	 * @param string $Namespace 模型名
	 * @param string $instanceName 实例名，如 id
	 * @param mixed ...$args 构造参数
	 * @return mixed
	 */
	protected function cacheInstance(string $Namespace, string $instanceName, ...$args):mixed{
		$name ="$Namespace~$instanceName";
		if(!array_key_exists($name, self::$_cache_model)){
			self::$_cache_model[$name] =new $Namespace(...$args);
		}
		return self::$_cache_model[$name];
	}
	/**
	 * 在app上回调缓存对应的数据，跨实例
	 * @param string|array $Namespace
	 * @param callable     $callback
	 * @param              ...$args
	 * @return mixed
	 */
	protected function cacheApp(string|array $Namespace, callable $callback, ...$args):mixed{
		$app =\nx\app::$instance;
		if(!isset($app[$Namespace])) $app[$Namespace]=call_user_func_array($callback,$args);
		return $app[$Namespace];
	}
}
