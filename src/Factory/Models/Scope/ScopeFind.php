<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeFind implements ScopeInterface
{
    use AppliesSoftDeleteScope;

    public function __construct(
        protected readonly string|array|null $id
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ?ModelInterface
    {
        if (!$this->id) return null;
        
        $query->set('tables', [$model->getTable()->name()]);
        $key = $model->getKey();
        $key = Key::fromValue($this->id, $key->isComposite() ? $key->names(): $key->scalarName());
        $query->where(...$key->toSqlCondition());

        $this->applySoftDeleteScope($model, $query);
        
        $row = $query->exec()->result()->first();
        
        if ($row) return new ($model::class)($key->fromResult((array)$row)->toValue());
        return null;
    }
}