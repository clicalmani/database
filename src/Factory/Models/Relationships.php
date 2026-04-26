<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Clicalmani\Database\Factory\Models\Relations\HasMany;
use Clicalmani\Database\Factory\Models\Relations\HasOne;
use Clicalmani\Database\Factory\Models\Relations\MorphMany;
use Clicalmani\Database\Factory\Models\Relations\MorphTo;
use Clicalmani\Database\Factory\Models\Relations\MorphToMany;
use Clicalmani\Database\Interfaces\JoinClauseInterface;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;

trait Relationships
{
    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent.
     * 
     * @param string $class Parent model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $original_key [Optional] Original key
     * @return mixed
     */
    protected function belongsTo(string $class, ?string $foreign_key = null, ?string $original_key = null): mixed
    {
        return (new BelongsTo($this::class, $class, ...$this->guessRelationshipKeys($foreign_key, $original_key, $class)))->get($this->id);
    }

    /**
     * One and one relationship
     * 
     * @param string $class Parent model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $original_key [Optional] original key
     * @return mixed
     */
    protected function hasOne(string $class, ?string $foreign_key = null, ?string $original_key = null): mixed
    {
        if ($this::class === $class) {
            $key = $foreign_key ?? Str::singularize($this->getTable()) . '_id';
            return self::find($this->{$key});
        }
        
        return (new HasOne($class, $this::class, ...$this->guessRelationshipKeys($foreign_key, $original_key)))->get($this->id);
    }

