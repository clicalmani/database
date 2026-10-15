<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeCount implements ScopeInterface
{
    use AppliesSoftDeleteScope;

    public function __construct(
        private readonly string $field = '*',
        private readonly bool $distinct = false,
        private readonly int $default = 0
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): int
    {
        $this->applySoftDeleteScope($model, $query);
        return $query->count($this->field, $this->distinct, $this->default);
    }
}