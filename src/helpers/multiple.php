<?php

namespace nx\helpers\model;

use nx\helpers\db\sql;
use nx\parts\callApp;
use nx\parts\model\cache;

/**
 * 群组数据
 */
abstract class multiple{
	use callApp, cache;

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
	protected const SINGLE = null;
	/**
	 * @param string|null $tableName
	 * @param string|null $primary
	 * @param string|null $config
	 * @return sql
	 */
	protected function table(?string $tableName = null, ?string $primary = null, ?string $config = null): sql{
		return $this->db($config ?? static::TABLE_DB)->from($tableName ?? static::TABLE, $primary ?? static::TABLE_PRIMARY);
	}
	static public function sql(): sql{
		return \nx\app::$instance?->db(static::TABLE_DB)->from(static::TABLE, static::TABLE_PRIMARY);
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
		return $table->execute()->first(static::SINGLE) ?: null;
	}
	private function __count(sql $table, array $conditions, array $options): int{
		$table->select($table::COUNT('*')->as('COUNT'))->where($conditions);
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
		$table = $this->table();
		if(static::TOMBSTONE) $conditions[static::FIELD_DELETED] = 0;
		$count = match (true) {
			isset($options[static::OPT_PAGE]) && $options[static::OPT_PAGE] === false => count($this->__fetch($table, $conditions, $options)),
			default => $this->__count(clone $table, $conditions, $options)
		};
		return [
			static::RESULT_COUNT => $count,
			static::RESULT_LIST => $count > 0 ? $this->__fetch($table, $conditions, $options) : [],
		];
	}
	/**
	 * 返回数据列表
	 *
	 * @param array $conditions 查询条件
	 * @param array{
	 *        desc:string|int,
	 *        sort:string|array{string:string|int},
	 *        page:int,
	 *        max:int,
	 *        select:array,
	 *        output:array,
	 *        COUNT:callable,
	 *        LIST:callable,
	 *        FETCH:callable,
	 *        MAP:callable
	 *    }         $options    支持 sort 排序参数 page 翻页 [page, max]
	 * @return array{count:int, list:array}
	 */
	public function list(array $conditions = [], array $options = []): array{
		return $this->_list($conditions, $options);
	}
}