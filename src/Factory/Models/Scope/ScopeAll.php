<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Core\Collection\CollectionInterface;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use stdClass;

class ScopeAll implements ScopeInterface
{
    use AppliesSoftDeleteScope;

    public function __construct()
    {}

    public function apply(QueryInterface $query, ModelInterface $model): CollectionInterface
    {
        $this->applySoftDeleteScope($model, $query);
        
        return $query->get()->map(function(stdClass $row) use($model) {
            return new ($model::class)($model->getKey()->fromResult((array)$row)->toValue());
        });
    }
}