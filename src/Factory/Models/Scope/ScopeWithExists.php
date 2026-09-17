<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Core\Support\Facades\Str;

class ScopeWithExists implements ScopeInterface
{
    public function __construct(
        protected readonly string $relation,
        protected readonly \Closure $callback
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        /** @var class-string<self> */
        $morphClass = array_key_first($this->relation);
        /** @var string */
        $alias = $this->relation[$morphClass];
        $morphModel = new $morphClass;

        $foreignKey = Str::singularize($model->getTable()->name()) . '_id';
        $morphType = Str::singularize($morphModel->getTable()->name()) . '_type';

        (new \Clicalmani\Database\SubQueries\WithExists($query, function(QueryInterface $query) use($model, $morphModel, $foreignKey, $morphType, $callback) {
            $query->selectRaw('1')
                ->from($morphModel->getTable()->withAlias())
                ->where("{$foreignKey} = {$model->getTable()->alias()}.{$model->getKey()->scalarName()}");
                $callback($query);
        }))($alias);

        return $model;
    }
}