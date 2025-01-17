<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLClauses
{
    /**
     * SQL where clause
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return static
     */
    public static function where(?string $criteria = '1', ?array $options = []) : static
    {
        $instance = static::getInstance();
        $instance->getQuery()->where($criteria, 'AND', $options);

        return $instance;
    }

    /**
     * Like where clause but with and as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return static
     */
    public function whereAnd(?string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'AND', $options);
        return $this;
    }

    /**
     * Like where clause but with or as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return static
     */
    public function whereOr(string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'OR', $options);
        return $this;
    }
    
    /**
     * SQL order by clause.
     * 
     * @param string $order
     * @return static
     */
    public function orderBy(string $order) : static
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    /**
     * SQL having clause.
     * 
     * @param string $criteria
     * @return static
     */
    public function having(string $criteria) : static
    {
        $this->query->having($criteria);
        return $this;
    }

    /**
     * Add the SQL GROUP BY operator the query.
     * 
     * @param string $criteria
     * @param ?bool $with_rollup
     * @return static
     */
    public function groupBy(string $criteria, ?bool $with_rollup = false) : static
    {
        if ($with_rollup) $criteria .= ' WITH ROLLUP';
        $this->query->groupBy($criteria);
        return $this;
    }

    /**
     * Add the SQL FROM clause to DELETE query.
     * 
     * @param string $fields
     * @return static
     */
    public function from(string $fields) : static
    {
        $this->query->from($fields);
        return $this;
    }

    /**
     * Add the SQL LIMIT clause to the query.
     * 
     * @param ?int $offset
     * @param ?int $row_count
     * @return static
     */
    public function limit(?int $offset = 0, ?int $row_count = 1) : static
    {
        $this->query->set('offset', $offset);
        $this->query->set('limit', $row_count);
        return $this;
    }
}