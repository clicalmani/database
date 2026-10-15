<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeHaving implements ScopeInterface
{
    public function __construct(
        private readonly string $criteria
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->having($this->criteria);
        return $model;
    }
}