<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeSelectRaw implements ScopeInterface
{
    public function __construct(
        protected readonly string|\Closure $query = '*'
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->set('tables', [$model->getTable()->withAlias()]);

        if ($this->query instanceof \Closure) {
           with ($this->query)($query);
        } else {
            $query->set('fields', $this->query);
        }
        return $model;
    }
}