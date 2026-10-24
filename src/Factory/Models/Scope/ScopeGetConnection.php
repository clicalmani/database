<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeGetConnection implements ScopeInterface
{
    public function __construct()
    {}

    public function apply(QueryInterface $query, ModelInterface $model): \PDO
    {
        return $query->getPdo();
    }
}