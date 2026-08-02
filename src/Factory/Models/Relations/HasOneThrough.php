<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;

class HasOneThrough extends Relationship
{
    private Elegant $through;
    private Elegant $farModel;

    /**
     * @param Elegant $model              Current model (e.g., User)
     * @param class-string<Elegant>       $farModelClass    Target model - far model (e.g., Log)
     * @param string $throughModelClass   Through model (e.g., Profile)
     * @param string|null $firstKey       Foreign key in through model (e.g., user_id)
     * @param string|null $secondKey      Foreign key in far model (e.g., profile_id)
     * @param string|null $localKey       Current model local key (e.g., id)
     * @param string|null $secondLocalKey Through model local key (e.g., id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $farModelClass,
        protected string $throughModelClass,
        protected ?string $firstKey = null,
        protected ?string $secondKey = null,
        protected ?string $localKey = null,
        protected ?string $secondLocalKey = null
    ) {
        $this->through  = new $this->throughModelClass;
        $this->farModel = new $this->farModelClass;
        $this->query    = $this->farModel->newQuery();
        
        $this->firstKey  = $firstKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->secondKey = $secondKey ?: Str::singularize($this->through->getTable()) . '_id';
        $this->localKey  = $localKey ?: $this->model->getKey();
        $this->secondLocalKey = $secondLocalKey ?: $this->through->getKey();
    }

    public function get(?string $fields = '*'): mixed
    {
        // 1. Select far model columns
        $this->query->selectRaw($this->farModel->getTableAlias() . '.*');

        // 2. Join through model: logs.profile_id = profiles.id
        $this->query->joinInner(
            $this->through->getTable(true),
            "{$this->farModel->getTableAlias()}.{$this->secondKey}",
            "{$this->through->getTableAlias()}.{$this->secondLocalKey}"
        );

        // 3. Filter for actual modal ID : profiles.user_id = user.id
        $this->query->where("{$this->through->getTableAlias()}.{$this->firstKey} = ?", [$this->model->{$this->localKey}]);

        // 4. Only one resut (HasONE)
        $this->result = $this->farModel->top(1)->get($fields)->first();

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
        
        // One request for all the parents: 
        return $this->farModelClass::select()                                       // SELECT logs.* FROM logs
            ->whereIn("{$this->through->getTableAlias()}.{$this->firstKey}", $keys) // e.g., profiles.user_id IN ()
            ->joinInner(
                $this->through->getTable(true),                                   // Join: profiles
                "{$this->farModel->getTableAlias()}.{$this->secondKey}",          // logs.profile_id = profiles.id
                "{$this->through->getTableAlias()}.{$this->secondLocalKey}"
            )->get(); 
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        // Build a dictionary [parent_id => far_model]
        $dictionary = [];
        
        foreach ($results as $result) { 
            // We use through model key to establish the relationship
            // firstKey is on the through model (e.g., profiles.user_id)
            // We must retrieve it through the join
            $key = (string) ($result->{$this->firstKey} ?? null); // e.g.,  [id => 1, user_id => 1] from hydration
            
            if ($key) {
                $dictionary[$key] = $result;
            }
        }

        // Assign each parent model
        foreach ($models as $model) {
            $key = (string) $model->{$this->localKey}; // e.g., user.id
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, null);
            }
        }
    }

    /**
     * Verify if a relation exists
     * @return bool
     */
    public function exists(): bool
    {
        return $this->get() !== null;
    }
}