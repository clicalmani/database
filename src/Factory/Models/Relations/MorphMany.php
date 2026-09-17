<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Core\Collection\Collection;
use Clicalmani\Core\Support\Facades\Str;
use Override;

class MorphMany extends Relationship
{
    private Elegant $related;

    /**
     * @param Elegant $model        The parent model (e.g., Post)
     * @param class-string<Elegant> $relatedClass  The child model (e.g., Comment)
     * @param string $name          Relationship name (e.g., 'commentable')
     * @param string $idKey         [Optional] ID key (e.g., 'commentable_id')
     * @param string $typeKey       [Optional] Type key (e.g., 'commentable_type')
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

    public function get(?string $fields = '*'): mixed
    {
        // Filters 
        // 1. commentable_id = ID
        // 2. commentable_type = Post::class
        $this->query->where("{$this->idKey} = ?", [$this->model->getKey()->scalarValue()]);
        $this->query->where("{$this->typeKey} = ?", [$this->model::class]);

        $this->result = $this->related->get($fields);

        return $this->result;
    }

    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->model->getKey()->scalarName());
    }

    public function getEager(array $keys): Collection
    {
        if (empty($keys)) {
            return collect();
        }
        
        return $this->relatedClass::select()                                // SELECT comments.*
            ->whereIn($this->idKey, $keys)                                  // commentable_id IN (IDs)
            ->andWhere($this->typeKey . ' = ?', [$this->model::class])      // commentable_type = Post::class
            ->get();                                                      
    }

    public function match(array $models, Collection $results, string $relation): void
    {
        $dictionary = [];
        
        /**
         * Key-Data mapping for easy access
         * @var Elegant
         */
        foreach ($results as $result) {
            $key = (string) $result->{$this->idKey};
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $result;
        }

        foreach ($models as $model) {
            $key = (string) $model->{$this->model->getKey()->scalarName()};
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, collect());
            }
        }
    }
}