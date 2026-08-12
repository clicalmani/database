<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Foundation\Support\Facades\DB;

class ScopeCreateOrFail implements ScopeInterface
{
    public function __construct(
        protected readonly array $attributes = []
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        return DB::deadlock(function() use ($query, $model) {
            try {
                $query->set('table', $model->getTable()->name());
                return $query->insert([$this->attributes], false);
            } catch (\Throwable $e) {
                return false;
            }
        });
    }
}