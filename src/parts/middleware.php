<?php
namespace nx\parts\model;

trait middleware{
	/**
	 * 执行指定类型的中间件，支持正序和倒序两次调用
	 *
	 * @param string $type    中间件类型（如 update/delete）
	 * @param mixed  ...$args 中间件参数
	 * @return bool
	 */
	protected function middleware(string $type, ...$args): mixed{
		$prefix = "{$type}_";
		$methods = array_filter(get_class_methods($this), fn($method) => str_starts_with($method, $prefix));
		usort($methods, fn($a, $b) => (int)str_replace($prefix, '', $a) <=> (int)str_replace($prefix, '', $b));
		$result = null;
		$_methods = [];
		foreach($methods as $method){
			\nx\app::$instance?->runtime('    ' . $this::class . '->' . $method . '()', 'm-w');
			$generator = $this->{$method}(...$args);
			if($generator instanceof \Generator){
				$result = $generator->current() ?? null;
				$_methods[] = $generator;
				if($result === false) break;
			} else if(false ===$generator){
				$result =false;
				break;
			}
		}
		foreach(array_reverse($_methods) as $generator){
			\nx\app::$instance?->runtime('    ' . $this::class . '->' . $method . '()->send()', 'm-w');
			$generator->send($result);
			$result = $generator->getReturn() ?? $result;
		}
		return $result;
	}
}