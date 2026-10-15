<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Collection\Collection;
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
use Clicalmani\Core\Collection\CollectionInterface;
use Clicalmani\Core\Collection\Map;
use Clicalmani\Database\Factory\Models\Table;
use Clicalmani\Core\Support\Facades\Str;

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
	 * Builder
	 * 
	 * @var \Clicalmani\Database\DBQueryBuilder
	 */
	private $builder;

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
	public function __construct(
		private ?int $query = null, 
		?array $params = [], 
		private ?array $options = [],
		private ?Contracts\QueryExecutorInterface $executor = null
	)
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

	public function exec(bool $runDry = false) : \Clicalmani\Database\DBQueryBuilder
	{ 
		$this->query = isset($this->params['query'])? $this->params['query']: $this->query;

		if ( isset($this->params['connection']) ) {
			self::connection($this->params['connection']);
		}

		$type = QueryType::from((int) $this->query);

		if ($type === QueryType::DROP_TABLE_IF_EXISTS) {
			$this->params['exists'] = true;
		}

		$builderClass  = $type->builderClass();
		$this->builder = new $builderClass($this->params, $this->options, $this->executor(), $type);
		
		if (!is_subclass_of($this->builder, Builders\AbstractInsertStatement::class)) $this->builder->query($runDry);
		
		return $this->builder;
	}

	public function delete() : bool
	{
		$this->query = static::DELETE;
		return $this->exec()->status() === 'success';
	}

	public function truncate() : bool
	{
		$this->setSingleTable();

		$this->query = static::TRUNCATE;

		return $this->exec()->status() === 'success';
	}

	public function update(?array $options = []) : int
	{
		$this->set('query', DBQuery::UPDATE);

		$fields = array_keys( $options );
		$values = array_values( $options );
		
		$this->params['fields'] = $fields;
		$this->params['values'] = $values;
		
		if (!isset($this->params['marker'])) {
			$this->params['marker'] = ':';
		}

		return $this->exec()->affectedRows();
	}

	public function increment(string $field, int $value = 1, ?array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $this->raw($field . ' + ' . $value)]) );
	}

	public function decrement(string $field, int $value = 1, array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $this->raw($field . ' - ' . $value)]) );
	}

	public function insert(array $options = []) : Builders\AbstractInsertStatement
	{
		if ( array_filter($options, fn($entry) => ! is_array($entry)) ) {
			$options = [$options];
		}
		
		$this->setSingleTable();

		$this->params['values'] = [];

		foreach ($options as $option) {
			$fields = array_keys( $option );
			$values = array_values( $option );
			
			$this->params['fields']   = $fields;
			$this->params['values'][] = $values;
		}

		$this->set('query', self::INSERT);
		
		return $this->exec();
	}

	public function replace(array $options = []) : Builders\AbstractInsertStatement
	{
		$this->insert($options);
		$this->set('query', self::REPLACE); 
		return $this->exec();
	}

	public function where( ...$args ): self
	{
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
		$relation = Table::from($relation)->name();
		$table = Table::from($this->params['tables'][0] ?? '')->name();
		$foreignKey = Str::singularize($table) . '_id';

		return (
			new NotExists($this, function(self $query) use($relation, $table, $foreignKey, $callback) {
				$query->setParams(['tables' => [$relation], 'fields' => 1]);
				$query->where("{$relation}.{$foreignKey} = {$table}.id");
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

	public function whereNotIn(string $key, array $values, string $boolean = 'AND', bool $not = true) : self
	{
		if (empty($values)) {
			return $this->where('1 = 1', $boolean, []);
		}

		$placeholders = implode(', ', array_fill(0, count($values), '?'));

		return $this->where(
			"$key " . ($not ? 'NOT IN' : 'IN') . " ($placeholders)",
			$boolean,
			array_values($values)
		);
	}

	public function orWhereNotIn(string $key, array $values) : self
	{
		return $this->whereNotIn($key, $values, 'OR');
	}
	
	public function whereIn(string $key, array $values, string $boolean = 'AND') : self
	{
		return $this->whereNotIn($key, $values, $boolean, false);
	}

	public function orWhereIn(string $key, array $values) : self
	{
		return $this->whereIn($key, $values, 'OR');
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

	public function orderBy(string|array $column, string|array $order = 'ASC') : self
	{
		$this->params['order_by'] = (string) \Clicalmani\Database\Concerns\OrderBy::from($column, $order);
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

	public function get(string $select = '*') : Collection
	{
		try {
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
			
			$result = $this->exec();
			$collection = new Collection;
			
			foreach ($result as $row) {
				$collection->add($row);
			}

			return $collection;
		} catch (\PDOException $e) {
			throw $e;
		}
	}

	public function all() : Collection
	{
		$this->params['where'] = true;
		$this->options = [];
		return $this->exec()->result();
	}

	public function limit(int $count = 1) : static
	{
		$this->params['calc'] = true;
		$this->params['limit'] = $count;
		return $this;
	}

	public function skip(int $offset = 0) : static
	{
		$this->params['offset'] = $offset;
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
			if ($is_crossed) return;
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
		if ($this->inTransaction() || !config('database.lock_enabled', true)) return false;

		if ( ! in_array($type, ['READ', 'READ LOCAL', 'WRITE']) ) $type = 'WRITE';

		$this->setSingleTable();
		$this->query = static::LOCK_TABLE;
		$this->params['lock_type'] = $type;
		if ( $disable_keys ) $this->params['disable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	public function unlock(?bool $enable_keys = false) : bool
	{
		if ($this->inTransaction() || !config('database.lock_enabled', true)) return false;

		$this->setSingleTable();
		$this->query = static::UNLOCK_TABLE;
		if ( $enable_keys ) $this->params['enable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	public function getBuilderResult() : Collection
	{
		return $this->builder->result();
	}

	public function getBuilder()
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
		return $result->result()->first()?->$field;
	}

	public function firstOrFail() : mixed
	{
		$result = $this->first();
		if ( !isset($result) ) {
			throw new \Exception('No record found');
		}

		return $result;
	}

	public function value(string $field, mixed $default = null) : mixed
	{
		$this->params['fields'] = $field;
		$this->params['limit'] = 1;

		$result = $this->exec()->result()->first();

		if (!isset($result)) {
			return $default;
		}

		$result = (array) $result;

		return $result[$field] ?? $default;
	}

	public function count(string $field = '*', bool $distinct = false, int $default = 0) : int
	{
		$expression = $distinct ? "DISTINCT $field" : $field;

		return (int) ($this->aggregate('COUNT', $expression) ?? $default);
	}

	public function sum(string $field, mixed $default = null) : mixed
	{
		$value = $this->aggregate('SUM', $field);

		return NULL === $value ? $default : $value + 0;
	}

	public function max(string $field, mixed $default = null) : mixed
	{
		$value = $this->aggregate('MAX', $field);

		return NULL === $value ? $default : $value;
	}

	public function min(string $field, mixed $default = null) : mixed
	{
		$value = $this->aggregate('MIN', $field);

		return NULL === $value ? $default : $value;
	}

	public function avg(string $field, mixed $default = null) : mixed
	{
		$value = $this->aggregate('AVG', $field);
		
		return NULL === $value ? $default : $value;
	}

	public function find(int|string $id, ?string $column = 'id') : mixed
	{
		$this->params['where'] = "$column = :id";
		$this->options = ['id' => $id];
		return $this->exec()->result()->first();
	}

	public function chunk(int $size, callable $callback, ?string $column = null) : bool
	{
		if ($size < 1) {
			throw new \InvalidArgumentException('Chunk size must be greater than 0.');
		}

		// Preserve the original state so we can restore it after chunking.
		$originalOffset = $this->params['offset'] ?? null;
		$originalLimit  = $this->params['limit']  ?? null;
		$originalOrder  = $this->params['order_by'] ?? null;

		// Use a stable ordering to avoid duplicates/skips across pages.
		if (isset($column) && !isset($originalOrder)) {
			$this->params['order_by'] = $column;
		} elseif (!isset($originalOrder)) {
			// Fallback: order by the first selected field if available, otherwise by 1.
			$this->params['order_by'] = '1';
		}

		$page = 1;

		try {
			do {
				$this->params['offset'] = ($page - 1) * $size;
				$this->params['limit']  = $size;

				$result = $this->exec()->result();

				if ($result->count() === 0) {
					break;
				}

				if ($callback($result, $page) === false) {
					return false;
				}

				$page++;
			} while ($result->count() === $size);
		} finally {
			// Restore the original state to keep the query reusable.
			$this->params['offset']   = $originalOffset;
			$this->params['limit']    = $originalLimit;
			$this->params['order_by'] = $originalOrder;
		}

		return true;
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

	public function paginate(int $page = 1, int $size = 15, ?string $column = 'id', bool $countTotal = true) : \Clicalmani\Core\Collection\Paginator
	{
		if ($page < 1) {
			throw new \InvalidArgumentException('Page number must be greater than 0.');
		}

		if ($size < 1) {
			throw new \InvalidArgumentException('Page size must be greater than 0.');
		}

		// Preserve original state so the query remains reusable.
		$originalOffset = $this->params['offset'] ?? null;
		$originalLimit  = $this->params['limit']  ?? null;
		$originalOrder  = $this->params['order_by'] ?? null;

		// Ensure a stable ordering for deterministic pagination.
		if (!isset($originalOrder) && isset($column)) {
			$this->params['order_by'] = "$column ASC";
		} elseif (!isset($originalOrder)) {
			$this->params['order_by'] = '1';
		}

		$total = null;

		try {
			// Compute total first (before applying offset/limit).
			if ($countTotal) {
				$total = $this->countAll();
			}

			$this->params['offset'] = ($page - 1) * $size;
			$this->params['limit']  = $size;

			$items = $this->exec()->result();
		} finally {
			// Restore original state.
			$this->restoreParam('offset',   $originalOffset);
			$this->restoreParam('limit',    $originalLimit);
			$this->restoreParam('order_by', $originalOrder);
		}

		return new \Clicalmani\Core\Collection\Paginator(
			items: $items,
			total: $total,
			perPage: $size,
			currentPage: $page,
		);
	}

	public function simplePaginate(int $page = 1, int $size = 15, ?string $column = 'id') : \Clicalmani\Core\Collection\Paginator
	{
		if ($page < 1) {
			throw new \InvalidArgumentException('Page number must be greater than 0.');
		}

		if ($size < 1) {
			throw new \InvalidArgumentException('Page size must be greater than 0.');
		}

		// Preserve original state so the query remains reusable.
		$originalOffset = $this->params['offset'] ?? null;
		$originalLimit  = $this->params['limit']  ?? null;
		$originalOrder  = $this->params['order_by'] ?? null;
		$originalCalc   = $this->params['calc']   ?? null;

		// Ensure a stable ordering for deterministic pagination.
		if (!isset($originalOrder) && isset($column)) {
			$this->params['order_by'] = "$column ASC";
		} elseif (!isset($originalOrder)) {
			$this->params['order_by'] = '1';
		}

		try {
			// Fetch one extra row to detect whether a next page exists,
			// without paying for a COUNT(*) query.
			$this->params['offset'] = ($page - 1) * $size;
			$this->params['limit']  = $size + 1;
			$this->params['calc']   = false;

			$items = $this->exec()->result();

			$hasMore = $items->count() > $size;

			// Drop the extra row before returning.
			if ($hasMore) {
				$items = $items->slice(0, $size);
			}
		} finally {
			// Restore original state.
			$this->restoreParam('offset',   $originalOffset);
			$this->restoreParam('limit',    $originalLimit);
			$this->restoreParam('order_by', $originalOrder);
			$this->restoreParam('calc',     $originalCalc);
		}

		return new \Clicalmani\Core\Collection\Paginator(
			items: $items,
			total: null,
			perPage: $size,
			currentPage: $page
		);
	}

	public function lazy(int $chunk = 1000, ?string $column = 'id') : \Generator
	{
		if ($chunk < 1) {
			throw new \InvalidArgumentException('Chunk size must be greater than 0.');
		}

		// Ensure a stable ordering to avoid duplicates/skips across pages.
		$orderBy = $this->params['order_by'] ?? null;
		if (!isset($orderBy) && isset($column)) {
			$this->params['order_by'] = "$column ASC";
		} elseif (!isset($orderBy)) {
			$this->params['order_by'] = '1';
		}

		$offset = 0;

		do {
			// Clone to avoid mutating the original query state between iterations.
			$query = clone $this;
			$query->params['offset'] = $offset;
			$query->params['limit']  = $chunk;

			$result = $query->exec()->result();

			foreach ($result as $row) {
				yield $row;
			}

			$offset += $chunk;
		} while ($result->count() === $chunk);
	}

	public function pluck(string $column, ?string $key = null): CollectionInterface
	{
		if ($key !== null) {
			$this->params['fields'] = "{$key}, {$column}";
		} else {
			$this->params['fields'] = $column;
		}

		// 2. Exécuter et récupérer les lignes
		$result = $this->exec()->result();
		$collection = new Collection;

		// 4. Construire la collection
		foreach ($result as $row) {
			$row = (array) $row;

			$value = $row[$column] ?? null;

			if ($key !== null) {
				$collection->put($row[$key] ?? null, $value);
			} else {
				$collection->push($value);
			}
		}

		return $collection;
	}

	public function lazyBy(string $column, int $chunk = 1000) : \Generator
	{
		$offset = 0;

		do {
			$query = clone $this;
			$query->params['order_by'] = "$column ASC";
			$query->params['offset'] = $offset;
			$query->params['limit'] = $chunk;

			$result = $query->exec()->result();

			foreach ($result as $row) {
				yield $row;
			}

			$offset += $chunk;
		} while ($result->count() === $chunk);
	}

	public function lazyByDesc(string $column, int $chunk = 1000) : \Generator
	{
		$offset = 0;

		do {
			$query = clone $this;
			$query->params['order_by'] = "$column DESC";
			$query->params['offset'] = $offset;
			$query->params['limit'] = $chunk;

			$result = $query->exec()->result();

			foreach ($result as $row) {
				yield $row;
			}

			$offset += $chunk;
		} while ($result->count() === $chunk);
	}

	public function exists() : bool
	{
		return $this->firstExists();
	}

	public function doesntExist() : bool
	{
		return !$this->firstExists();
	}

	public function when(mixed $condition, callable $callback, ?callable $default = null) : static
	{
		if ($condition) {
			$result = $callback($this);

			return $result instanceof self ? $result : $this;
		}

		if (isset($default)) {
			$result = $default($this);

			return $result instanceof self ? $result : $this;
		}

		return $this;
	}

	public function unless(mixed $condition, callable $callback, ?callable $default = null) : static
	{
		if (!$condition) {
			$result = $callback($this);

			return $result instanceof self ? $result : $this;
		}

		if (isset($default)) {
			$result = $default($this);

			return $result instanceof self ? $result : $this;
		}

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

	/**
	 * Count all rows matching the current query, ignoring offset/limit.
	 *
	 * @return int
	 */
	private function countAll() : int
	{
		$query = clone $this;
		$query->params['fields'] = 'COUNT(*) AS aggregate';
		unset($query->params['offset'], $query->params['limit'], $query->params['order_by']);

		$row = $query->exec()->result()->first();

		return (int) (((array) $row)['aggregate'] ?? 0);
	}

	/**
	 * Restore a parameter to its original value, or unset it if it was not set.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	private function restoreParam(string $key, mixed $value) : void
	{
		if (NULL === $value) {
			unset($this->params[$key]);
		} else {
			$this->params[$key] = $value;
		}
	}

	/**
	 * Execute an aggregate function (SUM, AVG, MIN, MAX, COUNT, ...) on the
	 * given column and return the raw scalar value, or null if no row matches.
	 *
	 * The query is cloned so the original instance remains reusable. The
	 * aggregate is aliased to 'aggregate' to provide a stable key regardless
	 * of how the database normalizes the expression.
	 *
	 * @param string $function Aggregate function name (e.g. 'SUM', 'AVG')
	 * @param string $field Column to aggregate
	 * @return mixed Raw scalar value, or null
	 */
	private function aggregate(string $function, string $field) : mixed
	{
		$function = strtoupper($function);

		if (!in_array($function, ['SUM', 'AVG', 'MIN', 'MAX', 'COUNT'], true)) {
			throw new \InvalidArgumentException("Unsupported aggregate function: $function");
		}

		$query = clone $this;
		$query->params['query'] = self::SELECT;
		$query->params['fields'] = "$function($field) AS aggregate";

		// Aggregate queries make no sense with ordering or pagination.
		unset($query->params['order_by'], $query->params['offset'], $query->params['limit']);

		$row = $query->exec()->result()->first();

		if (NULL === $row) {
			return null;
		}

		return ((array) $row)['aggregate'] ?? null;
	}

	/**
	 * Internal helper: check whether the current query yields at least one row.
	 *
	 * Clones the query, replaces the SELECT fields with a constant 1, removes
	 * ORDER BY (which is pointless for an existence test) and caps the result
	 * at one row. The original query is left untouched.
	 *
	 * @return bool
	 */
	private function firstExists() : bool
	{
		$query = clone $this;
		$query->params['fields'] = '1';
		$query->params['limit']  = 1;

		// ORDER BY is irrelevant when we only need to know if a row exists.
		unset($query->params['order_by'], $query->params['offset']);

		return $query->exec()->result()->count() > 0;
	}

	private function setSingleTable(): void
	{
		if (!isset($this->params['tables'][0])) {
			throw new \RuntimeException('No table specified for the query.');
		}

		$table = Table::from($this->params['tables'][0]);
		unset($this->params['tables']);
		$this->params['table'] = $table->name();
	}
}
