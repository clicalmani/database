<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeDecrement implements ScopeInterface
{
    public function __construct(
        private readonly string $field,
        private readonly int $value = 1,
        private readonly ?array $fields = []
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): bool
    {
        return $query->decrement($this->field, $this->value, $this->fields);
    }
}