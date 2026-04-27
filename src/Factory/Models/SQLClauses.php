<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLClauses
{
    public static function where(\Closure|string $criteria = '1', ?array $options = []) : static
    {
        $instance = static::getInstance();
        $query = $instance->getQuery();

        if ( $criteria instanceof \Closure ) {
            $criteria($query);
            return $instance;
        }

        $query->where($criteria, 'AND', $options);

        return $instance;
    }
    
    public static function whereIn(string $key, array $values): self
    {
        return static::where("$key IN (" . 
                    implode(', ', array_fill(0, count($values), '?')) . ")", $values);
    }

    public function whereAnd(?string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'AND', $options);
        return $this;
    }

    public function whereOr(string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'OR', $options);
        return $this;
    }

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
     * @return static
     */
    public static function whereHas(string $relation, \Closure $callback, string $boolean = 'AND') : static
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $instance = static::getInstance();

        $instance->getQuery()->whereHas(instance($relation)->getTable(), $callback, $boolean);

        return $instance;
    }

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
	 * @return static
	 */
	public static function whereDoesntHave(string $relation, \Closure $callback, string $boolean = 'AND') : static
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $instance = static::getInstance();

        $instance->getQuery()->whereDoesntHave(instance($relation)->getTable(), $callback, $boolean);

        return $instance;
    }

    public function orWhereHas(string $relation, \Closure $callback) : static
    {
        return static::whereHas($relation, $callback, 'OR');
    }

    public function orWhereDoesntHave(string $relation, \Closure $callback) : static
    {
        return static::whereDoesntHave($relation, $callback, 'OR');
    }
    
    public function orderBy(string $order) : static
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    public function having(string $criteria) : static
    {
        $this->query->having($criteria);
        return $this;
    }

    public function groupBy(string $criteria, ?bool $with_rollup = false) : static
    {
        if ($with_rollup) $criteria .= ' WITH ROLLUP';
        $this->query->groupBy($criteria);
        return $this;
    }

    public function from(string $fields) : static
    {
        $this->query->from($fields);
        return $this;
    }

    public function limit(?int $offset = 0, ?int $row_count = 1) : static
    {
        $this->query->set('offset', $offset);
        $this->query->set('limit', $row_count);
        return $this;
    }
}