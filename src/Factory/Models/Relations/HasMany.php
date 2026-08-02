<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class HasMany extends Relationship
{
    private Elegant $related;

    /**
     * @param Elegant $model           Parent model (e.g., Department)
     * @param class-string<Elegant>    $relatedClass     Child model (e.g., Employee)
     * @param string|null $foreignKey  Foreign key in the child model (e.g., department_id)
     * @param string|null $localKey    Parent local key (e.g., id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
        // If foreign key is not specified, we guess it (e.g., department_id)
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->localKey   = $localKey ?: $this->model->getKey();

        $this->related = new $this->relatedClass;
        $this->query   = $this->related->newQuery();
    }

    /**
     * Retrieve child models collection
     * 
     * @return mixed
     */
    public function get(?string $fields = '*'): mixed
    {
        // Filter : WHERE department_id = [Actual department ID]
        $this->where("{$this->foreignKey} = ?", [$this->model->{$this->localKey}]);

        // Result collection
        $this->result = $this->related->get($fields);

        return $this->result;
    }

    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->localKey);
    }

    public function getEager(array $keys): CollectionInterface
    {
        if (empty($keys)) {
            return collect();
        }
        
        return $this->relatedClass::whereIn($this->foreignKey, $keys)->get();
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];
        
        /**
         * Key-Data mapping for easy access
         * @var Elegant
         */
        foreach ($results as $result) {
            $key = (string) $result->{$this->foreignKey}; // Parent ID (e.g., department_id)
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $result;
        }

        foreach ($models as $model) {
            $key = (string) $model->{$this->localKey};
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, collect());
            }
        }
    }
}