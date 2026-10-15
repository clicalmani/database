<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Core\Support\Facades\DB;

class ScopeCreateOrFail implements ScopeInterface
{
    public function __construct(
        protected readonly array $attributes = []
    )
    {}

    public function apply(QueryInterface $query, ModelInterface $model): ModelInterface|false
    {
        return DB::deadlock(function() use ($query, $model) {
            try {
                return $model::class::create($this->attributes);
            } catch (\Throwable $e) {
                return false;
            }
        });
    }
}