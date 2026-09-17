<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Core\Collection\Collection;

class MorphTo extends Relationship
{
    private string $idKey;
    private string $typeKey;
    private string $parentId;
    private string $parentType;
    private Elegant $parentModel;

    /**
     * @param Elegant $model                 Model instance (e.g., Comment)
     * @param class-string<Elegant> $name    The relationship name (e.g., 'commentable')
     */
    public function __construct(
        protected Elegant $model,
        protected string $name
    ) {
        $this->idKey   = $this->name . '_id';
        $this->typeKey = $this->name . '_type';
        
        $this->parentId   = $this->model->{$this->idKey};       // e.g., 10
        $this->parentType = $this->model->{$this->typeKey};     // e.g., Post::class

        $this->parentModel = new $this->parentType;
        $this->query       = $this->parentModel->newQuery();
    }

    public function get(?string $fields = '*'): mixed
    {
        if (!$this->parentId || !$this->parentType) {
            return null;
        }

        $this->query->where($this->parentModel->getKey()->scalarName() . " = ?", [$this->parentId]); // SELECT * FROM posts WHERE posts.id = 10 LIMIT 1
        $this->result = $this->parentModel->top(1)->get($fields);

        return $this->result;
    }

    // For morphTo we must group by type
    // e.g., [Post::class => [3, 5, 7], Video::class => [2, 4, 6], ...]
    public function getParentKeys(array $models): array
    {
        $keysByType = [];
        
        foreach ($models as $model) {
            if (isset($this->parentType) && isset($this->parentId)) {
                if (!isset($keysByType[$this->parentType])) {
                    $keysByType[$this->parentType] = [];
                }
                $keysByType[$this->parentType][] = $this->parentId;
            }
        }
        
        return $keysByType;
    }

    public function getEager(array $keysByType): Collection
    {
        $results = collect();
        
        if (empty($keysByType)) {
            return $results;
        }
        
        /**
         * A distinct request for each type
         * @var class-string<Elegant> $type    e.g., App\Models\Post
         * @var string[] $keys  e.g., [3, 5, 7]
         */
        foreach ($keysByType as $type => $keys) {
            if (empty($keys) || !class_exists($type)) {
                continue;
            }

            /** @var Elegant */
            $model = new $type;
            /** @var Elegant[] type models */
            $typeResults = $type::whereIn($model->getKey()->scalarName(), $keys)->get();
            
            foreach ($typeResults as $result) {
                $results->add($result);
            }
        }

        return $results;
    }

    public function match(array $models, Collection $results, string $relation): void
    {
        // Group results by type and ID
        $dictionary = [];

        /** @var Elegant $result a model for each type e.g., Post, Video, ... */
        foreach ($results as $result) {
            $type = get_class($result);
            $id = $result->{$result->getKey()->scalarName()};
            
            if (!isset($dictionary[$type])) {
                $dictionary[$type] = [];
            }
            $dictionary[$type][$id] = $result;
        }

        // Assign to the models
        foreach ($models as $model) {
            if (isset($this->parentType) && isset($this->parentId)) {
                if (isset($dictionary[$this->parentType][$this->parentId])) {
                    $model->setRelation($relation, $dictionary[$this->parentType][$this->parentId]);
                } else {
                    $model->setRelation($relation, null);
                }
            } else {
                $model->setRelation($relation, null);
            }
        }
    }

    /**
     * Miscelinous method for morphTo
     * Allow to specify an explicit type
     * 
     * @param ModelInterface
     */
    public function associate(ModelInterface $model): self
    {
        $this->model->{$this->idKey} = $model->{$model->getKey()->scalarName()};
        $this->model->{$this->typeKey} = get_class($model);
        return $this;
    }
}