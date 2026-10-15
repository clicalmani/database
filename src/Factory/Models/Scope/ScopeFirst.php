<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use stdClass;

class ScopeFirst implements ScopeInterface
{
    use AppliesSoftDeleteScope;

    public function __construct()
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ?ModelInterface
    {
        $this->applySoftDeleteScope($model, $query);
        
        if ($row = $query->first()) {
            return new $model($model->getKey()->fromResult((array)$row)->toValue());
        }

        return null;
    }
}