<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeOrderBy implements ScopeInterface
{
    public function __construct(
        private readonly string $column,
        private readonly string $order
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->params['order_by'] = 
            (string) \Clicalmani\Database\Concerns\OrderBy::from($this->column, $this->order);
        return $model;
    }
}