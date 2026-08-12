<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeSelect implements ScopeInterface
{
    public function __construct(
        protected readonly string|\Closure $query = '*'
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        if ($this->query instanceof \Closure) {
           with ($this->query)($query);
        } else $query->set('fields', $this->query);
        return $model;
    }
}