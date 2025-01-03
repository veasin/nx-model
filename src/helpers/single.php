<?php
namespace nx\helpers\model;

use nx\helpers\db\sql;
use nx\parts\callApp;
use nx\parts\model\cache;
use nx\parts\model\plugin;

abstract class single{
	use callApp, plugin, cache;

	public int $id=0;
	protected string $id_key ='';
	protected array $data=[];
	protected array $_data=[];//初始化数据
	/**
	 * @var multiple
	 */
	protected const MULTIPLE=null;
	public function __construct(array $data=[]){
		if(null ===static::MULTIPLE) throw new \Error(static::class. ' need set multiple!');
		if(empty($this->id_key)) $this->id_key = static::MULTIPLE::TABLE_PRIMARY;
		$this->data=$this->_data=$data ?? [];
		$this->id=$this->data[$this->id_key] ?? 0;
		if(0 === $this->id) $this->default($data);
	}
	/**
	 * @param string|null $tableName
	 * @param string|null $primary
	 * @param string|null $config
	 * @return sql
	 */
	protected function table(?string $tableName=null, ?string $primary=null, ?string $config=null):sql{
		return $this->db($config ?? static::MULTIPLE::TABLE_DB)->from($tableName ?? static::MULTIPLE::TABLE, $primary ?? static::MULTIPLE::TABLE_PRIMARY ?? 'id');
	}
	/**
	 * 设置默认属性值
	 * @param array $data
	 */
	protected function default(array $data=[]):void{
		$this->plugin('default', $data);
	}
	protected function updateMiddleware($update=[]):\Generator{
		return yield true;
	}
	protected function createMiddleware():\Generator{
		return yield true;
	}
	protected function deleteMiddleware():\Generator{
		return yield true;
	}
	/**
	 * 保存当前对象中的数据，如不存在即添加
	 * @return bool
	 */
	public function save():bool{
		if($this->id){
			$update=array_diff_assoc($this->data, $this->_data);//对比出需要更新数据
			$middleware =$this->updateMiddleware($update);
			if($middleware->current() && !empty($update)){
				if(static::MULTIPLE::FIELD_UPDATED) $update[static::MULTIPLE::FIELD_UPDATED] = time();
				$ok = $this->table()->where([$this->id_key => $this->id])->update($update)->execute()->ok();
			}
			$middleware->send($ok ?? true);
			$ok=$middleware->getReturn();
			if($ok){
				$this->_data = $this->data;
				$this->plugin('update', $update);
			}
		}else{
			$this->plugin('before_create');
			$middleware =$this->createMiddleware();
			if($middleware->current()){
				if(static::MULTIPLE::FIELD_CREATED && !array_key_exists(static::MULTIPLE::FIELD_CREATED, $this->data)) $this->data[static::MULTIPLE::FIELD_CREATED]=time();
				$id=$this->table()->create($this->data)->execute()->lastInsertId();
				$ok=$id > 0;
				if($ok){
					$this->data=$this->_data=$this->table()->where([$this->id_key=>$id])->select()->execute()->first();
					$this->id=$this->data[$this->id_key];
					$this->save();//触发二次保存逻辑
				}
			}
			$middleware->send($ok ?? true);
			$ok=$middleware->getReturn();
			if($ok) $this->plugin('create');
		}
		return $ok;
	}
	/**
	 * 删除当前对象本身的数据，如未记录直接忽略
	 * @return bool
	 */
	public function delete():bool{
		if($this->id > 0){
			$middleware =$this->deleteMiddleware();
			if($middleware->current()){
				$this->_data = $this->data = [];
				$table = $this->table()->where([$this->id_key => $this->id]);
				if(static::MULTIPLE::TOMBSTONE && static::MULTIPLE::FIELD_DELETED){//逻辑删除
					$table->update([static::MULTIPLE::FIELD_DELETED => time()]);
				}
				else $table->delete();
				$ok = $table->execute()->ok();
			}
			$middleware->send($ok ?? true);
			$ok=$middleware->getReturn();
			if($ok) $this->plugin('delete');
			return $ok;
		}else{
			$this->id=0;
			return true;
		}
	}
	/**
	 * 更新自身数据
	 * @param array $data
	 * @param bool  $over 是否覆盖更新
	 * @return $this
	 */
	public function update(array $data, bool $over=false):self{
		foreach($data as $key=>$value){
			if($over) $this->data[$key]=$value;
			else if(array_key_exists($key, $this->_data)) $this->data[$key]=$value;
		}
		return $this;
	}
	public function output(array $options=[]):mixed{
		return [] + $this->data;
	}
}
