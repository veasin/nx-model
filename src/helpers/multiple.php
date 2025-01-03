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
	const TABLE ='';
	const TABLE_DB ='default';
	const TABLE_PRIMARY ='id';
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
	protected function table(?string $tableName=null, ?string $primary=null, ?string $config=null):sql{
		return $this->db($config ?? static::TABLE_DB)->from($tableName ?? static::TABLE, $primary ?? static::TABLE_PRIMARY ?? 'id');
	}
	static public function sql():sql{
		return \nx\app::$instance?->db(static::TABLE_DB)->from(static::TABLE, static::TABLE_PRIMARY ?? 'id');
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
		if(array_key_exists(static::OPT_SORT, $options)) $table->sort($options[static::OPT_SORT], static::DEFAULT_SORT);
		$table->select(array_key_exists(static::OPT_SELECT, $options) ? $options[static::OPT_SELECT] : []);
		if(array_key_exists(static::CALLBACK_FIND, $options) && is_callable($options[static::CALLBACK_FIND])) call_user_func($options[static::CALLBACK_FIND], $table, $conditions, $options);
		return $table->execute()->first(static::SINGLE);
	}
	/**
	 * @param sql   $table
	 * @param array $conditions
	 * @param array $options
	 * @return array|null
	 */
	private function __list(sql $table, array $conditions, array $options): ?array{
		$desc = $options[static::OPT_DESC] ?? static::DEFAULT_SORT;
		if(is_int($desc)) $desc = ['ASC', 'DESC'][$desc] ?? static::DEFAULT_SORT;
		if(array_key_exists(static::OPT_SORT, $options)) $table->sort($options[static::OPT_SORT], $desc);
		$table->select(array_key_exists(static::OPT_SELECT, $options) ? $options[static::OPT_SELECT] : []);
		if(array_key_exists(static::CALLBACK_LIST, $options) && is_callable($options[static::CALLBACK_LIST])) call_user_func($options[static::CALLBACK_LIST], $table, $conditions, $options);
		if(array_key_exists(static::CALLBACK_FETCH, $options) && is_callable($options[static::CALLBACK_FETCH])){
			$list = call_user_func($options[static::CALLBACK_FETCH], $table->execute(), $options);
		}
		elseif(array_key_exists(static::CALLBACK_MAP, $options) && is_callable($options[static::CALLBACK_MAP])){
			$list = $table->execute()->fetchMap($options[static::CALLBACK_MAP]);
		}
		else $list = $table->execute()->fetchAll();
		return $list;
	}
	protected function __count(sql $table, array $conditions = [], array $options = []): int{
		$table->select($table::COUNT('*')->as('COUNT'));
		if(count($conditions)) $table->where($conditions);
		if(array_key_exists(static::CALLBACK_COUNT, $options) && is_callable($options[static::CALLBACK_COUNT])) call_user_func($options[static::CALLBACK_COUNT], $table, $conditions, $options);
		$ok = $table->execute()->first();
		return null !== $ok ? $ok['COUNT'] : 0;
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
		if(array_key_exists(static::OPT_PAGE, $options) && !array_key_exists(static::CALLBACK_COUNT, $options) && false === $options[static::OPT_PAGE]){
			if(count($conditions)) $table->where($conditions);
			$list = $this->__list($table, $conditions, $options);
			$count = count($list);
		}
		else{
			$count = $this->__count($table, $conditions, $options);
			if($count > 0){
				if(array_key_exists(static::OPT_PAGE, $options) && false !== $options[static::OPT_PAGE]) $table->page($options[static::OPT_PAGE] ?? 1, $options[static::OPT_MAX] ?? 10);
				$list = $this->__list($table, $conditions, $options);
			}
			else $list = [];
		}
		return [static::RESULT_COUNT => $count, static::RESULT_LIST => $list];
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