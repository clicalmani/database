<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeWhereExists implements ScopeInterface
{
    public function __construct(
        protected readonly string $criteria,
        protected readonly array $options = []
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->whereExists($this->criteria, $this->options);
        return $model;
    }
}