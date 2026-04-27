<?php
namespace Clicalmani\Database\Factory\Models;

interface SQLClausesInterface
{
    /**
     * Add a where clause to the query.
     * 
     * The method accepts either a single string argument (the where clause) or two arguments (the where clause and an array of parameters).
     * If a closure is passed as the only argument, it will be executed with the query builder instance, allowing for more complex query constructions.
     * Example usage:
     * - where('status = ?', ['active'])
     * - where('created_at > ?', ['2024-01-01'])
     * - where(function($query) { $query->where('status = ?', ['active'])->where('created_at > ?', ['2024-01-01']); })
      * @param mixed ...$args
     * @return static
     */
    public static function where(\Closure|string $criteria = '1', ?array $options = []) : static;

    /**
     * Add a where clause with IN operator
     * 
     * @param string $key Column name
     * @param array $values Values to match
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public static function whereIn(string $key, array $values): \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Like where clause but with and as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function whereAnd(?string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Like where clause but with or as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function whereOr(string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add a where clause to the query based on the existence of a related model.
     * 
     * The method accepts the name of the related model and a closure that defines the conditions for the related model.
     * The closure receives an instance of the query builder for the related model, allowing for complex conditions to be defined.
     * Example usage:
     * - whereHas(RelatedModel::class, function($query) { $query->where('status = ?', ['active']); })
     * 
     * @param class-string $relation The name of the related model class.
     * @param \Closure $callback A closure that defines the conditions for the related model.
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public static function whereHas(string $relation, \Closure $callback, string $boolean = 'AND') : \Clicalmani\Database\Factory\Models\ModelInterface;

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
	 * @param class-string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @param string $boolean [Optional] The boolean operator to use when combining this clause with others (default is 'AND')
	 * @return \Clicalmani\Database\Factory\Models\ModelInterface
	 */
	public static function whereDoesntHave(string $relation, \Closure $callback, string $boolean = 'AND') : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
	 * Add an OR where clause to the query based on the existence of a related model.
	 * 
	 * This method functions similarly to whereHas, but it combines the clause with others using the OR operator instead of AND.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @return \Clicalmani\Database\Factory\Models\ModelInterface
	 */
    public function orWhereHas(string $relation, \Closure $callback) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
	 * Add an OR where clause to the query based on the non-existence of a related model.
	 * 
	 * This method functions similarly to whereDoesntHave, but it combines the clause with others using the OR operator instead of AND.
	 * 
	 * @param string $relation The name of the related model
	 * @param \Closure $callback A closure that defines the conditions for the related model
	 * @return \Clicalmani\Database\Factory\Models\ModelInterface
	 */
    public function orWhereDoesntHave(string $relation, \Closure $callback) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * SQL order by clause.
     * 
     * @param string $order
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function orderBy(string $order) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * SQL having clause.
     * 
     * @param string $criteria
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function having(string $criteria) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL GROUP BY operator the query.
     * 
     * @param string $criteria
     * @param ?bool $with_rollup
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function groupBy(string $criteria, ?bool $with_rollup = false) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL FROM clause to DELETE query.
     * 
     * @param string $fields
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function from(string $fields) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL LIMIT clause to the query.
     * 
     * @param ?int $offset
     * @param ?int $row_count
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function limit(?int $offset = 0, ?int $row_count = 1) : \Clicalmani\Database\Factory\Models\ModelInterface;
}