<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Factory\Models\Table;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class MorphToMany extends Relationship
{
    private Elegant $related;
    private string $tablePrefix = '';
    private string $morphType;
    private Elegant $pivotModel;
    private string $table = '';
    private string $tableAlias = '';

    /**
     * @param Elegant $model        The parent model (e.g., Post)
     * @param class-string<Elegant> $relatedClass  The target model (e.g., Comment)
     * @param string $name                 The relationship name (e.g., 'commentable')
     * @param string $pivotClass           The pivot class name (e.g., 'Commentable')
     * @param string $morphKey             Key pointing to the parent (e.g., 'commentable_id')
     * @param string $foreignKey           Key pointing to the target (e.g., 'comment_id')
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected string $name,
        protected ?string $pivotClass = null,
        protected ?string $morphKey = null,
        protected ?string $foreignKey = null
    ) {
        $this->pivotClass = $pivotClass ?: Str::pluralize($name);
        $this->pivotModel = new $pivotClass;

        $this->related = new $relatedClass;
        $this->query   = $this->related->newQuery();

        $this->table      = $this->pivotModel->getTable()->withAlias();
        $this->tableAlias = $this->pivotModel->getTable()->alias();
        $this->morphKey   = $this->morphKey   ?: $this->name . '_id';
        $this->morphType  = $this->name . '_type';
        $this->foreignKey = $this->foreignKey ?: Str::singularize($this->related->getTable()->name()) . '_id'; // e.g., comment_id

        $this->tablePrefix = DB::getPrefix(); // Database table prefix (Optional)
    }

    public function get(?string $fields = '*'): mixed
    {
        // Select target table column only
        $this->query->selectRaw($this->related->getTable()->alias() . '.*');
        
        // Join : comments.id = commentables.comment_id
        $this->query->joinInner(
            $this->table, 
            $this->related->getKey()->scalarName(true), 
            "{$this->tableAlias}.{$this->foreignKey}"
        );

        // Filters:
        // 1. Parent ID (e.g., posts.id) commentables.commentable_id = posts.id
        $this->query->where("{$this->tableAlias}.{$this->morphKey} = ?", [$this->model->getKey()->scalarValue()]);
        
        // 2. Parent type (e.g., 'App\Models\Post')
        // e.g., commentables.commentable_type = 'App\Models\Post'
        $this->query->where("{$this->tableAlias}.{$this->morphType} = ?", [$this->model::class]);

        $this->result = $this->related->get($fields);

        return $this->result;
    }

    public function getParentKeys(array $models): array
    {
        return $this->getModelKeys($models, $this->model->getKey()->scalarName());
    }

    public function getEager(array $keys): CollectionInterface
    {
        if (empty($keys)) {
            return collect();
        }
        
        return $this->relatedClass::select()                                                                  // SELECT comments.* FROM comments
            ->whereIn("{$this->pivotModel->getTable()->alias()}.{$this->morphKey}", $keys)                          // commentables.commentable_id IN (IDs)
            ->andWhere("{$this->pivotModel->getTable()->alias()}.{$this->morphType} = ?", [$this->model::class])    // commentables.commentable_type = 'App\Models\Post'
            ->innerJoin($this->pivotClass,                                                                         // Join commentables
                "{$this->related->getTable()->alias()}.{$this->related->getKey()->scalarName()}",             // comments.id                                                   // comments.id
                "{$this->pivotModel->getTable()->alias()}.{$this->foreignKey}"                                      // commentables.comment_id
            )
            ->get("{$this->related->getTable()->alias()}.*, {$this->pivotModel->getTable()->alias()}.{$this->morphKey}");                               
    }

    public function match(array $models, CollectionInterface $results, string $relation): void
    {
        $dictionary = [];
        
        /** @var Elegant $result Target model (e.g., Comment) */
        foreach ($results as $result) {
            // Retrieve morphKey from the pivot 
            // Otherwise retrieve it from the hydrated data
            $pivotData = $result->getPivot() ?? [];
            $key = isset($pivotData[$this->morphKey]) ? $pivotData[$this->morphKey]: $result->{$this->morphKey};
            
            if ($key) {
                if (!isset($dictionary[$key])) {
                    $dictionary[$key] = [];
                }
                $dictionary[$key][] = $result;
            }
        }
        
        foreach ($models as $model) {
            $key = (string) $model->getKey()->scalarValue();
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, collect());
            }
        }
    }

    /**
     * Attach a model
     * 
     * @param string|array $id
     * @param ?array $attributes
     * @return bool
     */
    public function attach(string|array $id, ?array $attributes = []): bool
    {
        $ids = is_array($id) ? $id : [$id];
        $success = true;

        $morphType = $this->name . '_type';

        foreach ($ids as $currentId) {
            $data = [
                $this->morphKey => $this->model->getKey()->scalarValue(),
                $this->foreignKey => $currentId,
                $morphType => $this->model::class
            ];

            $insertData = array_merge($data, $attributes);

            try {
                $success = DB::table($this->table)->insert($insertData)->exec() === 'success';
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Detache a model
     * 
     * @param string|array|null $id
     * @return bool
     */
    public function detach(string|array|null $id = null): bool
    {
        $ids = is_array($id) ? $id : [$id];

        try {
            return DB::table($this->table)->delete()
                        ->where("{$this->morphKey} = ? AND {$this->morphType} = ?", [
                            $this->model->getKey()->scalarValue(),
                            $this->model::class
                        ])
                        ->whereIn($this->foreignKey, $ids)
                        ->exec()
                        ->status() === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Synchronize relationships
     * 
     * @param array $ids
     * @return array
     */
    public function sync(array $ids): array
    {
        $changes = ['attached' => [], 'detached' => [], 'updated' => []];

        $current = [];

        $results = DB::table($this->table)->where("{$this->morphKey} = ? AND {$this->morphType} = ?", [
                        $this->model->getKey()->scalarValue(),
                        $this->model::class
                    ])->get($this->foreignKey);
        
        foreach ($results as $row) {
            $current[] = (int) $row->{$this->foreignKey};
        }

        $detach = array_diff($current, $ids);
        if (!empty($detach)) {
            $this->detach($detach);
            $changes['detached'] = array_values($detach);
        }

        $attach = array_diff($ids, $current);
        if (!empty($attach)) {
            $this->attach($attach);
            $changes['attached'] = array_values($attach);
        }

        return $changes;
    }
}