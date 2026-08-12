<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeQuery implements ScopeInterface
{
    public function __construct(
        protected readonly string|\Closure $select = '*'
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        if ($this->select instanceof \Closure) {
           with ($this->select)($query);
        } else $query->set('fields', $this->select);
        return $query;
    }
}