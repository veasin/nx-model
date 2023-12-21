<?php
namespace nx\helpers\model;

use nx\helpers\db\sql;
use nx\parts\callApp;
use nx\parts\db\table;
use nx\parts\model\cache;

/**
 * 群组数据
 * @package life\models\content
 */
class multiple{
	use callApp, cache, table;
	public static bool $TOMBSTONE=false; //逻辑删除
	public static string $FIELD_CREATED ='created_at';
	public static string $FIELD_UPDATED ='updated_at';
	public static string $FIELD_DELETED ='deleted_at';
	protected mixed $single=null;
	/**
	 * 私有方法 返回单条数据
	 * @param array $conditions 查询条件
	 * @param array $options
	 * @return array|null
	 */
	protected function _find(array $conditions=[], array $options=[]):?array{
		if(static::$TOMBSTONE) $conditions[static::$FIELD_DELETED] =0;
		$table=$this->table()->select()->where($conditions);
		if(array_key_exists('sort', $options)) $table->sort($options['sort'], 'DESC');
		$table->select(array_key_exists('select', $options) ?$options['select'] :[]);
		if(array_key_exists('FIND', $options) && is_callable($options['FIND'])) call_user_func($options['FIND'], $table, $conditions, $options);
		return $table->execute()->first($this->single);
	}
	/**
	 * @param sql $table
	 * @param array              $conditions
	 * @param array              $options
	 * @return array|null
	 */
	private function __list(sql $table, array $conditions, array $options):?array{
		$desc=$options['desc'] ?? 'DESC';
		if(is_int($desc)) $desc=['ASC', 'DESC'][$desc] ?? 'DESC';
		if(array_key_exists('sort', $options)) $table->sort($options['sort'], $desc);
		$table->select(array_key_exists('select', $options) ?$options['select'] :[]);
		if(array_key_exists('LIST', $options) && is_callable($options['LIST'])) call_user_func($options['LIST'], $table, $conditions, $options);
		if(array_key_exists('FETCH', $options) && is_callable($options['FETCH'])){
			$list = call_user_func($options['FETCH'], $table->execute(), $options);
		}elseif(array_key_exists('MAP', $options) && is_callable($options['MAP'])){
			$list=$table->execute()->fetchMap($options['MAP']);
		}else $list=$table->execute()->fetchAll();
		return $list;
	}
	protected function __count(sql $table, array $conditions=[], array $options=[]):int{
		$table->select($table::COUNT('*')->as('COUNT'));
		if(count($conditions)) $table->where($conditions);
		if(array_key_exists('COUNT', $options) && is_callable($options['COUNT'])) call_user_func($options['COUNT'], $table, $conditions, $options);
		$ok=$table->execute()->first();
		return null !== $ok ?$ok['COUNT'] :0;
	}
	/**
	 * 私有方法 返回多条数据
	 * @param array $conditions
	 * @param array $options
	 * @return array
	 */
	protected function _list(array $conditions=[], array $options=[]):array{
		$table=$this->table();
		if(static::$TOMBSTONE) $conditions[static::$FIELD_DELETED] =0;
		if(array_key_exists('page', $options) && !array_key_exists('COUNT', $options) && false === $options['page']){
			if(count($conditions)) $table->where($conditions);
			$list=$this->__list($table, $conditions, $options);
			$count=count($list);
		}else{
			$count =$this->__count($table, $conditions, $options);
			if($count > 0){
				if(array_key_exists('page', $options) && false !== $options['page']) $table->page($options['page'] ?? 1, $options['max'] ?? 10);
				$list=$this->__list($table, $conditions, $options);
			}else $list=[];
		}
		return ['count'=>$count, 'list'=>$list];
	}
	/**
	 * 返回数据列表
	 * @param array $conditions 查询条件
	 * @param array $options    支持 sort 排序参数 page 翻页 [page, max]
	 * @return array
	 */
	public function list(array $conditions=[], array $options=[]):array{
		return $this->_list($conditions, $options);
	}
}