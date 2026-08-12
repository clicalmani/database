<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeWith implements ScopeInterface
{
    public function __construct(
        protected readonly string|array $relation,
        protected readonly ?\Closure $callback = null
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $model->scopeWith($this->relation);
        if ($this->callback) call($this->callback, $query);
        return $model;
    }
}