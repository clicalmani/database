<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeWhereDoesntHave implements ScopeInterface
{
    public function __construct(
        protected readonly string $relation,
        protected readonly \Closure $callback,
        protected readonly string $boolean = 'AND'
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->whereDoesntHave(
            instance($this->relation)->getTable()->name(), 
            $this->callback, 
            $this->boolean
        );
        return $model;
    }
}