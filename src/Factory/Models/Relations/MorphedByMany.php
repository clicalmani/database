<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Core\Collection\Collection;
use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Core\Support\Facades\Str;

class MorphedByMany extends Relationship
{
    private Elegant $parent;
    private string $morphType;

    /**
     * @param Elegant $model        Child model (e.g., Comment)
     * @param class-string<Elegant> $parentClass   Target parent model (e.g., Post)
     * @param string $name          Relation name (ex: 'commentable')
     * @param string $table         Pivot table name (e.g, 'commentables')
     * @param string $foreignKey    Key targeting the child model (e.g, 'comment_id')
     * @param string $morphKey      Key targeting the parent model (e.g, 'commentable_id')
     */
    public function __construct(
        protected Elegant $model,
        protected string $parentClass,
        protected string $name,
        protected ?string $table = null,
        protected ?string $foreignKey = null,
        protected ?string $morphKey = null
    ) {
        $this->table  = $table ?: Str::pluralize($name);
        $this->parent = new $this->parentClass;
        $this->query  = $this->parent->newQuery();

        // Guess the key from child table name if not specified (e.g., comment_id)
        $this->foreignKey = $this->foreignKey ?: Str::singularize($this->model->getTable()->name()) . '_id';
        $this->morphKey   = $this->morphKey   ?: $this->name . '_id'; // e.g., commentable_id
        $this->morphType  = $this->name . '_type';                    // e.g., commentable_type
    }

    public function get(?string $fields = '*'): mixed
    {
        /** @var string */
        $tablePrefix = DB::getPrefix();

        // Retrieve only parent columns
        $this->query->selectRaw($this->parent->getTable()->alias() . '.*');
        
        // Join to the pivot table (e.g., posts.id = commentables.commentable_id)
        $this->query->joinInner($this->table, $this->parent->getKey()->scalarName(true), "{$tablePrefix}{$this->table}.{$this->morphKey}");

        // Filters: 
        // 1. Link the current model ID (child)
        // 2. Filter by morph type (parent class e.g., Post class)
        $this->query->where("{$tablePrefix}{$this->table}.{$this->foreignKey} = ?", [$this->model->getKey()->scalarValue()]); // e.g., commentables.comment_id = ID
        $this->query->where("{$tablePrefix}{$this->table}.{$this->morphType} = ?", [$this->parentClass]);                      // e.g., commentables.commentable_type = Post::class

        $this->result = $this->parent->get($fields);

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
        $morphType = $this->name . '_type';

        return $this->parentClass::select()                                                 // SELECT * FROM commentables
            ->whereIn("{$tablePrefix}{$this->table}.{$this->foreignKey}", $keys)            // commentables.comment_id IN (IDs)
            ->where("{$tablePrefix}{$this->table}.{$morphType} = ?", [$this->parentClass])  // commentables.commentable_type = Post::class
            ->join(fn($join) => 
                $join->inner()
                    ->to($this->table) // Join posts
                    ->on("{$this->parent->getKey()->scalarName(true)} = {$tablePrefix}{$this->table}.{$this->morphKey}") // commentables.commentable_id = posts.id
            )->get();         
    }
    
    public function match(array $models, Collection $results, string $relation): void
    {
        $dictionary = [];
        
        /**
         * Key-Data mapping for easy access
         * @var Elegant
         */
        foreach ($results as $result) { // Employee
            // We retrieve the foreign key from the pivot if specified
            // otherwise we retrieve it from hydration
            $pivotData = $result->getPivot() ?? [];
            $key = (string) ($pivotData[$this->foreignKey] ?? $result->{$this->foreignKey} ?? null);
            
            if ($key) {
                if (!isset($dictionary[$key])) {
                    $dictionary[$key] = [];
                }
                $dictionary[$key][] = $result;
            }
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