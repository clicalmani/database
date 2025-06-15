<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLClauses
{
    public static function where(?string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface
    {
        $instance = static::getInstance();
        $instance->getQuery()->where($criteria, 'AND', $options);

        return $instance;
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