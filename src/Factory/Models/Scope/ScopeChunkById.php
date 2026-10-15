<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeChunkById implements ScopeInterface
{
    public function __construct(
        protected readonly int $size,
        protected readonly \Closure $callback,
        protected readonly ?string $column = null
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): void
    {
        $query->chunkById($this->size, $this->callback, $this->column);
    }
}