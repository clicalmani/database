<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;

class ScopeFindOrFail implements ScopeInterface
{
    public function __construct(
        protected readonly string|array $id
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

        if (!$row) throw new ModelNotFoundException("Model not found", 404);
        return new ($model::class)($key->fromResult((array)$row)->toValue());
    }
}