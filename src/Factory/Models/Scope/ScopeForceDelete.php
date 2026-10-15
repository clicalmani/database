<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeForceDelete implements ScopeInterface
{
    public function __construct()
    {}

    public function apply(QueryInterface $query, ModelInterface $model): bool
    {
        /**
         * A delete operation must be set on a condition.
         * We first check the query where parameter.
         */
        if (empty($query->params['where'])) {
            throw new \Clicalmani\Core\Exceptions\ModelException("A delete operation must be set on a condition.");
        }
        
        return $query->delete();
    }
}