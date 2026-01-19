<?php
declare(strict_types=1);
namespace nx\helpers\model;
use nx\helpers\model\collection\db;
use nx\helpers\model\collection\ent;

abstract class collection{
	use ent, db;
	const ATTRIBUTES = [];
	public function __construct(protected(set) array $scope = [],
		protected(set) ?entity $parent = null,//链式构建和回溯
	){}
	protected function withScope(array $scope): static{
		$this->scope = $scope;
		return $this;
	}
	public function output(array $options): mixed{ return $this->source_query($options); }
	public function __toString(): string{ return $this->collection_string(); }
	protected function source_query(array $conditions = [], array $options=[]): mixed{ return $this->scope; }//数据源查询方法 待覆盖
	protected function source_find(array $conditions = [], array $options=[]): mixed{ return null; }//数据源查找方法 待覆盖
	protected function collection_string(): string{ return json_encode($this->scope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
	static public function _entity_relations(entity $entity, string $name, array $args = []): entity|collection|null{ return null; }
	static public function _entity_id(array $data): int|string|null{ return 0; }
	static public function _entity_create(array $data): int|string|null{ return 0; }
	static public function _entity_update(array $data): bool{ return false; }
	static public function _entity_delete(entity $entity): bool{ return false; }
	static public function _entity_string(array $data): string{ return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
	static public function _entity_output(array $data): mixed{ return $data; }
}
