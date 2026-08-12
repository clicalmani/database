<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeWhereIn implements ScopeInterface
{
    public function __construct(
        protected readonly string $key,
        protected readonly array $values
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->whereIn($this->key, $this->values);
        return $model;
    }
}