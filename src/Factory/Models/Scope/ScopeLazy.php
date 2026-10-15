<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeLazy implements ScopeInterface
{
    public function __construct(
        protected readonly int $chunk = 1000,
        protected readonly ?string $column = 'id'
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): \Generator
    {
        return $query->lazy($this->chunk, $this->column);
    }
}