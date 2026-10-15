<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Override;

final class ScopeWhere implements ScopeInterface
{
    public function __construct(
        protected readonly string|bool|\Closure $condition = true,
        protected readonly array $options = []
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->where($this->condition, $this->options);
        return $model;
    }
}