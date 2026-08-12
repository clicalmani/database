<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;

class BelongsTo extends Relationship
{
    private Elegant $parent;

    /**
     * @param Elegant $model          Child model (e.g., Post)
     * @param class-string<Elegant>   $parentClass    Parent model class (e.g., User)
     * @param string|null $foreignKey The foreign in the child table (e.g., user_id)
     * @param string|null $ownerKey   Parent primary key (e.g., id or post_id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $parentClass,
        protected ?string $foreignKey = null, 
        protected ?string $ownerKey = null
    ) {
        $this->parent = new $parentClass;
        $this->query  = $this->parent->newQuery();
        
        $this->foreignKey = $foreignKey ?: Str::singularize($this->parent->getTable()->name()) . '_id';
        $this->ownerKey   = $ownerKey ?: $this->parent->getKey()->scalarName();
    }

    /**
     * Retrieve the parent model
     * 
     * @return mixed
     */
    public function get(?string $fields = '*'): mixed
    {
        // We retrieve the foreign key value from the model
        $idToFind = $this->model->{$this->foreignKey};
        
        if (!$idToFind) {
            return null;
        }

        $this->result = $this->parentClass::where("{$this->ownerKey} = ?", [$idToFind])
                            ->get($fields)
                            ->first();
        
        return $this->result;
    }
    
    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->foreignKey);
    }

    public function getEager(array $keys): CollectionInterface
    {
        if (empty($keys)) {
            return new Collection();
        }
        
        return $this->parentClass::whereIn($this->ownerKey, $keys)->get();
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];

        foreach ($results as $row) {
            $dictionary[$row->{$this->parent->getKey()->scalarName()}] = $row;
        }
        
        foreach ($models as $model) {
            $parentId = $model->{$this->foreignKey};
            $parentRow = $dictionary[$parentId] ?? null;
            $model->setRelation($relation, $parentRow);
        }
    }
}