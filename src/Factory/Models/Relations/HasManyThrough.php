<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;

class HasManyThrough extends Relationship
{
    private Elegant $farModel;
    private Elegant $through;

    /**
     * @param Elegant $model              Current model (e.g., Project)
     * @param class-string<Elegant>       $farModelClass    Target model (e.g., User)
     * @param string $throughModelClass   Through model (e.g., Task)
     * @param string|null $firstKey       Foreign key on through model (e.g., project_id)
     * @param string|null $secondKey      Foreign key on far model (e.g., task_id)
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
        $this->farModel = new $farModelClass;
        $this->through  = new $this->throughModelClass;
        $this->query    = $this->farModel->newQuery();
        
        $this->firstKey  = $firstKey ?: Str::singularize($this->model->getTable()->name()) . '_id';
        $this->secondKey = $secondKey ?: Str::singularize($this->through->getTable()->name()) . '_id';
        $this->localKey  = $localKey ?: $this->model->getKey()->scalarName();
        $this->secondLocalKey = $secondLocalKey ?: $this->through->getKey()->scalarName();
    }

    public function get(?string $fields = '*'): mixed
    {
        // 1. Select target columns (e.g., User)
        $this->query->selectRaw($this->farModel->getTable()->name() . '.*');

        // 2. Join : (e.g., users.task_id = tasks.id)
        $this->query->joinInner(
            $this->through->getTable()->withAlias(),
            "{$this->farModel->getTable()->alias()}.{$this->secondKey}",
            "{$this->through->getTable()->alias()}.{$this->secondLocalKey}"
        );
        
        // 3. Filter : (e.g., tasks.project_id = project.id)
        $this->query->where("{$this->through->getTable()->alias()}.{$this->firstKey} = ?", [$this->model->{$this->localKey}]);
        
        // 4. Complete collection
        $this->result = $this->farModel->get($fields);

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
        
        return $this->farModelClass::select()
            ->whereIn("{$this->through->getTable()->alias()}.{$this->firstKey}", $keys) // Filter: (e.g., tasks.project_id IN (retrieved ids))
            ->join(fn($join) =>
                $join->inner()
                    ->to($this->through->getTable()->withAlias()) 
                    ->on("{$this->farModel->getTable()->alias()}.{$this->secondKey} = {$this->through->getTable()->alias()}.{$this->secondLocalKey}")                                                        // Join: (e.g., users.task_id = tasks.id)
            )->get();
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];
        
        /**
         * Key-Data mapping for easy access
         * @var Elegant (e.g., User)
         */
        foreach ($results as $result) {
            // We use through model for mapping
            // SELECT * FROM tasks WHERE id = ? [task_id in User model]
            $row = DB::table($this->through->getTable()->name())->where($this->secondLocalKey . ' = ?', [$result->{$this->secondKey}])->first();
            
            if ($row) {
                if (!isset($dictionary[$row->{$this->firstKey}])) { // [$row->project_id => []]
                    $dictionary[$row->{$this->firstKey}] = [];
                }
                $dictionary[$row->{$this->firstKey}][] = $result;
            }
        }

        foreach ($models as $model) {
            $key = (string) $model->{$this->localKey}; // e.g., $project->id
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, collect());
            }
        }
    }
}