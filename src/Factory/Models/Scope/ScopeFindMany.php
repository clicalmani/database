<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;
use stdClass;

class ScopeFindMany implements ScopeInterface
{
    public function __construct(
        protected readonly array $ids
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        $query->set('tables', [$model->getTable()->name()]);
        $key = $model->getKey();

        if (!$key->isComposite()) {
            $query->whereIn($key->scalarName(), $this->ids);
        } else {
            foreach ($key->names() as $index => $name) {
                if (!is_array($this->ids[$index])) {
                    throw new \InvalidArgumentException("Composite key requires an array of key-value pairs for each id.");
                }
                $query->whereIn($name, $this->ids[$index]);
            }
        }

        return $query->get()->map(fn(stdClass $row) => 
            new ($model::class)($key->fromResult((array)$row)->toValue())
        );
    }
}