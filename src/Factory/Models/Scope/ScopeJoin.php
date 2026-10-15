<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeJoin implements ScopeInterface
{
    public function __construct(
        protected string|\Closure|ModelInterface $model, 
        protected readonly ?\Closure $callback = null
    )
    {
        if (is_string($model)) {
            /** @var ModelInterface */
            $this->model = new $model;
        }
    }

    public function apply(QueryInterface $query, ModelInterface $model): \Generator
    {
        if ($this->model instanceof \Closure) $query->join($this->model);
        else $query->join($this->model->getTable()->withAlias(), $this->callback);
        return $model;
    }
}