<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Clicalmani\Database\Factory\Models\Relations\BelongsToMany;
use Clicalmani\Database\Factory\Models\Relations\HasMany;
use Clicalmani\Database\Factory\Models\Relations\HasManyThrough;
use Clicalmani\Database\Factory\Models\Relations\HasOne;
use Clicalmani\Database\Factory\Models\Relations\HasOneThrough;
use Clicalmani\Database\Factory\Models\Relations\MorphByMany;
use Clicalmani\Database\Factory\Models\Relations\MorphedByMany;
use Clicalmani\Database\Factory\Models\Relations\MorphMany;
use Clicalmani\Database\Factory\Models\Relations\MorphOne;
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
     * @param string $parentClass Parent model
     * @param ?string $foreignKey
     * @param ?string $ownerKey 
     * @return BelongsTo
     */
    protected function belongsTo(string $parentClass, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        return new BelongsTo(
            $this, $parentClass, $foreignKey, $ownerKey
        );
    }

    /**
     * One and one relationship
     * 
     * @param string $relatedClass
     * @param ?string $foreignKey
     * @param ?string $localKey
     * @return HasOne
     */
    protected function hasOne(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        return new HasOne(
            $this, $relatedClass, $foreignKey, $localKey
        );
    }

    /**
     * One to many relationship
     * 
     * @param string< $class Child model
     * @param ?string $foreignKey [Optional] Table foreign key
     * @param ?string $localKey [Optional] Original key
     * @return HasMany
     */
    protected function hasMany(string $class, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this, $class, $foreignKey, $localKey);
    }

    /**
     * Many to many relationship
     * 
     * @param string $relatedClass Child model
     * @param ?string $foreignKey [Optional] Table foreign key
     * @param ?string $relatedKey [Optional] Original key
     * @return BelongsToMany
     */
    protected function belongsToMany(string $relatedClass, ?string $table = null, ?string $foreignKey = null, ?string $relatedKey = null): BelongsToMany
    {
        return new BelongsToMany(
            $this, relatedClass, $table, $foreignKey, $relatedKey
        );
    }

    /**
     * Has one through relationship
     * 
     * @param string $farModelClass Child model
     * @param string $throughModelClass [Optional] Foreign key
     * @param ?string $firstKey [Optional] Original key
     * @param ?string $secondKey
     * @param ?string $localKey
     * @param ?string $secondLocalKey
     * @return HasOneThrough
     */
    protected function hasOneThrough(string $farModelClass, string $throughModelClass, ?string $firstKey = null, ?string $secondKey = null, ?string $localKey = null, ?string $secondLocalKey = null): HasOneThrough
    {
        return new HasOneThrough(
            $this, $farModelClass, $throughModelClass, $firstKey, $secondKey, $localKey, $secondLocalKey
        );
    }

    /**
     * Has many through relationship
     * 
     * @param string $farModelClass Child model
     * @param string $throughModelClass [Optional] Foreign key
     * @param ?string $firstKey [Optional] Original key
     * @param ?string $secondKey
     * @param ?string $localKey
     * @param ?string $secondLocalKey
     * @return HasManyThrough
     */
    protected function hasManyThrough(string $farModelClass, string $throughModelClass, ?string $firstKey = null, ?string $secondKey = null, ?string $localKey = null, ?string $secondLocalKey = null): HasManyThrough
    {
        return new HasManyThrough(
            $this, $farModelClass, $throughModelClass, $firstKey, $secondKey, $localKey, $secondLocalKey
        );
    }

    /**
     * Polymorphic relationship
     * 
     * @param ?string $name
     * @return MorphTo
     */
    protected function morphTo(?string $name = null): MorphTo
    {
        return (new MorphTo($this, $name))->get();
    }

    /**
     * One-to-One morphic relationship
     * 
     * @param string $relatedClass
     * @param string $name
     * @param ?string $idKey
     * @param ?string $typeKey
     * @return MorphOne
     */
    protected function morphOne(string $relatedClass, string $name, ?string $idKey = null, ?string $typeKey = null): MorphOne
    {
        return new MorphOne(
            $this, $relatedClass, $name, $idKey, $typeKey
        );
    }

    /**
     * One-to-Many morphic relationship
     * 
     * @param string $class Child model
     * @param string $name Morphic association
     * @param ?string $pivot_id
     * @param ?string $pivot_type
     * @param ?string $parent_type
     * @return MorphMany
     */
    protected function morphMany(
        string $relatedClass, 
        string $name, 
        ?string $idKey = null, 
        ?string $typeKey = null
    ): MorphMany
    {
        return new MorphMany(
            $this,
            $relatedClass, 
            $name,
            $idKey, 
            $typeKey
        );
    }

    /**
     * Many-to-Many polymorphic relationship
     * 
     * @param string $relatedClass Related model class
     * @param string $name Morphic association name (e.g., 'taggable')
     * @param string|null $table [Optional] Pivot table name (default: plural of the morphic association, e.g., 'taggables')
     * @param string|null $morphKey [Optional] Foreign key in the pivot table pointing to the current model (default e.g., 'tag_id' if the current model is 'Tag')
     * @param string|null $foreignKey [Optional] Foreign key in the pivot table pointing to the parent model (default e.g., 'taggable_id' if the morphic association is 'taggable')
     * @return MorphToMany
     */
    protected function morphToMany(
        string $relatedClass,
        string $name, 
        ?string $table = null, 
        ?string $morphKey = null, 
        ?string $foreignKey = null
    ): MorphToMany
    {
        return new MorphToMany(
            $this,
            $relatedClass,
            $name, 
            $table, 
            $morphKey, 
            $foreignKey
        );
    }

    /**
     * Inverse of morphToMany relationship
     * 
     * @param string $parentClass Parent model class
     * @param string $name Morphic association name
     * @param string|null $table [Optional] 
     * @param string|null $foreignKey [Optional] 
     * @param string|null $morphKey [Optional] 
     * @return MorphedByMany
     */
    protected function morphedByMany(
        string $parentClass, 
        string $name, 
        ?string $table = null, 
        ?string $foreignKey = null, 
        ?string $morphKey = null
    ): MorphedByMany
    {
        return new MorphedByMany(
            $this,
            $parentClass, 
            $name, 
            $table, 
            $foreignKey,
            $morphKey
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