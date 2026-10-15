<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeCreate implements ScopeInterface
{
    public function __construct(
        protected readonly array $attributes = []
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ?ModelInterface
    {
        return tap($model, fn($model) => $model->fill($this->attributes));
    }
}