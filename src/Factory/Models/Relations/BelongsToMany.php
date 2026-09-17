<?php

namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Core\Collection\Collection;
use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Core\Support\Facades\Str;
use Override;

class BelongsToMany extends Relationship
{
    protected array $pivotColumns = []; // Stores additional pivot columns

    private Elegant $related;

    /**
     * @param Elegant $model          The current model (e.g., User)
     * @param class-string<Elegant>   $relatedClass    The target model (e.g., Role)
     * @param string|null $table      The pivot table (e.g., role_user)
     * @param string|null $foreignKey The current model's foreign key in the pivot (e.g., user_id)
     * @param string|null $relatedKey The target model's foreign key in the pivot (e.g., role_id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected ?string $table = null,
        protected ?string $foreignKey = null,
        protected ?string $relatedKey = null
    ) {
        $this->related = new $this->relatedClass;
        $this->query   = $this->related->newQuery();

        // 1. Deduce the pivot table name (alphabetical order by convention)
        if (!$this->table) {
            $tables = [$this->model->getTable(), $this->related->getTable()->name()];
            sort($tables);
            $this->table = Str::singularize($tables[0]) . '_' . Str::singularize($tables[1]);
        }

        // 2. Deduce the keys
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()->name()) . '_id';
        $this->relatedKey = $relatedKey ?: Str::singularize($this->related->getTable()->name()) . '_id';
    }

    public function get(?string $fields = '*'): mixed
    {
        $tablePrefix = DB::getPrefix();
        $fullPivotTable = $tablePrefix . $this->table;

        // 1. Target table columns (e.g., roles.*)
        $select = [$this->related->getTable()->alias() . '.*'];

        // 2. Add pivot foreign key and additional pivot columns
        $select[] = "{$fullPivotTable}.{$this->foreignKey} AS pivot_{$this->foreignKey}";
        $select[] = "{$fullPivotTable}.{$this->relatedKey} AS pivot_{$this->relatedKey}";

        foreach ($this->pivotColumns as $column) {
            $select[] = "{$fullPivotTable}.{$column} AS pivot_{$column}";
        }

        $this->query->selectRaw(implode(', ', $select));

        // Join: roles.id = role_user.role_id
        $this->query->joinInner(
            $this->table,
            "{$fullPivotTable}.{$this->relatedKey}",
            $this->related->getKey()->scalarName(true)
        );

        // Filter: role_user.user_id = Current user ID
        $this->query->where("{$fullPivotTable}.{$this->foreignKey} = ?", [$this->model->getKey()->scalarValue()]);

        $this->result = $this->related->get();

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
        
        $tablePrefix = DB::getPrefix();
        $fullPivotTable = $tablePrefix . $this->table;

        $select = [$this->related->getTable()->alias() . '.*'];

        // Ensure foreign keys are present in pivot data for matching
        $select[] = "{$fullPivotTable}.{$this->foreignKey} AS pivot_{$this->foreignKey}";
        $select[] = "{$fullPivotTable}.{$this->relatedKey} AS pivot_{$this->relatedKey}";

        foreach ($this->pivotColumns as $column) {
            $select[] = "{$fullPivotTable}.{$column} AS pivot_{$column}";
        }

        return $this->relatedClass::select()
            ->whereIn("{$fullPivotTable}.{$this->foreignKey}", $keys)
            ->join(fn($join) => 
                $join->inner()
                    ->to($this->table)
                    ->on("{$fullPivotTable}.{$this->relatedKey} = {$this->related->getKey()->scalarName(true)}")
            )
            ->get(implode(', ', $select));
    }

    public function match(array $models, Collection $results, string $relation): void
    {
        $dictionary = [];
        
        /** @var Elegant $result */
        foreach ($results as $result) {
            $pivotData = $result->getPivot() ?? [];
            $key = (string) ($pivotData[$this->foreignKey] ?? null);
            
            if ($key !== null && $key !== '') {
                if (!isset($dictionary[$key])) {
                    $dictionary[$key] = [];
                }
                $dictionary[$key][] = $result;
            }
        }
        
        foreach ($models as $model) {
            $key = (string) $model->{$this->model->getKey()->scalarName()};
            
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, collect($dictionary[$key]));
            } else {
                $model->setRelation($relation, collect());
            }
        }
    }

    /**
     * Define the pivot table columns to retrieve.
     */
    public function withPivot(array $columns): self
    {
        $this->pivotColumns = array_unique(array_merge($this->pivotColumns, $columns));
        return $this;
    }

    /**
     * Attach a model (or a list of IDs) to the current model in the pivot table.
     */
    public function attach(mixed $id, array $attributes = []): bool
    {
        $ids = is_array($id) ? $id : [$id];
        $success = true;
        $fullPivotTable = DB::getPrefix() . $this->table;

        foreach ($ids as $currentId) {
            $data = [
                $this->foreignKey => $this->model->getKey()->scalarValue(),
                $this->relatedKey => $currentId
            ];

            $insertData = array_merge($data, $attributes);

            try {
                $success = DB::table($this->table)->insert($insertData)->exec()->status() === 'success';
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Detach one or more models from the current model in the pivot table.
     */
    public function detach(mixed $id = null): bool
    {
        try {
            $query = DB::table($this->table)->where("{$this->foreignKey} = ?", [$this->model->getKey()->scalarValue()]);

            if ($id !== null) {
                $query->whereIn($this->relatedKey, (array)$id);
            }
            
            return $query->delete()->exec()->status() === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Synchronize the pivot table with a list of IDs.
     */
    public function sync(array $ids): array
    {
        $results = DB::table($this->table)->selectRaw($this->relatedKey)
                        ->where("{$this->foreignKey} = ?", [$this->model->getKey()->scalarValue()])
                        ->get();
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated'  => []
        ];
        
        foreach ($results as $row) {
            $current[] = (int) $row->{$this->relatedKey};
        }

        $detach = array_diff($current, $ids);
        if (!empty($detach)) {
            $this->detach(array_values($detach));
            $changes['detached'] = array_values($detach);
        }

        $attach = array_diff($ids, $current);
        if (!empty($attach)) {
            $this->attach(array_values($attach));
            $changes['attached'] = array_values($attach);
        }

        return $changes;
    }
}