<?php
namespace Clicalmani\Database\Interfaces;

use Clicalmani\Foundation\Collection\Map;

interface QueryInterface extends DBInterface
{
    /**
	 * Sets query parameter
	 * 
	 * @param string $param parameter name
	 * @param mixed $value parameter value
	 * @return self
	 */
	public function set(string $param, mixed $value) : self;

	/**
	 * Unset a query parameter
	 * 
	 * @param string $param Parameter name
	 * @return self
	 */
	public function unset(string $param) : self;

	/**
	 * Binds query parameters.
	 * 
	 * @param array $options 
	 * @return void
	 */
	public function setOptions(array $options) : void;

	/**
	 * Gets query the specified parameter value
	 * 
	 * @param string $param Parameter name
	 * @return mixed Parameter value, or null on failure.
	 */
	public function getParam(string $param, mixed $default = null) : mixed;

	/**
	 * Execute a SQL query command
	 * 
	 * @return \Clicalmani\Database\DBQueryBuilder
	 */
	public function exec() : \Clicalmani\Database\DBQueryBuilder;

	/**
	 * Performs a delete request.
	 * 
	 * @return self
	 */
	public function delete() : self;

	/**
	 * Perform a truncate request.
	 * 
	 * @return bool true on success, false on failure
	 */
	public function truncate() : bool;

	/**
	 * Perform an update request.
	 * 
	 * @param array $option [optional] New attribute values
	 * @return bool true on success, false on failure
	 */
	public function update(array $options = []) : bool;

	/**
	 * Increment a field value by a specified value.
	 * 
	 * @param string $field Field name
	 * @param int $value [optional] Increment value. Default is 1
	 * @param ?array $fields [optional] Additional fields to be updated
	 * @return bool true on success, false on failure
	 */
	public function increment(string $field, int $value = 1, ?array $fields = []) : bool;

	/**
	 * Decrement a field value by a specified value.
	 * 
	 * @param string $field Field name
	 * @param int $value [optional] Decrement value. Default is 1
	 * @param array $fields [optional] Additional fields to be updated
	 * @return bool true on success, false on failure
	 */
	public function decrement(string $field, int $value = 1, array $fields = []) : bool;

	/**
	 * Insert new record to the selected database table. 
	 * 
	 * @param array $options [optional] New values to be inserted.
	 * @param bool $replace Run REPLACE query if record exists
	 * @return bool true on success, false on failure
	 */
	public function insert(array $options = [], bool $replace = false) : bool;

	/**
	 * Insert new record to the selected table or fail.
	 * 
	 * @return bool true on success, false on failure
	 */
	public function insertOrFail(array $options = []) : bool;

	/**
	 * Insert new record to the selected table and return the last inserted id.
	 * 
	 * @param array $options [optional] New values to be inserted.
	 * @return int
	 */
	public function insertGetId(array $options = []) : int;

	/**
	 * Insert new record or update the existing one
	 * 
	 * @deprecated
	 * @param array $options
	 * @return void
	 */
	public function insertOrUpdate(array $options) : void;

	/**
	 * Specify the query where condition. 
	 * 
	 * @param array ...$args Takes one, two or three parameters
	 * @return self
	 */
	public function where( ...$args ) : self;

	/**
	 * Specify the query having condition
	 * 
	 * @param string $criteria a SQL query having condition
	 * @return self
	 */
	public function having(string $criteria) : self;

	/**
	 * Orders the query result set.
	 * 
	 * @param string $order_by a SQL query order by statement
	 * @return self
	 */
	public function orderBy(string $order_by) : self;

	/**
	 * Group the query result set by a specified parameter
	 * 
	 * @param string $group_by a SQL query group by statement
	 * @return self
	 */
	public function groupBy(string $group_by) : self;

	/**
	 * Wheter to return a distinct result set.
	 * 
	 * @param bool $distinct [optional] default true
	 * @return self
	 */
	public function distinct(bool $distinct = true) : self;

	/**
	 * SQL from statement when deleting from joined tables.
	 * 
	 * @param string $fields 
	 * @return self
	 */
	public function from(string $fields) : self;

	/**
	 * Gets a database query result set. An optional comma separated list of request fields can be specified as 
	 * the unique argument.
	 * 
	 * @param string $fields a list of request fields separated by comma.
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function get(string $fields = '*') : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Fetch all rows in a query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function all() : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Limit the number of rows to be returned in a query result set.
	 * 
	 * @param int $offset [Optional] the starting index to fetch from. Default is 0
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return self
	 */
	public function limit(int $offset = 0, int $limit = 1) : self;

	/**
	 * Limit the number of rows to be returned in a query result set.
	 * 
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return self
	 */
	public function top(int $limit = 1) : self;

	/**
	 * Joins a database table to the current selected table. 
	 * 
	 * @param mixed $table Table name
	 * @param ?callable $callback [optional] A callback function
	 * @return self
	 */
	public function join(mixed $table, ?callable $callback = null) : self;

