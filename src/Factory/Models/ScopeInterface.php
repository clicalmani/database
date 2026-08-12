<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\QueryInterface;

interface ScopeInterface
{
    public function apply(QueryInterface $query, ModelInterface $model): mixed;
}