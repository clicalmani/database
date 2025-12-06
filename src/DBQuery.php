<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Database\Factory\Create;
use Clicalmani\Database\Factory\Drop;
use Clicalmani\Database\Factory\Alter;
use Clicalmani\Database\Interfaces\JoinClauseInterface;
use Clicalmani\Foundation\Collection\Map;

/**
 * Database query
 * 
 * Generate the SQL statement to be executed for the requested query.
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class DBQuery extends DB implements Interfaces\QueryInterface
{
	/**
	 * Query builder parameters
	 * 
	 * @var array $params
	 */
	public $params;

	/**
	 * Select flag
	 * 
	 * @var int 0
	 */
	const SELECT = 0;

	/**
	 * Insert flag
	 * 
	 * @var int 1
	 */
	const INSERT = 1;

	/**
	 * Delete flag
	 * 
	 * @var int 2
	 */
	const DELETE = 2;

	/**
	 * Update flag
	 * 
	 * @var int 3
	 */
	const UPDATE = 3;

	/**
	 * Create flag
	 * 
	 * @var int 4
	 */
	const CREATE = 4;

	/**
	 * Alter flag
	 * 
	 * @var int 7
	 */
	const ALTER  = 7;

	/**
	 * Drop table flag
	 * 
	 * @var int 5
	 */
	const DROP_TABLE = 5;

	/**
	 * Dropt table if exists flag
	 * 
	 * @var int 6
	 */
	const DROP_TABLE_IF_EXISTS = 6;

	/**
	 * Lock table flag
	 * 
	 * @var int 8
	 */
	const LOCK_TABLE = 8;

	/**
	 * Unlock table flag
	 * 
	 * @var int 9
	 */
	const UNLOCK_TABLE = 9;

	/**
	 * Replace flag
	 * 
	 * @var int 10
	 */
	const REPLACE = 10;

	/**
	 * Truncate flag
	 * 
	 * @var int 11
	 */
	const TRUNCATE = 11;

	/**
	 * Builder
	 * 
	 * @var \Clicalmani\Database\DBQueryBuilder
	 */
	private $builder;
	
	/**
	 * Constructor
	 * 
	 * @param int|null $query [Optional] DBQuery flag
	 * @param array $params [Optional] Query parameters
	 * @param array $options [Optional] 
	 */
	public function __construct(private ?int $query = null, ?array $params = [], private ?array $options = [])
	{ 
		$this->params = isset($params)? $params: [];
		$this->query = $query;
	}
	
	public function set(string $param, mixed $value) : self
	{ 
		if ($param == 'type') {
			$this->query = $value;
		} else $this->params[$param] = $value;

		return $this;
	}

	public function unset(string $param) : self
	{
		unset($this->params[$param]);
		return $this;
	}

	public function setOptions(array $options) : void
	{
		$this->options = $options;
	}

	public function getParam(string $param, mixed $default = null) : mixed
	{
		if (isset($this->params[$param])) {
			return $this->params[$param] ?? $default;
		}

		return $default;
	}

	public function exec() : \Clicalmani\Database\DBQueryBuilder
	{ 
		$this->query = isset($this->params['query'])? $this->params['query']: $this->query;

		if ( isset($this->params['connection']) ) {
			self::connection($this->params['connection']);
		}
		
		switch ($this->query){
			
			case static::SELECT:
				$this->builder = new Select($this->params, $this->options);
				$this->builder->query();
				break;
			
			case static::INSERT:
				$this->builder = new Insert($this->params, $this->options);
				$this->builder->query();
				break;
				
			case static::DELETE:
				$this->builder = new Delete($this->params, $this->options);
				$this->builder->query();
				break;
				
			case static::UPDATE:
				$this->builder = new Update($this->params, $this->options);
				$this->builder->query();
				break;

			case static::CREATE:
				$this->builder = new Create($this->params, $this->options);
				$this->builder->query();
				break;

			case static::DROP_TABLE:
				$this->builder = new Drop($this->params, $this->options);
				$this->builder->query();
				break;

			case static::DROP_TABLE_IF_EXISTS:
				$this->params['exists'] = true;
				$this->builder = new Drop($this->params, $this->options);
				$this->builder->query();
				break;

			case static::ALTER:
				$this->builder = new Alter($this->params, $this->options);
				$this->builder->query();
				break;

			case static::LOCK_TABLE:
				$this->builder = new Lock($this->params, $this->options);
				$this->builder->query();
				break;

			case static::UNLOCK_TABLE:
				$this->builder = new Unlock($this->params, $this->options);
				$this->builder->query();
				break;

			case static::REPLACE:
				$this->builder = new Replace($this->params, $this->options);
				$this->builder->query();
				break;

			case static::TRUNCATE:
				$this->builder = new Truncate($this->params, $this->options);
				$this->builder->query();
				break;
		}

		// Clear events data
		unset($this->params['muted_events']);
		unset($this->params['prevent_events']);

		return $this->builder;
	}

	public function delete() : static
	{
		$this->query = static::DELETE;
		return $this;
	}

	public function truncate() : bool
	{
		$table = @ isset( $this->params['tables'][0] ) ? $this->params['tables'][0]: null;

		if ( isset( $table ) ) {
			unset($this->params['tables']);
			$this->params['table'] = $table;
		}

		$this->query = static::TRUNCATE;

		return $this->exec()->status() === 'success';
	}

	public function update(array $options = []) : bool
	{
		$this->set('query', DBQuery::UPDATE);

		$fields = array_keys( $options );
		$values = array_values( $options );
		
		$this->params['fields'] = $fields;
		$this->params['values'] = $values;
		
		return $this->exec()->status() === 'success';
	}

	public function increment(string $field, int $value = 1, ?array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $field . ' + ' . $value]) );
	}

	public function decrement(string $field, int $value = 1, array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $field . ' - ' . $value]) );
	}

	public function insert(array $options = [], bool $replace = false) : bool
	{
		if ( array_filter($options, fn($entry) => is_string($entry)) ) {
			$options = [$options];
		}
		
		$table = @ isset( $this->params['tables'][0] ) ? $this->params['tables'][0]: null;
		
		if ( isset( $table ) ) {
			unset($this->params['tables']);
			$this->params['table'] = $table;
		}

		$this->params['values'] = [];

		foreach ($options as $option) {
			$fields = array_keys( $option );
			$values = array_values( $option );
			
			$this->params['fields']   = $fields;
			$this->params['values'][] = $values;
		}
		
		$this->set('query', (FALSE === $replace) ? self::INSERT: self::REPLACE); 
		
		return $this->exec()->status() === 'success';
	}

	public function insertOrFail(array $options = []) : bool
	{
		try {
			return $this->insert($options);
		} catch (\PDOException $e) {
			return false;
		}
	}

	public function insertGetId(array $options = []) : int
	{
		$this->insert($options);
		return DB::lastInsertId();
	}

	public function insertOrUpdate(array $options) : void
	{
		if (false === $this->insertOrFail($options)) {

			// Reset table for update
			$this->params['tables'] = [$this->params['table']];

			foreach ($options as $option) $this->update($option);
		}
	}

	public function where( ...$args ) : self
	{
		switch(count($args)) {
			case 1:
				$criteria = $args[0];
				$operator = 'AND';
				$options  = [];
			break;

			case 2:
				$criteria = $args[0];
				$operator = 'AND';
				$options  = $args[1];
			break;

			case 3:
				$criteria = $args[0];
				$operator = $args[1];
				$options  = $args[2];
			break;

			default: return $this;
		}
		
		$this->options = $options;

		$criteria = trim($criteria);

		if ( empty($criteria) ) $criteria = '1';

		if ( !isset($this->params['where']) ) {
			$this->params['where'] = $criteria;
		} else {
			$this->params['where'] .= " $operator " . $criteria;
		}
		
		return $this;
	}

	public function having(string $criteria) : self
	{
		if ( !isset($this->params['having']) ) {
			$this->params['having'] = $criteria;
		} else {
			$this->params['having'] .= ' AND ' . $criteria;
		}
		
		return $this;
	}

	public function orderBy(string $order_by) : static
	{
		$this->params['order_by'] = $order_by;
		return $this;
	}

	public function groupBy(string $group_by) : static
	{
		$this->params['group_by'] = $group_by;
		return $this;
	}

	public function distinct(bool $distinct = true) : static
	{
		$this->params['distinct'] = $distinct;
		return $this;
	}

	public function from(string $fields) : static
	{
		$this->params['fields'] = $fields;
		return $this;
	}

	public function get(string $fields = '*') : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		$this->params['fields'] = $fields;
		$result = $this->exec();
		$collection = new Collection;
		
		foreach ($result as $row) {
			$collection->add($row);
		}

		return $collection;
	}

	public function all() : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		$this->params['where'] = 'TRUE';
		return $this->exec()->result();
	}

	public function limit(int $offset = 0, int $limit = 1) : static
	{
		$this->params['calc'] = true;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $limit;
		
		return $this;
	}

	public function top(int $limit = 1) : static
	{
		$this->params['limit'] = $limit;
		return $this;
	}

	public function join(mixed $table, ?callable $callback = null) : static
	{
		if (NULL === $callback && is_string($table)) $this->params['tables'][] = $table;
		else {
			$clause = new \Clicalmani\Database\JoinClause;

			if ( is_string($table) ) $joint = ['table' => $table];

			if ( is_callable($callback) ) $callback($clause);
			elseif ( is_callable($table) ) $table($clause);
			
			if (!empty($clause->on)) $joint['criteria'] = $clause->on;
			if (isset($clause->type)) $joint['type'] = $clause->type;
			if (isset($clause->sub_query)) $joint['sub_query'] = $clause->sub_query;
			if (isset($clause->alias)) $joint['alias'] = $clause->alias;
			
			if (isset($joint)) $this->params['join'][] = $joint;
		}

		return $this;
	}

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Parent key
	 * @return static
	 */
	private function __join(string $table, ?string $foreign_key = null, ?string $original_key = null, string $type = 'LEFT', ?bool $is_crossed = false, ?string $operator = '=') : static
	{
		if ( ! isset($foreign_key) ) $foreign_key = strtolower($table).'_id';
		
		return $this->join($table, function(JoinClauseInterface $join) use ($foreign_key, $is_crossed, $original_key, $type, $operator) {
			$join->type($type);
			if ($is_crossed) $join->on('');
			else if ($foreign_key != $original_key) $join->on($foreign_key . $operator . $original_key);
			else $join->using($foreign_key);
		});
	}

	public function joinLeft(string $table, ?string $foreign_key = null, ?string $original_key = null) : self
	{
		return $this->__join($table, $foreign_key, $original_key);
	}

	public function joinRight(string $table, ?string $foreign_key = null, ?string $original_key = null) : self
	{
		return $this->__join($table, $foreign_key, $original_key, 'RIGHT');
	}

	public function joinInner(string $table, ?string $foreign_key = null, ?string $original_key = null) : self
	{
		return $this->__join($table, $foreign_key, $original_key, 'INNER');
	}

	public function joinCross(string $table) : self
	{
		return $this->__join($table, '', '', 'CROSS', true);
	}

	public function lock(?string $type = 'WRITE', ?bool $disable_keys = false) : bool
	{
		if ( ! in_array($type, ['READ', 'READ LOCAL', 'WRITE']) ) $type = 'WRITE';

		$this->query = static::LOCK_TABLE;
		$this->params['lock_type'] = $type;
		if ( $disable_keys ) $this->params['disable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	public function unlock(?bool $enable_keys = false) : bool
	{
		$this->query = static::UNLOCK_TABLE;
		if ( $enable_keys ) $this->params['enable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	public function getBuilderResult() : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		return $this->builder->result();
	}

	public function getBuilder() : ?\Clicalmani\Database\Interfaces\BuilderInterface
	{
		return $this->builder;
	}

	public function first() : mixed
	{
		$this->params['limit'] = 1;
		$result = $this->exec();
		return (object) $result->result()->first();
	}

	public function firstValue(string $field) : mixed
	{
		$this->params['fields'] = $field;
		$this->params['limit'] = 1;
		$result = $this->exec();
		return $result->result()->first()[$field];
	}

	public function firstOrFail() : mixed
	{
		$result = $this->first();
		if ( !isset($result) ) {
			throw new \Exception('No record found');
		}

		return $result;
	}

	public function value(string $field) : mixed
	{
		$this->params['fields'] = $field;
		$result = $this->exec();
		return $result->result()->first()[$field];
	}

	public function count(string $field = '*') : int
	{
		$this->params['fields'] = "COUNT($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["COUNT($field)"];
	}

	public function sum(string $field) : int
	{
		$this->params['fields'] = "SUM($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["SUM($field)"];
	}

	public function max(string $field) : int
	{
		$this->params['fields'] = "MAX($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["MAX($field)"];
	}

	public function min(string $field) : int
	{
		$this->params['fields'] = "MIN($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["MIN($field)"];
	}

	public function avg(string $field) : int
	{
		$this->params['fields'] = "AVG($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["AVG($field)"];
	}

	public function find(int $id, ?string $column = 'id') : mixed
	{
		$this->params['where'] = "$column = :id";
		$this->options = ['id' => $id];
		return $this->exec()->result()->first();
	}

	public function chunk(int $size, callable $callback) : void
	{
		$offset = 0;
		$limit = $size;

		do {
			$this->params['offset'] = $offset;
			$this->params['limit'] = $limit;
			$result = $this->exec()->result();

			if ( $result->count() > 0 ) {
				if (FALSE === $callback($result)) break;
				$offset += $size;
			}
		} while ( $result->count() > 0 );
	}

	public function chunkById(int $size, callable $callback, ?string $column = 'id') : void
	{
		$offset = 0;
		$limit = $size;

		do {
			$this->params['where'] = "$column > :offset";
			$this->params['limit'] = $limit;
			$this->options = ['offset' => $offset];
			$result = $this->exec()->result();

			if ( $result->count() > 0 ) {
				if (FALSE === $callback($result)) break;
				$offset += $size;
			}
		} while ( $result->count() > 0 );
	}

	public function paginate(int $page, int $size) : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		$offset = ($page - 1) * $size;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $size;
		return $this->exec()->result();
	}

	public function simplePaginate(int $page, int $size) : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		$offset = ($page - 1) * $size;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $size;
		$this->params['calc'] = false;
		return $this->exec()->result();
	}

	public function lazy() : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		return $this->exec()->result();
	}

	public function pluck(string $field, ?string $key = null) : Map
	{
		$this->params['fields'] = $field;
		$result = $this->exec();
		$collection = collection()->asMap();

		foreach ($result as $index => $row) {
			if (NULL !== $key) $collection->set($row[$key], $row[$field]);
			else $collection->set($index, $row[$field]);
		}

		return $collection;
	}

	public function lazyBy(string $field, ?string $key = null) : Map
	{
		$this->params['fields'] = $field;
		$result = $this->exec()->result();
		$collection = collection()->asMap();

		foreach ($result as $index => $row) {
			if (NULL !== $key) $collection->set($row[$key], $row[$field]);
			else $collection->set($index, $row[$field]);
		}

		return $collection;
	}

	public function lazyByDesc(string $field, ?string $key = null) : Map
	{
		$this->params['fields'] = $field;
		$this->params['order_by'] = "$field DESC";
		$result = $this->exec()->result();
		$collection = collection()->asMap();

		foreach ($result as $index => $row) {
			if (NULL !== $key) $collection->set($row[$key], $row[$field]);
			else $collection->set($index, $row[$field]);
		}

		return $collection;
	}

	public function exists() : bool
	{
		return $this->count() > 0;
	}

	public function doesntExist() : bool
	{
		return $this->count() === 0;
	}

	public function when(bool $condition, callable $callback) : static
	{
		if ($condition) $callback($this->query);
		return $this;
	}

	public function unless(bool $condition, callable $callback) : static
	{
		if (!$condition) $callback($this->query);
		return $this;
	}
}
