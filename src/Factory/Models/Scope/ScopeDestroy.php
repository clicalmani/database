<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeDestroy implements ScopeInterface
{
    public function __construct(
        protected string|int|array $keys
    )
    {
        $this->keys = (array) $keys;
    }

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): bool
    {
        if (! count($this->keys) ) return false;

        $query->set('table', $model->getTable()->name());

        $key = $model->getKey();

        if (!$key->isComposite()) {
            $query->whereIn($key->scalarName(), $this->keys);
        } else {
            $ids = $this->keys;
            foreach ($key->names() as $index => $name) {
                $query->where("{$name} = ?", [$this->keys[$index]]);
            }
        }
        
        return $query->exec()->status() === 'success';
    }
}