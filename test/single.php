<?php

include_once "../vendor/autoload.php";
error_reporting(E_ALL);
use nx\test\test;

class users extends \nx\helpers\model\multiple{
	public const string TABLE = 'user';
	protected function table(?string $tableName = null, ?string $primary = null, ?string $config = null): \nx\helpers\db\sql{
		return new fakeSQL($tableName ?? static::TABLE, $primary ?? static::TABLE_PRIMARY, null);
	}
}

class user extends \nx\helpers\model\single{
	const MULTIPLE = users::class;
	protected function table(?string $tableName = null, ?string $primary = null, ?string $config = null): \nx\helpers\db\sql{
		return new fakeSQL($tableName ?? static::MULTIPLE::TABLE, $primary ?? static::MULTIPLE::TABLE_PRIMARY, null);
	}
	protected function delete_before(){// a-z before middle
		//yield 'ok';

	}

}

class fakeResult extends \nx\helpers\db\pdo\result{
	protected array $data =[];
	protected int $count = 0;
	protected int $last =1;
	public function ok():bool{
		return $this->result;
	}
	/**
	 * $db->update() <=false
	 *      ->affectedRows();
	 *
	 */
	public function rowCount():?int{
		return $this->result ? $this->count : null;
	}
	public function lastInsertId():?int{
		return $this->result ? (int)$this->last : null;
	}
	public function first($className=null, ...$args):mixed{
		if(!$this->result) return null;
		$r =$className
			? new $className(...$args)
			: $this->data[0] ?? null;
		return $r ?: null;
	}
	public function all($className=null, ...$args):?array{
		if(!$this->result) return null;
		return $className
			? array_map(fn($args)=>new $className(...$args), $this->data)
			: $this->data;
	}
	public function setData($last=1, $data=null, $count=null):void{
		$this->last = $last;
		$this->data = $data;
		if(is_array($data)){
			if(is_null($count)) $count = count($data);
		}
		if(!is_null($count)) $this->count = $count;
	}
}

class fakeSQL extends nx\helpers\db\sql{
	protected $result =[true, 1, [], null];
	public function setResult($result, $last=1, $data=null, $count=null):static{
		$this->result = [$result, $last, $data, $count];
	}
	public function execute(?\nx\helpers\db\pdo $db = null): \nx\helpers\db\pdo\result{
		$r = new fakeResult(($this->result[0]??false) ?true :false, null, null);
		switch($this->action){
			case 'insert':
				test::case('insert', (string)$this)
					->toBe('INSERT INTO `user` (`name`, `email`, `created_at`) VALUES (?, ?, ?)')
					->and($this->params[0])
					->toBeArray()
					->toHaveKey(0, 'test1')
					->toHaveKey(1, 'test@test.com');
				$r->setData(3, [], null);
				break;
			case 'update':
				test::case('update', (string)$this)
					->toBe('UPDATE `user`  SET `name` = ?, `updated_at` = ? WHERE `id` = ?')
					->and($this->params)
					->toBeArray()
					->toHaveKey(0, 'test1')
					->toHaveKey(2, 2);
				break;
			case 'select':
				test::case('select', (string)$this)
					->toBe('SELECT * FROM `user` WHERE `id` = ?')
					->and($this->params)
					->toBeArray()
					->toHaveKey(0, 3);
				$r->setData(3, [['id'=>3,'name'=>'test2', 'email'=>'test@test.com']], null);
				break;
			case 'delete':
				test::case('select', (string)$this)
					->toBe('DELETE FROM `user` WHERE `id` = ?')
					->and($this->params)
					->toBeArray()
					->toHaveKey(0, 2);
				break;
			default:
				var_dump((string)$this);
				break;
		}

		return $r;
	}
}

$user =new user(['name'=>'test', 'email'=>'test@test.com']);
$user->update(['name'=>'test1']);
$user->save();

$user =new user(['id'=>2,'name'=>'test', 'email'=>'test@test.com']);
$user->update(['name'=>'test1']);
$user->save();

$user->destroy();
