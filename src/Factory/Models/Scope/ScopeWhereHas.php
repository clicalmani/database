<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeWhereHas implements ScopeInterface
{
    /**
     * @param class-string<ModelInterface> $relation The name of the related model class.
     * @param \Closure $callback A closure that defines the conditions for the related model.
     * @param string $boolean [Optional] The boolean operator to use when combining this clause with others (default is 'AND').
     */
    public function __construct(
        protected readonly string $relation,
        protected readonly \Closure $callback,
        protected readonly string $boolean = 'AND'
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->whereHas(
            instance($this->relation)->getTable()->name(), 
            $this->callback, 
            $this->boolean
        );
        return $model;
    }
}