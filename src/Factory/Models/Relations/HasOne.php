<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class HasOne extends Relationship
{
    private Elegant $related;
    
    /**
     * @param Elegant $model          Current parent model (e.g., User)
     * @param class-string<Elegant>   $relatedClass  Child model class (e.g., Profile)
     * @param string|null $foreignKey Foreign key in child table (e.g., user_id)
     * @param string|null $localKey   Parent local key (e.g., id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
        $this->related = new $this->relatedClass;
        $this->query   = $this->related->newQuery();

        // Default: user_id if $foreignKey is not specified
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->localKey   = $localKey ?: $this->model->getKey();
    }

    /**
     * Retrieve unique child
     * 
     * @return mixed
     */
    public function get(?string $fields = '*'): mixed
    {
        // 1. Filter: SELECT * FROM profiles WHERE user_id = ?
        $this->query->where("{$this->foreignKey} = ?", [$this->model->{$this->localKey}]);

        $this->result = $this->related->top(1)->get($fields)->first(); // Parent model (e.g., User)

        return $this->result;
    }

    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->localKey);
    }

    public function getEager(array $keys): CollectionInterface
    {
        if (empty($keys)) {
            return new Collection();
        }
        
        // Filter: SELECT * FROM profiles WHERE user_id IN (IDs from User)
        return $this->relatedClass::whereIn($this->foreignKey, $keys)->get();
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];

        /**
         * Key-Data mapping for easy access
         * @var Elegant (e.g., Profile)
         */
        foreach ($results as $row) {
            $dictionary[$row->{$this->foreignKey}] = $row; // [$profile->user_id => $profile]
        }

        foreach ($models as $model) {
            $parentId = $model->{$this->model->getKey()}; // e.g., $parentId = $user->id
            $childRow = $dictionary[$parentId] ?? null;   // e.g., $dictionay[$user->id] = $profile
            $model->setRelation($relation, $childRow);    // e.g., $user->profile 
        }
    }
}