<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeSimplePaginate implements ScopeInterface
{
    public function __construct(
        protected readonly ?int $page = 1,
        protected readonly ?int $size = 15,
        protected readonly ?string $column = 'id'
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): \Clicalmani\Core\Collection\Paginator
    {
        return $query->simplePaginate($this->page, $this->size, $this->column);
    }
}