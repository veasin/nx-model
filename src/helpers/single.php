<?php
namespace nx\helpers\model;

use nx\parts\callApp;
use nx\parts\db\table;
use nx\parts\model\cache;
use nx\parts\model\plugin;

class single{
	use callApp, plugin, cache, table;

	public int $id=0;
	protected string $id_key ='id';
	protected array $data=[];
	protected array $_data=[];//初始化数据
	/**
	 * @var multiple
	 */
	protected ?string $multiple=null;
	public function __construct(array $data=[]){
		if(null ===$this->multiple) throw new \Error(static::class. ' need set multiple!');
		$this->data=$this->_data=$data ?? [];
		$this->id=$this->data[$this->id_key] ?? 0;
		if(0 === $this->id) $this->default($data);
	}
	/**
	 * 设置默认属性值
	 * @param array $data
	 */
	protected function default(array $data=[]):void{
		$this->plugin('default', $data);
	}
	/**
	 * 保存当前对象中的数据，如不存在即添加
	 * @return bool
	 */
	public function save():bool{
		if($this->id){
			$update=array_diff_assoc($this->data, $this->_data);//对比出需要更新数据
			if(empty($update)) return true;//如果无须更新返回成功
			if($this->multiple::$FIELD_UPDATED) $update[$this->multiple::$FIELD_UPDATED]=time();
			$ok=$this->table()->where([$this->id_key=>$this->id])->update($update)->execute()->ok();
			$this->_data=$this->data;
			if($ok) $this->plugin('update');
		}else{
			if($this->multiple::$FIELD_CREATED && !array_key_exists($this->multiple::$FIELD_CREATED, $this->data)) $this->data[$this->multiple::$FIELD_CREATED]=time();
			$this->plugin('before_create');
			$id=$this->table()->create($this->data)->execute()->lastInsertId();
			$ok=$id > 0;
			if($ok){
				$this->data=$this->_data=$this->table()->where([$this->id_key=>$id])->select()->execute()->first();
				$this->id=$this->data[$this->id_key];
				$this->plugin('create');
				$this->save();//触发二次保存逻辑
			}
		}
		return $ok;
	}
	/**
	 * 删除当前对象本身的数据，如未记录直接忽略
	 * @return bool
	 */
	public function delete():bool{
		if($this->id > 0){
			$this->_data=$this->data;
			$table=$this->table()->where([$this->id_key=>$this->id]);
			if($this->multiple::$TOMBSTONE && $this->multiple::$FIELD_DELETED){//逻辑删除
				$table->update([$this->multiple::$FIELD_DELETED=>time()]);
			}else $table->delete();
			$ok=$table->execute()->ok();
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
	 * @return $this
	 */
	public function update(array $data):self{
		foreach($data as $key=>$value){
			if(array_key_exists($key, $this->_data)) $this->data[$key]=$value;
		}
		return $this;
	}
	public function output(array $options=[]):mixed{
		return [] + $this->data;
	}
}
