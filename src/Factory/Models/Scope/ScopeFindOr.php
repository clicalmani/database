<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeFindOr implements ScopeInterface
{
    public function __construct(
        protected readonly string|array $id,
        protected readonly \Closure $callback
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->set('tables', [$model->getTable()->name()]);
        $key = $model->getKey();
        $key = Key::fromValue($this->id, $key->isComposite() ? $key->names(): $key->scalarName());
        $query->where(...$key->toSqlCondition());
        $row = $query->exec()->result()->first();
        
        if ($row) return new ($model::class)($key->fromResult((array)$row)->toValue());
        return $model->hydrate(call($this->callback));
    }
}