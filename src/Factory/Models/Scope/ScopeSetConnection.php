<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeSetConnection implements ScopeInterface
{
    public function __construct(
        private readonly string $driver
    ) {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        $query->connection($this->driver);

        return $model;
    }
}