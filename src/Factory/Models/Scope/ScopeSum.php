<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeSum implements ScopeInterface
{
    public function __construct(
        private readonly string $field,
        private readonly mixed $default = null
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        return $query->sum($this->field, $this->default);
    }
}