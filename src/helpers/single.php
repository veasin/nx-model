<?php
namespace nx\helpers\model;

use nx\helpers\db\sql;
use nx\parts\callApp;
use nx\parts\model\cache;
use nx\parts\model\middleware;

abstract class single{
	use callApp, middleware, cache;

	public int $id = 0;
	protected string $id_key;
	protected array $data = [];
	protected array $_data = [];//初始化数据
	/**
	 * @var multiple
	 */
	protected const MULTIPLE = null;
	public function __construct(array $data = []){
		!static::MULTIPLE && throw new \Error(static::class . ' requires MULTIPLE definition');
		$this->id_key ??= static::MULTIPLE::TABLE_PRIMARY;
		$this->data = $this->_data = $data;
		$this->id = (int)($this->data[$this->id_key] ?? 0);
		$this->id === 0 && $this->middleware('default');
	}
	/**
	 * @param string|null $tableName
	 * @param string|null $primary
	 * @param string|null $config
	 * @return sql
	 */
	protected function table(?string $tableName = null, ?string $primary = null, ?string $config = null): sql{
		return $this->db($config ?? static::MULTIPLE::TABLE_DB)->from($tableName ?? static::MULTIPLE::TABLE, $primary ?? static::MULTIPLE::TABLE_PRIMARY);
	}
	protected function update_middle($update = []): \Generator{
		if($ok = !empty($update)){
			static::MULTIPLE::FIELD_UPDATED && $update[static::MULTIPLE::FIELD_UPDATED] = time();
			$ok = $this->table()->where([$this->id_key => $this->id])->update($update)->execute()->ok();
		}
		if($r = yield $ok) $this->_data = $this->data;
		return $r;
	}
	protected function create_middle($run = true): \Generator{
		if($run){
			static::MULTIPLE::FIELD_CREATED && !array_key_exists(static::MULTIPLE::FIELD_CREATED, $this->data) && $this->data[static::MULTIPLE::FIELD_CREATED] = time();
			$id = $this->table()->insert($this->data)->execute()->lastInsertId();
			$run = $id > 0;
		}
		if($r = yield $run){
			$this->data = $this->_data = $this->table()->where([$this->id_key => $id])->select()->execute()->first();
			$this->id = $this->data[$this->id_key];
			$this->save();//触发二次保存逻辑
		}
		return $r;
	}
	/**
	 * 保存当前对象中的数据，如不存在即添加
	 *
	 * @return bool
	 */
	public function save(): bool{
		return $this->middleware(
			$this->id ? "update" : "create",
			$this->id ? array_diff_assoc($this->data, $this->_data) : true
		);
	}
	/**
	 * @return bool
	 * @deprecated 2025/07/25
	 * @see        destroy
	 * */
	public function delete(): bool{
		return $this->destroy();
	}
	protected function delete_middle($run): \Generator{
		if($run){
			$table = $this->table()->where([$this->id_key => $this->id]);
			static::MULTIPLE::TOMBSTONE && static::MULTIPLE::FIELD_DELETED
				? $table->update([static::MULTIPLE::FIELD_DELETED => time()])//逻辑删除
				: $table->delete();
			$run = $table->execute()->ok();
		}
		if($r = yield $run){
			$this->_data = $this->data = [];
			$this->id = 0;
		}
		return $r;
	}
	/**
	 * 删除当前对象本身的数据，如未记录直接忽略
	 * UPDATE "posts" SET "deleted_at"=[timestamp] WHERE "deleted_at" = 0 AND "id" = 1
	 *  ->where([$this->id_key => $this->id, static::MULTIPLE::FIELD_DELETED => 0 ])
	 *
	 * @return bool
	 */
	public function destroy(): bool{
		return $this->id >0 ?$this->middleware('delete', true) :true;
	}
	/**
	 * 更新自身数据
	 *
	 * @param array $data
	 * @param bool  $over 是否覆盖更新
	 * @return $this
	 */
	public function update(array $data, bool $over = false): self{
		foreach($data as $key => $value){
			if($over || array_key_exists($key, $this->_data)) $this->data[$key] = $value;
		}
		return $this;
	}
	public function output(array $options = []): mixed{
		return [...$this->data];
	}
}
