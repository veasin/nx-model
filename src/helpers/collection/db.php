<?php
declare(strict_types=1);
namespace nx\helpers\model\collection;

use nx\helpers\db\sql;
use nx\helpers\model\entity;
use nx\helpers\db\sql\table;

trait db{
	const TABLE = '';
	const TABLE_DB = 'default';
	const TABLE_PRIMARY = 'id';
	const TOMBSTONE = false; //逻辑删除
	const FIELD_CREATED = 'created_at';
	const FIELD_UPDATED = 'updated_at';
	const FIELD_DELETED = 'deleted_at';
	const OPT_SORT = 'sort';
	const OPT_SELECT = 'select';
	const OPT_DESC = 'desc';
	const OPT_PAGE = 'page';
	const OPT_MAX = 'max';
	const OPT_OUTPUT = 'output';
	const CALLBACK_LIST = 'LIST';
	const CALLBACK_FIND = 'FIND';
	const CALLBACK_FETCH = 'FETCH';
	const CALLBACK_MAP = 'MAP';
	const CALLBACK_COUNT = 'COUNT';
	const RESULT_COUNT = 'count';
	const RESULT_LIST = 'list';
	const DEFAULT_SORT = 'DESC';

	protected function table(?string $tableName = null, ?string $primary = null, ?string $config = null): table{
		return \nx\app::$instance?->db($config ?? static::TABLE_DB)->table($tableName ?? static::TABLE, $primary ?? static::TABLE_PRIMARY);
	}
	/**
	 * 私有方法 返回单条数据
	 *
	 * @param array $conditions 查询条件
	 * @param array{
	 *     sort:string|array{string:string|int},
	 *     select:array,
	 *     FIND:callable|null
	 * }            $options
	 * @return array|null
	 */
	protected function _find(array $conditions = [], array $options = []): ?array{
		if(static::TOMBSTONE) $conditions[static::FIELD_DELETED] = 0;
		$table = $this->table()->select()->where($conditions);
		$this->__select($table, $options);
		$this->__callback($options, static::CALLBACK_FIND, $table, $conditions, $options);
		return $table->execute()->first() ?: null;//static::ENTITY
	}
	private function __count(sql $table, array $conditions, array $options): int{
		$table->select(sql::COUNT('*')->as('COUNT'))->where($conditions);
		$this->__callback($options, static::CALLBACK_COUNT, $table, $conditions, $options);
		return $table->execute()->first()['COUNT'] ?? 0;
	}
	private function __fetch(sql $table, array $conditions, array $options): ?array{
		$table->where($conditions);
		$this->__select($table, $options, $this->__sort($options));
		$this->__callback($options, static::CALLBACK_LIST, $table, $conditions, $options);
		isset($options[static::OPT_PAGE]) && $options[static::OPT_PAGE] && $table->page($options[static::OPT_PAGE] ?? 1, $options[static::OPT_MAX] ?? 10);
		return match (true) {
			isset($options[static::CALLBACK_FETCH]) => $options[static::CALLBACK_FETCH]($table->execute(), $options),
			isset($options[static::CALLBACK_MAP]) => $table->execute()->fetchMap($options[static::CALLBACK_MAP]),
			default => $table->execute()->fetchAll()
		};
	}
	private function __sort(array $options): string{
		return match (true) {
			isset($options[static::OPT_DESC]) && is_int($options[static::OPT_DESC]) => ['ASC', 'DESC'][$options[static::OPT_DESC]] ?? static::DEFAULT_SORT,
			default => $options[static::OPT_DESC] ?? static::DEFAULT_SORT
		};
	}
	private function __select(sql $table, array $options, ?string $desc = null): void{
		isset($options[static::OPT_SORT]) && $table->sort($options[static::OPT_SORT], $desc ?? $this->__sort($options));
		$table->select($options[static::OPT_SELECT] ?? []);
	}
	private function __callback(array $options, string $type, ...$args): void{
		($options[$type] ?? null)?->call($this, ...$args);
	}
	/**
	 * 私有方法 返回多条数据
	 *
	 * @param array $conditions
	 * @param array{
	 *         desc:string|int,
	 *         sort:string|array{string:string|int},
	 *         page:int,
	 *         max:int,
	 *         select:array,
	 *         output:array,
	 *         COUNT:callable,
	 *         LIST:callable,
	 *         FETCH:callable,
	 *         MAP:callable
	 *     }        $options
	 * @return array{count:int, list:array}
	 */
	protected function _list(array $conditions = [], array $options = []): array{
		$table = $this->table()->select();
		if(static::TOMBSTONE) $conditions[static::FIELD_DELETED] = 0;
		$count = match (true) {
			isset($options[static::OPT_PAGE]) && $options[static::OPT_PAGE] === false => count($this->__fetch($table, $conditions, $options)),
			default => $this->__count($table, $conditions, $options)
		};
		return [
			static::RESULT_COUNT => $count,
			static::RESULT_LIST => $count > 0 ? $this->__fetch($table, $conditions, $options) : [],
		];
	}
	protected function source_query(array $conditions = [], array $options=[]):array{
		return $this->_list($conditions, $options);
	}
	protected function source_find(array $conditions = [], array $options = []): ?array{
		return $this->_find($conditions, $options);
	}
	protected function collection_string():string{
		//基于scope构建sql
		return "";
	}
	public function _entity_id(array $data):int|string|null{
		return $data[static::TABLE_PRIMARY] ?? null;
	}
	public function _entity_create(entity $entity, $data):int|string{
		static::FIELD_CREATED && !array_key_exists(static::FIELD_CREATED, $data) && $data[static::FIELD_CREATED] = time();
		return $this->table()->insert($data)->execute()->lastInsertId();
	}
	public function _entity_update(entity $entity, $update=[]):bool{
		if(empty($update)) return false;
		static::FIELD_UPDATED && $update[static::FIELD_UPDATED] = time();
		return $this->table()->where([static::TABLE_PRIMARY => $entity->id])->update($update)->execute()->ok();
	}
	public function _entity_delete(entity $entity):bool{
		$table = $this->table()->where([static::TABLE_PRIMARY => $entity->id]);
		static::TOMBSTONE && static::FIELD_DELETED
			? $table->update([static::FIELD_DELETED => time()])//逻辑删除
			: $table->delete();
		return $table->execute()->ok();
	}
	public function _entity_string(array $data):string{
		return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	}


}