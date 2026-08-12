<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeDestroy implements ScopeInterface
{
    public function __construct(
        protected readonly string $fields = '*'
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->set('table', $model->getTable()->name());
        return $query->truncate();
    }
}