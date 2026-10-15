<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopePaginate implements ScopeInterface
{
    public function __construct(
        protected readonly int $page,
        protected readonly int $size,
        protected readonly string $column = 'id',
        protected readonly bool $countTotal = true
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): \Clicalmani\Core\Collection\Paginator
    {
        return $query->paginate($this->page, $this->size, $this->column, $this->countTotal);
    }
}