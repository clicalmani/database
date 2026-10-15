<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Core\Collection\CollectionInterface;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopePluck implements ScopeInterface
{
    public function __construct(
        protected readonly string $column,
        protected readonly ?string $key = null
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): CollectionInterface
    {
        return $query->pluck($this->column, $this->key);
    }
}