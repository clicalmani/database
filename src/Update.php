<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Support\Facades\DB;

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
		
		$this->sql = 'UPDATE ' . (isset($this->params['low_priority']) ? 'LOW_PRIORITY ': '') . (isset($this->params['ignore']) && $this->params['ignore'] ? 'IGNORE ': '') . collection()->exchange($this->params['tables'])->map(function($table) {
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

			if ($tables) $this->sql .= ', ' . join(',', $tables);
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
		$sql = $this->sql;

		// Prevents any collision or parameter marker style mixing
		// by rewriting the WHERE clause with unique parameter aliases.
		$whereBindings = [];

		if (isset($this->params['where'])) {

			$wherePos = strpos($sql, 'WHERE TRUE');
			$before   = substr($sql, 0, $wherePos);
			$whereSeg = substr($sql, $wherePos);

			$counter = 0;
			$options = $this->options;

			$whereSeg = preg_replace_callback(
				'/\?|:([a-zA-Z_][a-zA-Z0-9_]*)/',
				function($m) use (&$whereBindings, &$counter, &$options) {
					$alias = '__where_' . $counter++;

					if ($m[0] === '?') {
						// Positional style: consume parameters sequentially in order of appearance
						$value = array_shift($options);
					} else {
						// Named style: resolve parameters by key from $this->options
						$name  = $m[1];
						$value = $options[$name] ?? null;
					}

					$whereBindings[$alias] = $value;
					return ':' . $alias;
				},
				$whereSeg
			);

			$sql = $before . $whereSeg;
		}

		/** @var \PDOStatement */
		$statement = DB::prepare($sql, $this->params['options']);

		$this->dispatch('query');

		// 1. Bind the SET clause parameters
		foreach ($this->params['values'] as $i => $type) {
			$value = is_subclass_of($type, \Clicalmani\Database\Factory\DataTypes\DataType::class) 
				? $type->getValue() 
				: $type;

			if ( isset($this->params['marker']) && $this->params['marker'] === '?' ) {
				$statement->bindValue($i + 1, $value, $this->getDataType($type));
			} else {
				$statement->bindValue(':' . $this->params['fields'][$i], $value, $this->getDataType($type));
			}
		}

		// 2. Bind the WHERE clause parameters using the unique aliases
		foreach ($whereBindings as $alias => $value) {
			$statement->bindValue(':' . $alias, $value, $this->getDataType($value));
		}

		$statement->execute();

		$this->status     = $statement ? true : false;
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
