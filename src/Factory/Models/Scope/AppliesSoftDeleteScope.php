<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\QueryInterface;

trait AppliesSoftDeleteScope
{
    private function applySoftDeleteScope(ModelInterface $model, QueryInterface $query): void
    {
        if (!$model->usesSoftDeletes()) return;

        $column = $model->getDeletedAtColumn();

        $scopeCondition = match ($model->getSoftDeleteMode()) {
            'with'    => null,                        
            'only'    => "{$column} IS NOT NULL",
            default   => "{$column} IS NULL",
        };
        
        if ($scopeCondition) {
            $where = $query->params['where'] ?? '';
            $where = $where === '' ? $scopeCondition : "({$where}) AND {$scopeCondition}";

            $query->params['where'] = $where;
        }
    }
}