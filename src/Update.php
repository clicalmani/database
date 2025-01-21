<?php
namespace Clicalmani\Database;

/**
 * Class Update
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Update extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql = 'UPDATE ' . (isset($this->params['low_priority']) ? 'LOW_PRIORITY ': '') . (isset($this->params['ignore']) ? 'IGNORE ': '') . collection()->exchange($this->params['tables'])->map(function($table) {
			$arr = preg_split('/\s/', $table, -1, PREG_SPLIT_NO_EMPTY);
			$table = $arr[0];

			if ($arr[0] !== $arr[sizeof($arr)-1]) $table .= ' ' . end($arr);

			return DB::getPrefix() . $table;
		})->join(',');

		if (isset($this->params['join'])) {

			$tables = [];
			
			foreach ($this->params['join'] as $joint) {
				
				$tables[] = DB::getPrefix() . $joint['table'];
			}

			$this->sql .= ', ' . join(',', $tables);
		}
		
		$this->sql .= ' SET ' . collection()->exchange($this->params['fields'])->map(function($field, $index) {
			return "`$field` = :$field";
		})->join(',');
		
		$this->sql .= ' WHERE TRUE ';
		
		if (isset($this->params['where'])) {
			
			$this->sql .= 'AND ' . $this->params['where'];
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
		/** @var \PDOStatement */
		$statement = DB::prepare($this->sql, $this->params['options']);

		$this->dispatch('query');

		foreach ($this->params['values'] as $i => $type) {
			if ( is_subclass_of($type, \Clicalmani\Database\Factory\DataTypes\DataType::class) ) $value = $type->getValue();
			else $value = $type;
			$statement->bindValue($this->params['fields'][$i], $value, $this->getDataType($type));
		}
		
		foreach ($this->options as $param => $value) {
			$statement->bindValue($param, $value, $this->getDataType($value));
		}

		$statement->execute();
    		
		$this->status     = $statement ? true: false;
	    $this->error_code = DB::errno();
	    $this->error_msg  = DB::error();

		$statement = null;
	}
	
	/**
	 * Get iterator
	 * 
	 * @return \Traversable
	 */
	public function getIterator() : \Traversable 
	{
		return new DBQueryIterator($this);
	}
}
