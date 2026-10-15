<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeOrWhereHas implements ScopeInterface
{
    public function __construct(
        protected readonly string $relation,
        protected readonly \Closure $callback
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->orWhereHas($this->criteria, $this->callback);
        return $model;
    }
}