<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeCreate implements ScopeInterface
{
    public function __construct(
        protected readonly array $attributes = [],
        protected readonly bool $orUpdate = false
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        return $model->insert([$this->attributes], $this->orUpdate);
    }
}