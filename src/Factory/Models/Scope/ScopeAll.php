<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use stdClass;

class ScopeAll implements ScopeInterface
{
    public function __construct()
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->set('tables', [$model->getTable()->name()]);
        return $query->exec()->result()->map(function(stdClass $row) use($model) {
            return new ($model::class)($model->getKey()->fromResult((array)$row)->toValue());
        });
    }
}