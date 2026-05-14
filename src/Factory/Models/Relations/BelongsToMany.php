<?php

namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class BelongsToMany extends Relationship
{
    protected array $pivotColumns = []; // Stores additional pivot columns

    /**
     * @param Elegant $model          The current model (e.g., User)
     * @param string $relatedClass    The target model (e.g., Role)
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
        $related = new $this->relatedClass;

        // 1. Deduce the pivot table name (alphabetical order by convention)
        if (!$this->table) {
            $tables = [$this->model->getTable(), $related->getTable()];
            sort($tables);
            $this->table = Str::singularize($tables[0]) . '_' . Str::singularize($tables[1]);
        }

        // 2. Deduce the keys
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->relatedKey = $relatedKey ?: Str::singularize($related->getTable()) . '_id';
    }

    public function get(): mixed
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $related = new $this->relatedClass;
        $query = $related->newQuery();
        
        /** @var string */
        $tablePrefix = DB::getPrefix();

        // 1. Target table columns (e.g., roles.*)
        $select = [$related->getTableAlias() . '.*'];

        // 2. Add pivot columns with a prefix to avoid collisions
        foreach ($this->pivotColumns as $column) {
            $select[] = "{$tablePrefix}.{$this->table}.{$column} AS pivot_{$column}";
        }

        $query->selectRaw(implode(', ', $select));

        // Join: roles.id = role_user.role_id
        $query->joinInner(
            $this->table,
            "{$tablePrefix}.{$this->table}.{$this->relatedKey}",
            $related->getKey(true)
        );

        // Filter: role_user.user_id = Current user ID
        $query->where("{$tablePrefix}.{$this->table}.{$this->foreignKey} = ?", [$this->model->{$this->model->getKey()}]);

        $this->result = $related->fetch($this->relatedClass);

        return $this->result;
    }

    /**
     * Define the pivot table columns to retrieve.
     * 
     * @param array $columns
     * @return $this
     */
    public function withPivot(array $columns): self
    {
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);
        return $this;
    }

    /**
     * Attach a model (or a list of IDs) to the current model in the pivot table.
     * 
     * @param int|array $id          ID or array of IDs to attach
     * @param array $attributes      Additional columns for the pivot table
     * @return bool
     */
    public function attach(mixed $id, array $attributes = []): bool
    {
        $ids = is_array($id) ? $id : [$id];
        $success = true;

        foreach ($ids as $currentId) {
            // Prepare base data (foreign keys)
            $data = [
                $this->foreignKey => $this->model->{$this->model->getKey()},
                $this->relatedKey => $currentId
            ];

            // Merge with additional attributes (e.g., ['status' => 'active'])
            $insertData = array_merge($data, $attributes);

            // Insert via the DB manager
            // DB::table($this->table)->insert($insertData);
            try {
                $fields = implode(', ', array_keys($insertData));
                $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
                
                $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
                
                DB::statement($sql, array_values($insertData));
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Detach one or more models from the current model in the pivot table.
     * 
     * @param int|array|null $id ID or array of IDs to detach. If null, detaches everything.
     * @return bool
     */
    public function detach(mixed $id = null): bool
    {
        $query = "DELETE FROM {$this->table} WHERE {$this->foreignKey} = ?";
        $params = [$this->model->{$this->model->getKey()}];

        if ($id !== null) {
            if (is_array($id)) {
                // Handle an array of IDs (WHERE IN)
                $placeholders = implode(', ', array_fill(0, count($id), '?'));
                $query .= " AND {$this->relatedKey} IN ($placeholders)";
                $params = array_merge($params, $id);
            } else {
                // Handle a single ID
                $query .= " AND {$this->relatedKey} = ?";
                $params[] = $id;
            }
        }

        try {
            return DB::statement($query, $params);
        } catch (\Exception $e) {
            // Log error if necessary
            return false;
        }
    }

    /**
     * Synchronize the pivot table with a list of IDs.
     * 
     * @param array $ids List of target IDs (e.g., [1, 2, 5])
     * @return array A summary of the changes made
     */
    public function sync(array $ids): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated'  => []
        ];

        // 1. Retrieve the IDs currently present in the pivot table for this model
        $current = [];
        $sql = "SELECT {$this->relatedKey} FROM {$this->table} WHERE {$this->foreignKey} = ?";
        $results = DB::select($sql, [$this->model->{$this->model->getKey()}]);
        
        foreach ($results as $row) {
            $current[] = (int)$row->{$this->relatedKey};
        }

        // 2. Calculate IDs to detach (present in DB but not in the new list)
        $detach = array_diff($current, $ids);
        if (!empty($detach)) {
            $this->detach($detach);
            $changes['detached'] = array_values($detach);
        }

        // 3. Calculate IDs to attach (present in the list but not in DB)
        $attach = array_diff($ids, $current);
        if (!empty($attach)) {
            $this->attach($attach);
            $changes['attached'] = array_values($attach);
        }

        return $changes;
    }
}