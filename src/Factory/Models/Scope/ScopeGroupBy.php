<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeGroupBy implements ScopeInterface
{
    public function __construct(
        private readonly string $criteria,
        private readonly bool $withRollup
    )
    {}
    
    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->groupBy($this->criteria . ($this->withRollup ? ' WITH ROLLUP': ''));
        return $model;
    }
}