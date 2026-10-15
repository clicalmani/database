<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Override;

final class ScopeUnless implements ScopeInterface
{
    public function __construct(
        protected readonly mixed $condition, 
        protected readonly \Closure $callback, 
        protected readonly ?\Closure $default = null
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->unless($this->condition, $this->callback, $this->default);
        return $model;
    }
}