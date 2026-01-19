<?php
declare(strict_types=1);
namespace nx\helpers\model\collection;

use nx\helpers\model\collection;
use nx\helpers\model\entity;

trait ent{
	//需要解决基于RELATIONS的关系方法的ide提示
	const ENTITY = null;//单体类名 entity::class，如需针对单体进行写删，就需要配置此字段。
	const RELATIONS = [];//附属关系，如 用户的订单 即 orders=>userOrders::class orders=>[userOrders::class, []] ，需要包含传递参数和额外的构建参数配置，只在单体中使用
	public function id($id): ?entity{ //设置过滤pk为$id，并返回关系中对应的单体。如存在 scope_entity 自动调用
		$data = $this->source_find([...$this->scope, ['id' => $id]]);
		if(null === $data) return null;
		return new (static::ENTITY ?? entity::class)($data, $this);
	}
	public function create(array $data = []): entity{ //通过array创建单体，并传递此集合
		return new (static::ENTITY ?? entity::class)($data, $this);
	}
	static public function _entity_relations(entity $entity, string $name, array $args = []): entity|collection|null{
		if(isset(static::RELATIONS[$name])){
			$r = static::RELATIONS[$name];
			if(is_string($r)) $r = [$r, []];
			return new ($r[0])($entity, [...$r[1], ...$args]);
		}
		throw new \BadMethodCallException("Undefined relation method: {$name}");
	}
}