	/**
	 * Left join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return self
	 */
	public function joinLeft(string $table, ?string $foreign_key = null, ?string $original_key = null) : self;

	/**
	 * Right join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return self
	 */
	public function joinRight(string $table, ?string $foreign_key = null, ?string $original_key = null) : self;

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param ?string $foreign_key [Optional] Foreign key
	 * @param ?string $original_key [Optional] Original key
	 * @return self
	 */
	public function joinInner(string $table, ?string $foreign_key = null, ?string $original_key = null) : self;

	/**
	 * Cross join
	 * 
	 * @param string $table Table name
	 * @return self
	 */
	public function joinCross(string $table) : self;

	/**
	 * Lock table in writing mode
	 * 
	 * @param ?string $type [optional] Lock type. Default is 'WRITE'
	 * @param ?bool $disable_keys [optional] Disable foreign keys check. Default is false
	 * @return bool
	 */
	public function lock(?string $type = 'WRITE', ?bool $disable_keys = false) : bool;

	/**
	 * Unlock a locked table in writing mode
	 * 
	 * @param ?bool $enable_keys [optional] Enable foreign keys check. Default is false
	 * @return bool
	 */
	public function unlock(?bool $enable_keys = false) : bool;

	/**
	 * Returns the query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function getBuilderResult() : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Returns the query builder object.
	 * 
	 * @return ?\Clicalmani\Database\Interfaces\BuilderInterface
	 */
	public function getBuilder() : ?\Clicalmani\Database\Interfaces\BuilderInterface;

	/**
	 * Returns the first row in a query result set.
	 * 
	 * @return mixed
	 */
	public function first() : mixed;

	/**
	 * Returns the first value of a field in a query result set.
	 * 
	 * @param string $field The field to be returned
	 * @return mixed
	 */
	public function firstValue(string $field) : mixed;

	/**
	 * Returns the first row in a query result set or fail.
	 * 
	 * @return mixed
	 */
	public function firstOrFail() : mixed;

	public function value(string $field) : mixed;

	/**
	 * Count the number of rows in a query result set.
	 * 
	 * @param string $field [optional] The field to be counted. Default is '*'
	 * @return int
	 */
	public function count(string $field = '*') : int;

	/**
	 * Sum the values of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function sum(string $field) : int;

	/**
	 * Get the maximum value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function max(string $field) : int;

	/**
	 * Get the minimum value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function min(string $field) : int;

	/**
	 * Get the average value of a field in a query result set.
	 * 
	 * @param string $field The field to be summed
	 * @return int
	 */
	public function avg(string $field) : int;

	/**
	 * Find a record by its id
	 * 
	 * @param int $id Record id
	 * @param string $column [optional] Column name. Default is 'id'
	 * @return mixed
	 */
	public function find(int $id, string $column = 'id') : mixed;

	/**
	 * Chunk the results of the query.
	 * 
	 * @param int $size Chunk size
	 * @param collable $callback Callback function
	 * @return void
	 */
	public function chunk(int $size, callable $callback) : void;

	/**
	 * Chunk the results of the query by id.
	 * 
	 * @param int $size Chunk size
	 * @param collable $callback Callback function
	 * @param ?string $column [optional] Column name. Default is 'id'
	 * @return void
	 */
	public function chunkById(int $size, callable $callback, ?string $column = 'id') : void;

	/**
	 * Paginate the query result set.
	 * 
	 * @param int $page Page number
	 * @param int $size Page size
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function paginate(int $page, int $size) : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Paginate the query result set without FOUND_ROWS.
	 * 
	 * @param int $page Page number
	 * @param int $size Page size
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function simplePaginate(int $page, int $size) : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Lazy load the query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\CollectionInterface
	 */
	public function lazy() : \Clicalmani\Foundation\Collection\CollectionInterface;

	/**
	 * Get the query result set as a map.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
	public function pluck(string $field, ?string $key = null) : Map;

	/**
	 * Lazy load the query result set by a specified field.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
	public function lazyBy(string $field, ?string $key = null) : Map;

	/**
	 * Lazy load the query result set by a specified field in descending order.
	 * 
	 * @param string $field The field to be used as the map value
	 * @param ?string $key [optional] The field to be used as the map key. Default is null
	 * @return \Clicalmani\Foundation\Collection\Collection\Map
	 */
	public function lazyByDesc(string $field, ?string $key = null) : Map;

	/**
	 * Check if a record exists in the query result set.
	 * 
	 * @return bool
	 */
	public function exists() : bool;

	/**
	 * Check if a record does not exist in the query result set.
	 * 
	 * @return bool
	 */
	public function doesntExist() : bool;

	/**
	 * Check if a record exists in the query result set.
	 * 
	 * @param bool $condition
	 * @param callable $callback
	 * @return self
	 */
	public function when(bool $condition, callable $callback) : self;

	/**
	 * Check if a record does not exist in the query result set.
	 * 
	 * @param bool $condition
	 * @param callable $callback
	 * @return self
	 */
	public function unless(bool $condition, callable $callback) : self;
}