<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Collection\Map;

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
	 * Returns query options
	 * 
	 * @return ?array
	 */
	public function getOptions(): ?array;

	/**
	 * Gets query the specified parameter value
	 * 
	 * @param string $param Parameter name
	 * @return mixed Parameter value, or null on failure.
	 */
	public function getParam(string $param, mixed $default = null) : mixed;

	/**
	 * Returns the query parameters
	 * @return array
	 */
	public function getParams(): array;

	/**
	 * Set query parameters
	 * 
	 * @param array $newParams
	 */
	public function setParams(array $newParams): void;

	/**
	 * Execute a SQL query command
	 * 
	 * @return \Clicalmani\Database\DBQueryBuilder
	 */
	public function exec() : \Clicalmani\Database\DBQueryBuilder;

	/**
	 * Performs a delete request.
	 * 
	 * @return bool
	 */
	public function delete() : bool;

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
	 * @return int Affected rows
	 */
	public function update(?array $options = []) : int;

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
	 * @param bool $update Update if record exists
	 * @return Builders\AbstractInsertStatement
	 */
	public function insert(array $options = []) : Builders\AbstractInsertStatement;

	public function replace(array $options = []) : Builders\AbstractInsertStatement;

	/**
	 * Specify the query where condition. 
	 * 
	 * @param array ...$args Takes one, two or three parameters
	 * @return self
	 */
	public function where( ...$args ) : self;

	/**
	 * Add an OR condition to the query. The method accepts the same parameters as the where() method, but the condition will be added with an OR operator instead of AND.
	 * 
	 * @param mixed ...$args The arguments for the where condition, same as the where() method
	 * @return self
	 */
	public function orWhere(mixed ...$args): self;

	/**
	 * Add a WHERE EXISTS clause to the query
	 *
	 * @param Closure|string $criteria The subquery criteria
	 * @param array $options The query options
	 * @return QueryInterface
	 */
	public function whereExists(\Closure|string $criteria, ?array $options = []) : QueryInterface;

	/**
	 * Add a WHERE NOT EXISTS clause to the query
	 *
	 * @param Closure|string $criteria The subquery criteria
	 * @param array $options The query options
	 * @return QueryInterface
	 */
	public function whereNotExists(\Closure|string $criteria, ?array $options = []) : QueryInterface;

	/**
	 * Add a where clause to the query based on the existence of a related model.
	 * 
	 * The method accepts the name of the related model and a closure that defines the conditions for the related model.
	 * The closure receives an instance of the query builder for the related model, allowing you to specify additional where clauses or joins.
	 * The resulting SQL will include an EXISTS subquery that checks for the existence of related records matching the specified conditions.
	 * 
	 * @example
	 * $query->whereHas('comments', function($query) {
	 *     $query->where('status = ?', ['approved']);
	 * });
	 * 
	 * This example will generate a SQL query that selects records from the main table where there exists at least one related record in the 'comments' table with a status of 'approved'.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @param string $boolean [Optional] The boolean operator to use when combining this clause with others (default is 'AND')
	 * @return self
	 */
	public function whereHas(string $relation, \Closure $callback, string $boolean = 'AND') : self;

	/**
	 * Add an OR where clause to the query based on the existence of a related model.
	 * 
	 * This method functions similarly to whereHas, but it combines the clause with others using the OR operator instead of AND.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @return self
	 */
	public function orWhereHas(string $relation, \Closure $callback) : self;

	/**
	 * Add a where clause to the query based on the non-existence of a related model.
	 * 
	 * This method is the inverse of whereHas. It adds a clause that checks for the absence of related records matching the specified conditions.
	 * The resulting SQL will include a NOT EXISTS subquery that checks for the non-existence of related records matching the specified conditions.
	 * 
	 * @example
	 * $query->whereDoesntHave('comments', function($query) {
	 *     $query->where('status = ?', ['approved']);
	 * });
	 * 
	 * This example will generate a SQL query that selects records from the main table where there does not exist any related record in the 'comments' table with a status of 'approved'.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @param string $boolean [Optional] The boolean operator to use when combining this clause with others (default is 'AND')
	 * @return self
	 */
	public function whereDoesntHave(string $relation, \Closure $callback, string $boolean = 'AND') : self;

	/**
	 * Add an OR where clause to the query based on the non-existence of a related model.
	 * 
	 * This method functions similarly to whereDoesntHave, but it combines the clause with others using the OR operator instead of AND.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @return self
	 */
	public function orWhereDoesntHave(string $relation, \Closure $callback) : self;

	/**
	 * Add a "where NOT IN" clause to the query.
	 *
	 * Generates a condition of the form "key NOT IN (?, ?, ...)" with one
	 * placeholder per value. The values are passed as bindings, so they are
	 * safely escaped by the PDO driver.
	 *
	 *     $query->whereNotIn('status', ['archived', 'deleted']);
	 *     // ... WHERE status NOT IN ('archived', 'deleted')
	 *
	 * An empty array produces a condition that is always true ("1 = 1"),
	 * because "NOT IN ()" is invalid SQL. This matches the intuitive
	 * semantics: excluding nothing excludes nothing.
	 *
	 * @param string $key Column name (or expression) to test
	 * @param array $values Values to exclude
	 * @param string $boolean [Optional] Boolean operator to join with. Default 'AND'
	 * @param bool $not [Optional] Whether to negate. Default true
	 * @return self
	 */
	public function whereNotIn(string $key, array $values, string $boolean = 'AND', bool $not = true) : self;

	/**
	 * Add an "or where NOT IN" clause to the query.
	 *
	 * Shortcut for whereNotIn(..., 'OR').
	 *
	 * @param string $key Column name (or expression) to test
	 * @param array $values Values to exclude
	 * @return self
	 */
	public function orWhereNotIn(string $key, array $values) : self;

	/**
	 * Add a "where IN" clause to the query.
	 *
	 * An empty array produces a condition that is always false ("1 = 0"),
	 * because "IN ()" is invalid SQL. This matches the intuitive semantics:
	 * including nothing includes nothing.
	 *
	 * @param string $key Column name (or expression) to test
	 * @param array $values Values to include
	 * @param string $boolean [Optional] Boolean operator to join with. Default 'AND'
	 * @return self
	 */
	public function whereIn(string $key, array $values, string $boolean = 'AND') : self;

	/**
	 * Add an "or where IN" clause to the query.
	 *
	 * @param string $key Column name (or expression) to test
	 * @param array $values Values to include
	 * @return self
	 */
	public function orWhereIn(string $key, array $values) : self;

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
	 * @param string|array $column
	 * @param string|array $order ASC or DESC
	 * @return self
	 */
	public function orderBy(string|array $column, string|array $order = 'ASC') : self;

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
	 * Specify the query select statement.
	 * 
	 * @param string $raw a SQL query select statement
	 * @return self
	 */
	public function selectRaw(string $raw) : self;

	/**
	 * Gets a database query result set. An optional comma separated list of request fields can be specified as 
	 * the unique argument.
	 * 
	 * @param string $fields a list of request fields separated by comma.
	 * @return \Clicalmani\Core\Collection\CollectionInterface
	 */
	public function get(string $fields = '*') : \Clicalmani\Core\Collection\Collection;

	/**
	 * Fetch all rows in a query result set.
	 * 
	 * @return \Clicalmani\Core\Collection\CollectionInterface
	 */
	public function all() : \Clicalmani\Core\Collection\Collection;

	/**
	 * Limit the number of rows to be returned in a query result set.
	 *
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return self
	 */
	public function limit(int $count = 1) : self;

	public function skip(int $offset = 0) : self;

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
	 * @param string|\Closure|null $foreignKey [Optional] Foreign key
	 * @param ?string $localKey [Optional] Original key
	 * @return self
	 */
	public function joinLeft(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self;

	/**
	 * Right join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string|\Closure|null $foreignKey [Optional] Foreign key
	 * @param ?string $localKey [Optional] Original key
	 * @return self
	 */
	public function joinRight(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self;

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string|\Closure|null $foreignKey [Optional] Foreign key
	 * @param ?string $localKey [Optional] Original key
	 * @return self
	 */
	public function joinInner(string $table, string|\Closure|null $foreignKey = null, ?string $localKey = null) : self;

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
	 * @return \Clicalmani\Core\Collection\CollectionInterface
	 */
	public function getBuilderResult() : \Clicalmani\Core\Collection\Collection;

	/**
	 * Returns the query builder object.
	 * 
	 * @return ?\Clicalmani\Database\Interfaces\BuilderInterface
	 */
	public function getBuilder();

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

	/**
	 * Retrieve a single column value from the first matching row.
	 *
	 * @param string $field The column name to retrieve
	 * @param mixed $default [Optional] Default value to return if no row is found
	 * @return mixed
	 */
	public function value(string $field) : mixed;

	/**
	 * Count the number of rows matching the current query.
	 *
	 * By default, counts all rows. Pass a column name to count non-null
	 * values in that column, or use the $distinct flag to count unique
	 * values (COUNT(DISTINCT column)).
	 *
	 *     $query->count();                      // COUNT(*)
	 *     $query->count('email');               // COUNT(email) — ignore les NULL
	 *     $query->count('user_id', distinct: true); // COUNT(DISTINCT user_id)
	 *
	 * @param string $field [Optional] Column to count. Default '*' (all rows)
	 * @param bool $distinct [Optional] Whether to count distinct values. Default false
	 * @param int $default [Optional] Value returned when no row matches. Default 0
	 * @return int
	 */
	public function count(string $field = '*', bool $distinct = false, int $default = 0) : int;

	/**
	 * Retrieve the sum of the given column across all matching rows.
	 *
	 * Returns the given $default (null by default) when no row matches,
	 * which mirrors SQL's own behavior (SUM over an empty set is NULL).
	 *
	 * @param string $field Column to sum
	 * @param mixed $default [Optional] Value returned when no row matches. Default null
	 * @return mixed
	 */
	public function sum(string $field, mixed $default = null) : mixed;

	/**
	 * Retrieve the maximum value of the given column across all matching rows.
	 *
	 * The return type is preserved as-is from the database, so this works
	 * for numeric, string, and date columns alike. Returns $default (null
	 * by default) when no row matches.
	 *
	 * @param string $field Column to inspect
	 * @param mixed $default [Optional] Value returned when no row matches. Default null
	 * @return mixed
	 */
	public function max(string $field, mixed $default = null) : mixed;

	/**
	 * Retrieve the minimum value of the given column across all matching rows.
	 *
	 * The return type is preserved as-is from the database, so this works
	 * for numeric, string, and date columns alike. Returns $default (null
	 * by default) when no row matches.
	 *
	 * @param string $field Column to inspect
	 * @param mixed $default [Optional] Value returned when no row matches. Default null
	 * @return mixed
	 */
	public function min(string $field, mixed $default = null) : mixed;

	/**
	 * Retrieve the average of the given column across all matching rows.
	 *
	 * Always returns a float when a value is present, since averages are
	 * inherently fractional. Returns $default (null by default) when no row
	 * matches.
	 *
	 * @param string $field Column to average
	 * @param mixed $default [Optional] Value returned when no row matches. Default null
	 * @return mixed
	 */
	public function avg(string $field, mixed $default = null) : mixed;

	/**
	 * Find a record by its id
	 * 
	 * @param int $id Record id
	 * @param string $column [optional] Column name. Default is 'id'
	 * @return mixed
	 */
	public function find(int|string $id, ?string $column = 'id') : mixed;

	/**
	 * Chunk the results of the query by fetching a fixed number of rows at a time
	 * and passing each chunk to the given callback.
	 *
	 * The callback receives the current chunk (a Collection) and the current page
	 * number (starting at 1). If the callback returns false, chunking will stop.
	 *
	 * @param int $size Number of rows per chunk
	 * @param callable $callback Callback receiving (Collection $chunk, int $page)
	 * @param string|null $column [Optional] Column to order by for stable pagination
	 * @return bool True if all chunks were processed, false if the callback stopped it
	 */
	public function chunk(int $size, callable $callback, ?string $column = null) : bool;

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
	 * Paginate the query results.
	 *
	 * Returns a paginator object exposing the current page's items along with
	 * metadata: total items, total pages, current page, per-page size, and
	 * whether previous/next pages exist.
	 *
	 * The paginator is iterable and countable, so existing code that treats
	 * the return value as a collection continues to work:
	 *
	 *     foreach ($query->paginate(1, 15) as $row) { ... }
	 *     $query->paginate(1, 15)->count();
	 *
	 * @param int $page Current page (1-based)
	 * @param int $size Number of items per page
	 * @param string|null $column [Optional] Column to order by for stable pagination
	 * @param bool $countTotal [Optional] Whether to compute the total row count. Default true
	 * @return \Clicalmani\Core\Collection\Paginator
	 */
	public function paginate(int $page = 1, int $size = 15, ?string $column = 'id', bool $countTotal = true) : \Clicalmani\Core\Collection\Paginator;

	/**
	 * Paginate the query results without computing the total row count.
	 *
	 * Unlike paginate(), this method does not execute a COUNT(*) query,
	 * making it significantly faster on large datasets. In exchange, the
	 * paginator cannot tell you the total number of items or the last page
	 * number. It can, however, tell you whether there are more pages by
	 * checking if the current page is full.
	 *
	 * Returns a Paginator instance that is iterable and countable, so it can
	 * be used as a drop-in replacement for the plain Collection previously
	 * returned:
	 *
	 *     foreach ($query->simplePaginate(1, 15) as $row) { ... }
	 *
	 * @param int $page Current page (1-based)
	 * @param int $size Number of items per page
	 * @param string|null $column [Optional] Column to order by for stable pagination
	 * @return \Clicalmani\Core\Collection\Paginator
	 */
	public function simplePaginate(int $page = 1, int $size = 15, ?string $column = 'id') : \Clicalmani\Core\Collection\Paginator;

	/**
	 * Lazily iterate over the result set, fetching rows in chunks to avoid
	 * loading the entire dataset into memory at once.
	 *
	 * The generator yields one row at a time. Internally, rows are fetched
	 * from the database in chunks of $chunk size. An ORDER BY clause is
	 * required for stable pagination; if none is set, one is applied
	 * automatically using the given column (defaults to the primary key
	 * convention 'id').
	 *
	 * Example:
	 *     foreach ($query->lazy(500) as $row) {
	 *         // process $row
	 *     }
	 *
	 * @param int $chunk [Optional] Number of rows to fetch per chunk. Default 1000
	 * @param string|null $column [Optional] Column to order by for stable pagination
	 * @return \Generator
	 */
	public function lazy() : \Generator;

	/**
	 * Pluck a single column's values from the result set.
	 *
	 * @param string $column The column to pluck
	 * @param string|null $key [Optional] Column to use as the key for the returned array
	 * @return \Clicalmani\Core\Collection\CollectionInterface
	 */
	public function pluck(string $field, ?string $key = null) : \Clicalmani\Core\Collection\CollectionInterface;

	/**
	 * Lazily iterate over the result set ordered by the given column in ascending order.
	 * Useful for processing large datasets without loading everything into memory at once.
	 *
	 * @param string $column The column to order by
	 * @param int $chunk [Optional] Number of rows to fetch per chunk. Default 1000
	 * @return \Generator
	 */
	public function lazyBy(string $column, int $chunk = 1000) : \Generator;

	/**
	 * Lazily iterate over the result set ordered by the given column in descending order.
	 * Useful for processing large datasets without loading everything into memory at once.
	 *
	 * @param string $column The column to order by
	 * @param int $chunk [Optional] Number of rows to fetch per chunk. Default 1000
	 * @return \Generator
	 */
	public function lazyByDesc(string $column, int $chunk = 1000) : \Generator;

	/**
	 * Determine whether at least one row matches the current query.
	 *
	 * Uses SELECT 1 ... LIMIT 1 instead of COUNT(*) for performance: the
	 * database stops scanning at the first matching row, so the cost is
	 * O(1) regardless of how many rows match. On a table with millions of
	 * rows, this is orders of magnitude faster than COUNT(*) > 0.
	 *
	 * The query is cloned, so the original instance remains reusable.
	 *
	 *     if (User::query()->where('email = ?', [$email])->exists()) {
	 *         // ...
	 *     }
	 *
	 * @return bool
	 */
	public function exists() : bool;

	/**
	 * Determine whether no row matches the current query.
	 *
	 * Strict inverse of exists(). Uses the same SELECT 1 ... LIMIT 1
	 * strategy, so it's just as fast.
	 *
	 *     if (User::query()->where('email = ?', [$email])->doesntExist()) {
	 *         // safe to create the user
	 *     }
	 *
	 * @return bool
	 */
	public function doesntExist() : bool;

	/**
	 * Apply a callback to the query when the given condition is truthy.
	 *
	 * The callback receives the current query builder instance, allowing it
	 * to chain any query method. Its return value is used if it returns
	 * something other than null; otherwise, the original instance is returned
	 * to preserve the fluent chain.
	 *
	 * If $default is provided and the condition is falsy, $default is invoked
	 * instead. This avoids chaining a separate unless() call.
	 *
	 *     // Simple usage
	 *     $query->when($isAdmin, fn($q) => $q->where('role = ?', ['admin']));
	 *
	 *     // With an else branch
	 *     $query->when(
	 *         $isAdmin,
	 *         fn($q) => $q->where('role = ?', ['admin']),
	 *         fn($q) => $q->where('role = ?', ['user']),
	 *     );
	 *
	 *     // Truthy non-boolean conditions work too
	 *     $query->when($search, fn($q) => $q->where('name LIKE ?', ["%$search%"]));
	 *
	 * @param mixed $condition Any truthy/falsy value
	 * @param callable $callback Callback invoked when $condition is truthy
	 * @param callable|null $default [Optional] Callback invoked when $condition is falsy
	 * @return static
	 */
	public function when(mixed $condition, callable $callback, ?callable $default = null) : static;

	/**
	 * Apply a callback to the query when the given condition is falsy.
	 *
	 * Strict inverse of when(): the callback is invoked only when $condition
	 * evaluates to false. Its return value is used if it returns a builder
	 * instance; otherwise, the current instance is returned.
	 *
	 *     // Apply a filter only when the user is NOT an admin
	 *     $query->unless($isAdmin, fn($q) => $q->where('role = ?', ['user']));
	 *
	 * @param mixed $condition Any truthy/falsy value
	 * @param callable $callback Callback invoked when $condition is falsy
	 * @param callable|null $default [Optional] Callback invoked when $condition is truthy
	 * @return static
	 */
	public function unless(mixed $condition, callable $callback, ?callable $default = null) : static;
	
	/**
	 * Adds a sub-query in the where condition of a query.
	 * @param string $relation The sub-query table
	 * @param string $key The to filter with
	 * @param \Closure $callback A callback function
	 * @param ?string $boolean The boolean operator
	 * @param ?string $operator The operator to be used with the key.
	 * @return self
	 */
	public function subWhere(string $relation, string $key, \Closure $callback, ?string $boolean = 'AND', ?string $operator = '=') : self;
}