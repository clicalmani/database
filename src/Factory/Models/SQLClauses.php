<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLClauses
{
    public function orWhere(mixed ...$args) : self
    {
        $this->query->orWhere(...$args);
        return $this;
    }

    /**
     * Add a where clause to the query with an AND boolean operator.
     * 
     * This method is used to add a where clause to the query with an AND boolean operator. It accepts a criteria string or a closure that defines the conditions for the where clause, along with an optional array of parameters.
     * The criteria string can include placeholders for parameters, which will be replaced with the values from
     * the options array. If a closure is provided, it will be executed with the query builder instance, allowing for more complex conditions to be defined.
     * Example usage:
     * - where('status = ?', ['active'])
     * - where(function($query) { $query->where('status = ?', ['active'])->orWhere('status = ?', ['pending']); })
     * @param \Closure|string $criteria The criteria for the where clause, either as a string with placeholders or as a closure that defines the conditions.
     * @param array|null $options An optional array of parameters to replace placeholders in the criteria string.
     * @return static
     */
    public function andWhere(\Closure|string $criteria = '1', ?array $options = []) : self
    {
        $this->query->where($criteria, $options);
        return $this;
    }

    public function orWhereHas(string $relation, \Closure $callback) : self
    {
        $this->query->whereHas(instance($relation)->getTable()->name(), $callback, 'OR');
        return $this;
    }

    public function orWhereDoesntHave(string $relation, \Closure $callback) : self
    {
        return $this->query->orWhereDoesntHave(instance($relation)->getTable()->name(), $callback, 'OR');
    }

    public function from(string $table) : static
    {
        $this->query->from($table);
        return $this;
    }
    
    public function value(string $column) : mixed
    {
        return $this->get()->first()?->{$column};
    }

    /**
     * Set the marker for the query.
     *
     * @param string|null $value
     * @return static
     */
    public function marker(?string $value = ':') : static
    {
        $this->query->set('marker', $value);
        return $this;
    }
}