    /**
     * One to many relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $original_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    protected function hasMany(string $class, ?string $foreign_key = null, ?string $original_key = null): \Clicalmani\Foundation\Collection\CollectionInterface
    {
        return (new HasMany($class, $this::class, ...$this->guessRelationshipKeys($foreign_key, $original_key)))->get($this->id);
    }

    /**
     * Many to many relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    protected function belongsToMany(string $class, ?string $foreign_key = null, ?string $parent_key = null) : CollectionInterface
    {
        if ( $this->isEmpty() ) return collection();

        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key);

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $class)->getTable(true), function(JoinClauseInterface $join) use ($foreign_key, $parent_key) {
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class);
    }

    /**
     * Has one through relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return mixed
     */
    protected function hasOneThrough(string $class, string $pivot_class, ?string $foreign_key = null, ?string $parent_key = null, ?string $pivot_foreign_key = null, ?string $pivot_parent_key = null) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key, $pivot_class); 
        [$pivot_foreign_key, $pivot_parent_key] = $this->guessRelationshipKeys($pivot_foreign_key, $pivot_parent_key); 

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $pivot_class)->getTable(true), function($join) use ($pivot_foreign_key, $pivot_parent_key) { 
            $join->right()->on("$pivot_foreign_key=$pivot_parent_key");
        });

        $this->query->join((new $class)->getTable(true), function($join) use ($foreign_key, $parent_key) { 
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class)->first();
    }

    /**
     * Has many through relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    protected function hasManyThrough(string $class, string $pivot_class, ?string $foreign_key = null, ?string $parent_key = null, ?string $pivot_foreign_key = null, ?string $pivot_parent_key = null) : CollectionInterface
    {
        if ( $this->isEmpty() ) return collection();
        
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key, $pivot_class); 
        [$pivot_foreign_key, $pivot_parent_key] = $this->guessRelationshipKeys($pivot_foreign_key, $pivot_parent_key); 

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $pivot_class)->getTable(true), function($join) use ($pivot_foreign_key, $pivot_parent_key) { 
            $join->right()->on("$pivot_foreign_key=$pivot_parent_key");
        });

        $this->query->join((new $class)->getTable(true), function($join) use ($foreign_key, $parent_key) { 
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class);
    }

    /**
     * Polymorphic relationship
     * 
     * @param ?string $pivot_key
     * @return ?self
     */
    protected function morphTo(?string $pivot_key = null): ?self
    {
        return (new MorphTo($this::class, $pivot_key))->get($this->id);
    }

    /**
     * One-to-One morphic relationship
     * 
     * @param string $class Child model
     * @param string $morphic Morphic association
     * @return mixed
     */
    protected function morphOne(string $class, string $morphic) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        $file = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[0]['file'];

        $this->query->set('tables', [(new $class)->getTable(true)]);
        $this->query->where("{$morphic}_id = ? AND {$morphic}_type = ? ", [$this->id, strtolower(pathinfo($file, PATHINFO_FILENAME))]);
        
        return $this->fetch($class)->first();
    }

    /**
     * One-to-Many morphic relationship
     * 
     * @param string $class Child model
     * @param string $name Morphic association
     * @param ?string $pivot_id
     * @param ?string $pivot_type
     * @param ?string $parent_type
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    protected function morphMany(string $class, string $name, ?string $pivot_id = null, ?string $pivot_type = null, ?string $parent_type = null): CollectionInterface
    {
        return (new MorphMany(
            $class, $name, 
            $pivot_id, 
            $pivot_type, 
            $parent_type)
        )->get($this->id);
    }

    /**
     * Many-to-Many polymorphic relationship
     * 
     * @param string $related_class Related model class
     * @param string $name Morphic association name (e.g., 'taggable')
     * @param string|null $pivot_table [Optional] Pivot table name (default: plural of the morphic association, e.g., 'taggables')
     * @param string|null $foreign_pivot_key [Optional] Foreign key in the pivot table pointing to the current model (default e.g., 'tag_id' if the current model is 'Tag')
     * @param string|null $parent_pivot_id [Optional] Foreign key in the pivot table pointing to the parent model (default e.g., 'taggable_id' if the morphic association is 'taggable')
     * @param string|null $parent_pivot_type [Optional] Morph type column in the pivot table (default e.g., 'taggable_type' if the morphic association is 'taggable')
     * @param string|null $parent_type [Optional] The value to match in the morph type column for the parent model (default: singular of the parent model's table, e.g., 'post' if the parent model is 'Post')
     * @param string|null $parent_class [Optional] The parent model class (default: the current model class)
     * @return array
     */
    protected function morphToMany(
        string $related_class, 
        string $name, 
        ?string $pivot_table = null, 
        ?string $foreign_pivot_key = null, 
        ?string $parent_pivot_id = null, 
        ?string $parent_pivot_type = null, 
        ?string $parent_type = null, 
        ?string $parent_class = null
    ): array
    {
        return (new MorphToMany(
            $related_class, 
            $name, 
            $pivot_table, 
            $foreign_pivot_key, 
            $parent_pivot_id, 
            $parent_pivot_type, 
            $parent_type, 
            $parent_class ?? $this::class
        ))->get($this->id);
    }

    /**
     * Inverse of morphToMany relationship
     * 
     * @param string $parent_class Parent model class
     * @param string $name Morphic association name (e.g., 'holidayable')
      * @param string|null $pivot_table [Optional] Pivot table name (default: plural of the morphic association, e.g., 'holidayables')
     * @param string|null $foreign_pivot_key [Optional] Foreign key in the pivot table pointing to the current model (default e.g., 'holiday_id' if the current model is 'Holiday')
     * @param string|null $parent_pivot_id [Optional] Foreign key in the pivot table pointing to the parent model (default e.g., 'holidayable_id' if the morphic association is 'holidayable')
     * @param string|null $parent_pivot_type [Optional] Morph type column in the pivot table (default e.g., 'holidayable_type' if the morphic association is 'holidayable')
     * @param string|null $parent_type [Optional] The value to match in the morph type column for the parent model (default: singular of the parent model's table, e.g., 'department' if the parent model is 'Department')
     * @return array
     */
    protected function morphedByMany(
        string $parent_class, 
        string $name, 
        ?string $pivot_table = null, 
        ?string $foreign_pivot_key = null, 
        ?string $parent_pivot_id = null, 
        ?string $parent_pivot_type = null, 
        ?string $parent_type = null
    ): array
    {
        return $this->morphToMany(
            $this::class, 
            $name, 
            $pivot_table, 
            $foreign_pivot_key, 
            $parent_pivot_id, 
            $parent_pivot_type, 
            $parent_type, 
            $parent_class
        );
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Pivot table foreign key
     * @param ?string $parent_key [Optional] Pivot table original key
     * @param string $direction [Optional] Join direction
     * @return static
     */
    private function __pivot(string $class, ?string $foreign_key = null, ?string $parent_key = null, ?string $direction = 'left') : static
    {
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key);

        $this->query->join((new $class)->getTable(true), function(JoinClauseInterface $join) use ($foreign_key, $parent_key, $direction) {
            if ($direction !== 'cross') $join->{$direction}()->on("$foreign_key=$parent_key");
            else $join->{$direction}();
        });

        return $this;
    }

    public function pivotRight(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'right');
    }

    public function pivotLeft(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'left');
    }

    public function pivotInner(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'inner');
    }

    public function pivotOuter(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'outer');
    }

    public function pivotCross(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'cross');
    }
}