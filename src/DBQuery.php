<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Database\Factory\Create;
use Clicalmani\Database\Factory\Drop;
use Clicalmani\Database\Factory\Alter;
use Clicalmani\Foundation\Collection\Map;

/**
 * Database query
 * 
 * Generate the SQL statement to be executed for the requested query.
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class DBQuery extends DB
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
	
	/**
	 * Sets query parameter
	 * 
	 * @param string $param parameter name
	 * @param mixed $value parameter value
	 * @return static
	 */
	public function set(string $param, mixed $value) : static
	{ 
		if ($param == 'type') {
			$this->query = $value;
		} else $this->params[$param] = $value;

		return $this;
	}

	/**
	 * Unset a query parameter
	 * 
	 * @param string $param Parameter name
	 * @return static
	 */
	public function unset(string $param) : static
	{
		unset($this->params[$param]);
		return $this;
	}

	/**
	 * Binds query parameters.
	 * 
	 * @param array $options 
	 * @return void
	 */
	public function setOptions(array $options) : void
	{
		$this->options = $options;
	}

	/**
	 * Gets query the specified parameter value
	 * 
	 * @param string $param Parameter name
	 * @return mixed Parameter value, or null on failure.
	 */
	public function getParam(string $param, mixed $default = null)
	{
		if (isset($this->params[$param])) {
			return $this->params[$param] ?? $default;
		}

		return $default;
	}

	/**
	 * Execute a SQL query command
	 * 
	 * @return \Clicalmani\Database\DBQueryBuilder
	 */
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

	/**
	 * Performs a delete request.
	 * 
	 * @return static
	 */
	public function delete() : static
	{
		$this->query = static::DELETE;
		return $this;
	}

	/**
	 * Perform a truncate request.
	 * 
	 * @return bool true on success, false on failure
	 */
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

	/**
	 * Perform an update request.
	 * 
	 * @param array $option [optional] New attribute values
	 * @return bool true on success, false on failure
	 */
	public function update(array $options = []) : bool
	{
		$this->set('query', DBQuery::UPDATE);

		$fields = array_keys( $options );
		$values = array_values( $options );
		
		$this->params['fields'] = $fields;
		$this->params['values'] = $values;
		
		return $this->exec()->status() === 'success';
	}

	/**
	 * Increment a field value by a specified value.
	 * 
	 * @param string $field Field name
	 * @param int $value [optional] Increment value. Default is 1
	 * @param ?array $fields [optional] Additional fields to be updated
	 * @return bool true on success, false on failure
	 */
	public function increment(string $field, int $value = 1, ?array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $field . ' + ' . $value]) );
	}

	/**
	 * Decrement a field value by a specified value.
	 * 
	 * @param string $field Field name
	 * @param int $value [optional] Decrement value. Default is 1
	 * @param ?array $fields [optional] Additional fields to be updated
	 * @return bool true on success, false on failure
	 */
	public function decrement(string $field, int $value = 1, ?array $fields = []) : bool
	{
		return $this->update( array_merge($fields, [$field => $field . ' - ' . $value]) );
	}

	/**
	 * Insert new record to the selected database table. 
	 * 
	 * @param array $options [optional] New values to be inserted.
	 * @param ?bool $replace Run REPLACE query if record exists
	 * @return bool true on success, false on failure
	 */
	public function insert(array $options = [], ?bool $replace = false) : bool
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

	/**
	 * Insert new record to the selected table or fail.
	 * 
	 * @return bool true on success, false on failure
	 */
	public function insertOrFail(array $options = []) : bool
	{
		try {
			return $this->insert($options);
		} catch (\PDOException $e) {
			return false;
		}
	}

	/**
	 * Insert new record to the selected table and return the last inserted id.
	 * 
	 * @param array $options [optional] New values to be inserted.
	 * @return int
	 */
	public function insertGetId(array $options = []) : int
	{
		$this->insert($options);
		return DB::insertId();
	}

	/**
	 * Insert new record or update the existing one
	 * 
	 * @deprecated
	 * @param array $options
	 * @return void
	 */
	public function insertOrUpdate(array $options) : void
	{
		if (false === $this->insertOrFail($options)) {

			// Reset table for update
			$this->params['tables'] = [$this->params['table']];

			foreach ($options as $option) $this->update($option);
		}
	}

	/**
	 * Specify the query where condition. 
	 * 
	 * @param array ...$args Takes one, two or three parameters
	 * @return static
	 */
	public function where( ...$args ) : static
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

	/**
	 * Specify the query having condition
	 * 
	 * @param string $criteria a SQL query having condition
	 * @return static
	 */
	public function having(string $criteria) : static
	{
		if ( !isset($this->params['having']) ) {
			$this->params['having'] = $criteria;
		} else {
			$this->params['having'] .= ' AND ' . $criteria;
		}
		
		return $this;
	}

	/**
	 * Orders the query result set.
	 * 
	 * @param string $order_by a SQL query order by statement
	 * @return static
	 */
	public function orderBy(string $order_by) : static
	{
		$this->params['order_by'] = $order_by;
		return $this;
	}

	/**
	 * Group the query result set by a specified parameter
	 * 
	 * @param string $group_by a SQL query group by statement
	 * @return static
	 */
	public function groupBy(string $group_by) : static
	{
		$this->params['group_by'] = $group_by;
		return $this;
	}

	/**
	 * Wheter to return a distinct result set.
	 * 
	 * @param bool $distinct [optional] default true
	 * @return static
	 */
	public function distinct($distinct = true) : static
	{
		$this->params['distinct'] = $distinct;
		return $this;
	}

	/**
	 * SQL from statement when deleting from joined tables.
	 * 
	 * @param string $fields 
	 * @return static
	 */
	public function from(string $fields) : static
	{
		$this->params['fields'] = $fields;
		return $this;
	}

	/**
	 * Gets a database query result set. An optional comma separated list of request fields can be specified as 
	 * the unique argument.
	 * 
	 * @param string $fields a list of request fields separated by comma.
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function get(string $fields = '*') : Collection
	{
		$this->params['fields'] = $fields;
		$result = $this->exec();
		$collection = new Collection;
		
		foreach ($result as $row) {
			$collection->add($row);
		}

		return $collection;
	}

	/**
	 * Fetch all rows in a query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function all() : Collection
	{
		$this->params['where'] = 'TRUE';
		return $this->exec()->result();
	}

	/**
	 * Limit the number of rows to be returned in a query result set.
	 * 
	 * @param int $offset [Optional] the starting index to fetch from. Default is 0
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return static
	 */
	public function limit(int $offset = 0, int $limit = 1) : static
	{
		$this->params['calc'] = true;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $limit;
		
		return $this;
	}

	/**
	 * Limit the number of rows to be returned in a query result set.
	 * 
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return static
	 */
	public function top(int $limit = 1) : static
	{
		$this->params['limit'] = $limit;
		return $this;
	}

	/**
	 * Joins a database table to the current selected table. 
	 * 
	 * @param mixed $table Table name
	 * @param ?callable $callback [optional] A callback function
	 * @return static
	 */
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

		return $this->join($table, function(JoinClause $join) use ($foreign_key, $is_crossed, $original_key, $type, $operator) {
			$join->type($type);
			if ($is_crossed) $join->on('');
			else if ($foreign_key != $original_key) $join->on($foreign_key . $operator . $original_key);
			else $join->using($foreign_key);
		});
	}

	/**
	 * Left join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return static
	 */
	public function joinLeft(string $table, ?string $foreign_key = null, ?string $original_key = null) : static
	{
		return $this->__join($table, $foreign_key, $original_key);
	}

	/**
	 * Right join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return static
	 */
	public function joinRight(string $table, ?string $foreign_key = null, ?string $original_key = null) : static
	{
		return $this->__join($table, $foreign_key, $original_key, 'RIGHT');
	}

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return static
	 */
	public function joinInner(string $table, ?string $foreign_key = null, ?string $original_key = null) : static
	{
		return $this->__join($table, $foreign_key, $original_key, 'INNER');
	}

	/**
	 * Cross join
	 * 
	 * @param string $table Table name
	 * @return static
	 */
	public function joinCross(string $table) : static
	{
		return $this->__join($table, '', '', 'CROSS', true);
	}

	/**
	 * Lock table in writing mode
	 * 
	 * @param ?string $type [optional] Lock type. Default is 'WRITE'
	 * @param ?bool $disable_keys [optional] Disable foreign keys check. Default is false
	 * @return bool
	 */
	public function lock(?string $type = 'WRITE', ?bool $disable_keys = false) : bool
	{
		if ( ! in_array($type, ['READ', 'READ LOCAL', 'WRITE']) ) $type = 'WRITE';

		$this->query = static::LOCK_TABLE;
		$this->params['lock_type'] = $type;
		if ( $disable_keys ) $this->params['disable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	/**
	 * Unlock a locked table in writing mode
	 * 
	 * @param ?bool $enable_keys [optional] Enable foreign keys check. Default is false
	 * @return bool
	 */
	public function unlock(?bool $enable_keys = false) : bool
	{
		$this->query = static::UNLOCK_TABLE;
		if ( $enable_keys ) $this->params['enable_keys'] = true;
		return $this->exec()->status() === 'success';
	}

	/**
	 * Returns the query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function getBuilderResult() : Collection
	{
		return $this->builder->result();
	}

	/**
	 * Returns the query builder object.
	 * 
	 * @return \Clicalmani\Database\DBQueryBuilder|null
	 */
	public function getBuilder() : DBQueryBuilder|null
	{
		return $this->builder;
	}

	/**
	 * Returns the first row in a query result set.
	 * 
	 * @return mixed
	 */
	public function first() : mixed
	{
		$this->params['limit'] = 1;
		$result = $this->exec();
		return (object) $result->result()->first();
	}

	/**
	 * Returns the first value of a field in a query result set.
	 * 
	 * @param string $field The field to be returned
	 * @return mixed
	 */
	public function firstValue(string $field) : mixed
	{
		$this->params['fields'] = $field;
		$this->params['limit'] = 1;
		$result = $this->exec();
		return $result->result()->first()[$field];
	}

	/**
	 * Returns the first row in a query result set or fail.
	 * 
	 * @return mixed
	 */
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

	/**
	 * Count the number of rows in a query result set.
	 * 
	 * @param ?string $field [optional] The field to be counted. Default is '*'
	 * @return int
	 */
	public function count(?string $field = '*') : int
	{
		$this->params['fields'] = "COUNT($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["COUNT($field)"];
	}

	/**
	 * Sum the values of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function sum(string $field) : int
	{
		$this->params['fields'] = "SUM($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["SUM($field)"];
	}

	/**
	 * Get the maximum value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function max(string $field) : int
	{
		$this->params['fields'] = "MAX($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["MAX($field)"];
	}

	/**
	 * Get the minimum value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function min(string $field) : int
	{
		$this->params['fields'] = "MIN($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["MIN($field)"];
	}

	/**
	 * Get the average value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function avg(string $field) : int
	{
		$this->params['fields'] = "AVG($field)";
		$result = $this->exec();
		return (int) $result->result()->first()["AVG($field)"];
	}

	/**
	 * Find a record by its id
	 * 
	 * @param int $id Record id
	 * @param ?string $column [optional] Column name. Default is 'id'
	 * @return mixed
	 */
	public function find(int $id, ?string $column = 'id') : mixed
	{
		$this->params['where'] = "$column = :id";
		$this->options = ['id' => $id];
		return $this->exec()->result()->first();
	}

	/**
	 * Chunk the results of the query.
	 * 
	 * @param int $size Chunk size
	 * @param collable $callback Callback function
	 * @return void
	 */
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

	/**
	 * Chunk the results of the query by id.
	 * 
	 * @param int $size Chunk size
	 * @param collable $callback Callback function
	 * @param ?string $column [optional] Column name. Default is 'id'
	 * @return void
	 */
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

	/**
	 * Paginate the query result set.
	 * 
	 * @param int $page Page number
	 * @param int $size Page size
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function paginate(int $page, int $size) : Collection
	{
		$offset = ($page - 1) * $size;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $size;
		return $this->exec()->result();
	}

	/**
	 * Paginate the query result set without FOUND_ROWS.
	 * 
	 * @param int $page Page number
	 * @param int $size Page size
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function simplePaginate(int $page, int $size) : Collection
	{
		$offset = ($page - 1) * $size;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $size;
		$this->params['calc'] = false;
		return $this->exec()->result();
	}

	/**
	 * Lazy load the query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function lazy() : Collection
	{
		return $this->exec()->result();
	}

	/**
	 * Get the query result set as a map.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
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

	/**
	 * Lazy load the query result set by a specified field.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
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

	/**
	 * Lazy load the query result set by a specified field in descending order.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
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

	/**
	 * Check if a record exists in the query result set.
	 * 
	 * @return bool
	 */
	public function exists() : bool
	{
		return $this->count() > 0;
	}

	/**
	 * Check if a record does not exist in the query result set.
	 * 
	 * @return bool
	 */
	public function doesntExist() : bool
	{
		return $this->count() === 0;
	}

	/**
	 * Check if a record exists in the query result set.
	 * 
	 * @param bool $condition
	 * @param callable $callback
	 * @return bool
	 */
	public function when(bool $condition, callable $callback) : static
	{
		if ($condition) $callback($this->query);
		return $this;
	}

	/**
	 * Check if a record does not exist in the query result set.
	 * 
	 * @param bool $condition
	 * @param callable $callback
	 * @return bool
	 */
	public function unless(bool $condition, callable $callback) : static
	{
		if (!$condition) $callback($this->query);
		return $this;
	}
}
