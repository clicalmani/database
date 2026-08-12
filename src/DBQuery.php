<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Database\Factory\Create;
use Clicalmani\Database\Factory\Drop;
use Clicalmani\Database\Factory\Alter;
use Clicalmani\Database\JoinClauseInterface;
use Clicalmani\Database\SubQueries\DBSubQuery;
use Clicalmani\Database\SubQueries\Exists;
use Clicalmani\Database\SubQueries\NotExists;
use Clicalmani\Database\SubQueries\SubWhere;
use Clicalmani\Database\SubQueries\WhereExists;
use Clicalmani\Database\SubQueries\WhereNotExists;
use Clicalmani\Database\SubQueries\WithExists;
use Clicalmani\Foundation\Collection\Map;

/**
 * Database query
 * 
 * Generate the SQL statement to be executed for the requested query.
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class DBQuery extends DB implements QueryInterface
{
	/**
	 * Query builder parameters
	 * 
	 * @var array $params
	 */
	public $params;

	/**
	 * Relations to eager load
	 * 
	 * @var array $options
	 */
	protected array $with = [];

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
	 * Union flag
	 * 
	 * @var int 12
	 */
	const UNION = 12;

	/**
	 * Show columns flag
	 * 
	 * @var int 13
	 */
	const SHOW_COLUMNS = 13;

	/**
	 * Builder
	 * 
	 * @var \Clicalmani\Database\DBQueryBuilder
	 */
	private $builder;

	/**
	 * Union query
	 * 
	 * @var \Clicalmani\Database\DBQuery
	 */
	private $union_query;

	/**
	 * Backup of the current query parameters and options for subqueries
	 * 
	 * @var bool false
	 */
	private array $backup = [];
	
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

	public function setOptions(array $options, bool $merge = false) : void
	{
		if (FALSE === $merge) $this->options = $options;
		else $this->options = array_merge($this->options, $options);
	}

	/**
	 * Set query parameters
	 * 
	 * @param array $newParams
	 */
	public function setParams(array $newParams): void
	{
		$this->params = $newParams;
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
				break;
			
			case static::INSERT:
				$this->builder = new Insert($this->params, $this->options);
				break;
				
			case static::DELETE:
				$this->builder = new Delete($this->params, $this->options);
				break;
				
			case static::UPDATE:
				$this->builder = new Update($this->params, $this->options);
				break;

			case static::CREATE:
				$this->builder = new Create($this->params, $this->options);
				break;

			case static::DROP_TABLE:
				$this->builder = new Drop($this->params, $this->options);
				break;

			case static::DROP_TABLE_IF_EXISTS:
				$this->params['exists'] = true;
				$this->builder = new Drop($this->params, $this->options);
				break;

			case static::ALTER:
				$this->builder = new Alter($this->params, $this->options);
				break;

			case static::LOCK_TABLE:
				$this->builder = new Lock($this->params, $this->options);
				break;

			case static::UNLOCK_TABLE:
				$this->builder = new Unlock($this->params, $this->options);
				break;

			case static::REPLACE:
				$this->builder = new Replace($this->params, $this->options);
				break;

			case static::TRUNCATE:
				$this->builder = new Truncate($this->params, $this->options);
				break;

			case static::UNION:
				if (!isset($this->union_query)) {
					throw new \Exception('Union query not defined');
				}
				break;

			case static::SHOW_COLUMNS:
				$this->builder = new ShowColumns($this->params, $this->options);
				break;
		}

		$this->builder->query();

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

	public function truncate() : self
	{
		$table = @ isset( $this->params['tables'][0] ) ? $this->params['tables'][0]: null;

		if ( isset( $table ) ) {
			unset($this->params['tables']);
			$this->params['table'] = $table;
		}

		$this->query = static::TRUNCATE;

		return $this;
	}

	public function update(?array $options = []) : self
	{
		$this->set('query', DBQuery::UPDATE);

		$fields = array_keys( $options );
		$values = array_values( $options );
		
		$this->params['fields'] = $fields;
		$this->params['values'] = $values;
		
		if (!isset($this->params['marker'])) {
			$this->params['marker'] = ':';
		}

		return $this;
	}

	public function increment(string $field, int $value = 1, ?array $fields = []) : self
	{
		return $this->update( array_merge($fields, [$field => $field . ' + ' . $value]) );
	}

	public function decrement(string $field, int $value = 1, array $fields = []) : self
	{
		return $this->update( array_merge($fields, [$field => $field . ' - ' . $value]) );
	}

	public function insert(array $options = [], bool $replace = false) : self
	{
		if ( array_filter($options, fn($entry) => ! is_array($entry)) ) {
			$options = [$options];
		}
		
		// Make sure there is no table alias in the table name for insert query
		$arr = explode(' ', $this->params['tables'][0]);
		$table = @ count( $arr ) ? array_shift($arr): null;
		
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
		
		return $this;
	}

	/**
	 * Insert ignore query
	 * 
	 * @param array $options Insert options
	 * @param bool $replace [Optional] Whether to replace existing records or ignore them
	 * @return self
	 */
	public function insertIgnore(array $options = [], bool $replace = false): self
	{
		$this->params['ignore'] = true;
		return $this->insert($options, $replace);
	}

	public function insertOrFail(array $options = []) : bool
	{
		try {
			return $this->insert($options)->exec()->status() === 'success';
		} catch (\PDOException $e) {
			return false;
		}
	}

	public function insertGetId(array $options = [], bool $replace = false) : int
	{
		try {
			$this->insert($options, $replace)->exec();
			return DB::lastInsertId();
		} catch (\Throwable $e) {
			throw $e;
		}
	}

	public function where( ...$args ): self
	{
		/**
		 * Logical grouping with closure: If the first argument is a closure, it will be treated as a logical group of conditions. The closure will receive an instance of the query builder, allowing you to define multiple conditions within the group. The resulting SQL will wrap these conditions in parentheses and combine them with an OR operator.
		 * Example usage:
		 * $query->orWhere(function($query) {
		 *     $query->where('status = ?', ['active'])
		 *           ->where('created_at > ?', ['2024-01-01']);
		 * });
		 * This will generate a SQL query that includes a condition like: OR (status = 'active' AND created_at > '2024-01-01')
		 */
		if ($args && $args[0] instanceof \Closure) {

			$callback = $args[0];
			$tables = $this->params['tables'];
			$subquery = new DBSubQuery($this, function(self $query) use($callback, $tables) {
				$query->set('tables', $tables);
				$callback($query);
			});
			
			$subquery->call();
			
			$subCondition = $subquery->getQuery()->getParam('where');
			
			$subquery->restore();
			
			return $this->where( '(' . $subCondition . ')', 
				$subquery->getOptions()
			);
		}

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
		
		$this->options = is_array($options) ? array_merge($this->options, $options): $this->options;
		
		$criteria = trim($criteria);

		if ( empty($criteria) ) $criteria = '1';

		if ( !isset($this->params['where']) ) {
			$this->params['where'] = $criteria;
		} else {
			$this->params['where'] .= " $operator " . $criteria;
		}
		
		return $this;
	}

	/**
	 * Add an OR condition to the query. The method accepts the same parameters as the where() method, but the condition will be added with an OR operator instead of AND.
	 * 
	 * @param mixed ...$args The arguments for the where condition, same as the where() method
	 * @return self
	 */
	public function orWhere(mixed ...$args): self
	{
		/**
		 * Logical grouping with closure: If the first argument is a closure, it will be treated as a logical group of conditions. The closure will receive an instance of the query builder, allowing you to define multiple conditions within the group. The resulting SQL will wrap these conditions in parentheses and combine them with an OR operator.
		 * Example usage:
		 * $query->orWhere(function($query) {
		 *     $query->where('status = ?', ['active'])
		 *           ->where('created_at > ?', ['2024-01-01']);
		 * });
		 * This will generate a SQL query that includes a condition like: OR (status = 'active' AND created_at > '2024-01-01')
		 */
		if ($args && $args[0] instanceof \Closure) {

			$callback = $args[0];
			$tables = $this->params['tables'];
			$subquery = new DBSubQuery($this, function(self $query) use($callback, $tables) {
				$query->set('tables', $tables);
				$callback($query);
			});
			
			$subquery->call();
			
			$subCondition = $subquery->getQuery()->getParam('where');
			
			$subquery->restore();
			
			return $this->where( '(' . $subCondition . ')', 'OR',
				$subquery->getOptions()
			);
		}

		switch(count($args)) {
			case 1:
				$criteria = $args[0];
				$options  = [];
			break;

			case 2:
				$criteria = $args[0];
				$options  = $args[1];
			break;

			default: return $this;
		}

		return $this->where($criteria, 'OR', $options);
	}
	
	public function whereExists(\Closure|string $criteria, ?array $options = [], ?string $boolean = 'AND') : self
	{
		if ($criteria instanceof \Closure) {
			return (new Exists($this, $criteria))($boolean);
		}

		return $this->where('EXISTS (' . $criteria . ')', $boolean, $options);
	}
	
	public function whereNotExists(\Closure|string $criteria, ?array $options = [], ?string $boolean = 'AND') : self
	{
		if ($criteria instanceof \Closure) {
			return (new NotExists($this, $criteria))($boolean);
		}

		return $this->where('NOT EXISTS (' . $criteria . ')', $boolean, $options);
	}
	
	public function whereHas(string $relation, \Closure $callback, ?string $boolean = 'AND') : self
	{
		return (
			new Exists($this, static function(self $query) use($relation, $callback) {
				$query->setParams(['tables' => [$relation], 'fields' => 1]);
				$callback($query);
			})
		)($boolean);
	}
	
	public function orWhereHas(string $relation, \Closure $callback) : self
	{
		return $this->whereHas($relation, $callback, 'OR');
	}
	
	public function whereDoesntHave(string $relation, \Closure $callback, string $boolean = 'AND') : self
	{
		return (
			new NotExists($this, static function(self $query) use($relation, $callback) {
				$query->setParams(['tables' => [$relation], 'fields' => 1]);
				$callback($query);
			})
		)($boolean);
	}
	
	public function orWhereDoesntHave(string $relation, \Closure $callback) : self
	{
		return $this->whereDoesntHave($relation, $callback, 'OR');
	}
	
	public function subWhere(string $relation, string $key, \Closure $callback, ?string $boolean = 'AND', ?string $operator = '=') : self
	{
		return (new SubWhere($this, static function(self $query) use($relation, $callback) {
			$query->setParams(['tables' => [$relation]]);
			$callback($query);
		}))($key, $operator, $boolean);
	}
	
	public function whereIn(string $key, array $values): self
	{
		return $this->where("$key IN (" . 
			implode(', ', array_fill(0, count($values), '?')) . ")", $values);
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

	public function orderBy(string $order_by) : self
	{
		$this->params['order_by'] = $order_by;
		return $this;
	}

	public function groupBy(string $group_by) : self
	{
		$this->params['group_by'] = $group_by;
		return $this;
	}

	public function distinct(bool $distinct = true) : self
	{
		$this->params['distinct'] = $distinct;
		return $this;
	}

	public function selectRaw(string $raw) : self
	{
		$this->set('fields', $raw);
		return $this;
	}

	public function from(string $tables) : self
	{
		$this->set('tables', explode(',', $tables));
		return $this;
	}

	public function whereRaw(string $condition) : self
	{
		$this->set('where', $condition);
		return $this;
	}

	public function orderByRaw(string $order) : self
	{
		$this->set('order_by', $order);
		return $this;
	}

	public function marker(?string $value = ':') : self
	{
		$this->set('marker', $value);
		return $this;
	}

	public function get(string $select = '*') : \Clicalmani\Foundation\Collection\CollectionInterface
	{
		$stringify = fn(mixed $fields, string $default) => match (gettype($fields)) {
			'string' => $fields,
			'array'  => implode(', ', $fields),
			default  => $default,
		};

		if (!isset($this->params['fields'])) {
			$this->params['fields'] = $select;
		} elseif ($select === '*') {
			$select = $stringify($this->params['fields'], $select);
		} elseif ($this->params['fields'] !== '*') {
			$select = $select . ', ' . $stringify($this->params['fields'], '');
		}
		
		$this->params['fields'] = $select;
		
		if ( $this->union_query instanceof self ) {
			/** @var \Clicalmani\Database\Union */
			$builder = $this->builder;
			$builder->setFields($select);
		}
		
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

			if ( is_string($table) ) $join = ['table' => $table];

			if ( is_callable($callback) ) $callback($clause);
			elseif ( is_callable($table) ) $table($clause);
			
			if (!empty($clause->on)) $join['criteria'] = $clause->on;
			if (!empty($clause->table)) $join['table'] = $clause->table;
			if (isset($clause->type)) $join['type'] = $clause->type;
			if (isset($clause->sub_query)) $join['sub_query'] = $clause->sub_query;
			if (isset($clause->alias)) $join['alias'] = $clause->alias;
			if (!empty($clause->condition)) $join['condition'] = $clause->condition;
			if ($clause->bindings) $this->options = array_merge($this->options, $clause->bindings);
			
			if (isset($join)) $this->params['join'][] = $join;
		}

		return $this;
	}

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string|\Closure|null $foreignKey [Optional] Foreign key
	 * @param ?string $localKey [Optional] Parent key
	 * @return static
	 */
	private function __join(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null, string $type = 'LEFT', ?bool $is_crossed = false, ?string $operator = '=') : static
	{
		if ( ! isset($foreignKey) ) $foreignKey = strtolower($table).'_id';
		if ( $foreignKey instanceof \Closure) {
			return $this->join($table, function(JoinClauseInterface $join) use($type, $foreignKey) {
				$join->type($type);
				$foreignKey($join);
			});
		}
		
		return $this->join($table, function(JoinClauseInterface $join) use ($foreignKey, $is_crossed, $localKey, $type, $operator) {
			$join->type($type);
			if ($is_crossed) $join->on('');
			else if ($foreignKey != $localKey) $join->on($foreignKey . $operator . $localKey);
			else $join->using($foreignKey);
		});
	}

	public function joinLeft(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self
	{
		return $this->__join($table, $foreignKey, $localKey);
	}

	public function joinRight(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self
	{
		return $this->__join($table, $foreignKey, $localKey, 'RIGHT');
	}

	public function joinInner(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self
	{
		return $this->__join($table, $foreignKey, $localKey, 'INNER');
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
		return $result->result()->first();
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
		return $result->result()->first()?->$field ?? null;
	}

	public function count(string $field = '*') : int
	{
		$this->params['fields'] = "COUNT($field)";
		$result = $this->exec();
		return (int) $result->result()->first()?->{"COUNT($field)"};
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

	public function union(self $query, bool $all = false) : self
	{
		$this->union_query = $query;
		$this->params['query'] = static::UNION;
		$this->builder = new Union($this->params, $this->options, $this->union_query->params, $this->union_query->options, $all);
		return $this;
	}

	/**
	 * Eager load relationships
	 * 
	 * @param string ...$relations The relationships to eager load
	 * @return self
	 */
	public function with(string ...$relations) : self
	{
		$this->with = array_merge($this->with, $relations);
		return $this;
	}

	/**
	 * Returns the query parameters
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * Returns the query options
	 * 
	 * @return ?array
	 */
	public function getOptions(): ?array
	{
		return $this->options;
	}
}
