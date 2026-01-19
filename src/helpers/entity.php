<?php
declare(strict_types=1);
namespace nx\helpers\model;
class entity implements \ArrayAccess{
	protected(set) int|string|null $id = null;
	public function __construct(protected array $data = [],
		protected(set) ?collection $collection = null, //链式构建和回溯
	){
		$this->id = $this->collection::_entity_id($this->data) ?? null;
	}
	public function update(array $set = [], bool $overwrite = false): static{
		//$check =!empty($this->collection::ATTRIBUTES);//脏数据检测
		foreach($set as $k => $v){
			//if($check && !array_key_exists($k, $this->collection::ATTRIBUTES)) throw new InvalidArgumentException("Invalid field: {$k}");
			if($overwrite || array_key_exists($k, $this->data)) $this->data[$k] = $v;
		}
		return $this;
	}
	public function save(): bool{
		if($this->id){//update
			return $this->collection::_entity_update($this->data);
		}
		else{
			$this->id = $this->collection::_entity_create($this->data);
			return !empty($this->id);
		}
	}
	public function destroy(): bool{ return $this->collection::_entity_delete($this); }
	public function output(array $options): mixed{ return $this->collection::_entity_output($this->data); }
	public function __call(string $name, array $arguments): mixed{ return $this->collection::_entity_relations($this, $name, $arguments); }
	public function __toString(): string{ return $this->collection::_entity_string($this->data); }
	public function offsetExists(mixed $offset): bool{ return isset($this->data[$offset]); }
	public function offsetGet(mixed $offset): mixed{ return $this->data[$offset] ?? null; }
	public function offsetSet(mixed $offset, mixed $value): void{ $this->data[$offset] = $value; }
	public function offsetUnset(mixed $offset): void{ unset($this->data[$offset]); }
}