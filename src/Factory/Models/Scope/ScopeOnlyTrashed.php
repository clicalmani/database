<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeOnlyTrashed implements ScopeInterface
{
    public function __construct()
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface
    {
        return $model->setSoftDeleteMode('only');
    }
}