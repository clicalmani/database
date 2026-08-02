<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;

class MorphOne extends Relationship
{
    private Elegant $related;

    /**
     * @param Elegant $model        The parent model (e.g., User)
     * @param class-string<Elegant> $relatedClass  The child model (e.g., Image)
     * @param string $name          The relationship name (e.g., 'imageable')
     * @param string|null $idKey    ID key (e.g., 'imageable_id')
     * @param string|null $typeKey  Type key (e.g., 'imageable_type')
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected string $name,
        protected ?string $idKey = null,
        protected ?string $typeKey = null
    ) {
        $this->idKey   = $idKey ?: $name . '_id';
        $this->typeKey = $typeKey ?: $name . '_type';
        $this->related = new $this->relatedClass;
        $this->query   = $this->related->newQuery();
    }

    /**
     * Retrive the unique child model (e.g., Image)
     * 
     * @return mixed
     */
    public function get(?string $fields = '*'): mixed
    {
        // Filters
        // 1. imageable_id = ID 
        // 2. imageable_type = User::class
        $this->query->where("{$this->idKey} = ?", [$this->model->{$this->model->getKey()}]);
        $this->query->where("{$this->typeKey} = ?", [$this->model::class]);

        $this->result = $this->related->top(1)->get($fields);

        return $this->result;
    }

    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->model->getKey());
    }

    public function getEager(array $keys): CollectionInterface
    {
        if (empty($keys)) {
            return collect();
        }
        
        return $this->relatedClass::select()                            // SELECT images.*
            ->whereIn($this->idKey, $keys)                              // imageable_id IN (IDs)
            ->andWhere($this->typeKey . ' = ?', [$this->model::class])  // imageable_type = User::class
            ->get();
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];
        
        /**
         * Key-Data mapping
         * @var Elegant
         */
        foreach ($results as $result) {
            $key = (string) $result->{$this->idKey};
            $dictionary[$key] = $result;
        }

        foreach ($models as $model) {
            $key = (string) $model->{$this->model->getKey()};
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, null);
            }
        }
    }
}