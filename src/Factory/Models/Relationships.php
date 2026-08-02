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
use Clicalmani\Database\JoinClauseInterface;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;

trait Relationships
{
    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent.
     * 
     * @param class-string<Elegant> $parentClass Parent model
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
     * @param class-string<Elegant> $relatedClass
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
     * @param class-string<Elegant> $class Child model
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
     * @param class-string<Elegant> $relatedClass Child model
     * @param ?class-string<Elegant> $pivotClass The pivot model class
     * @param ?string $foreignKey [Optional] Table foreign key
     * @param ?string $relatedKey [Optional] Original key
     * @return BelongsToMany
     */
    protected function belongsToMany(string $relatedClass, ?string $pivotClass = null, ?string $foreignKey = null, ?string $relatedKey = null): BelongsToMany
    {
        return new BelongsToMany(
            $this, $relatedClass, $pivotClass ? (new $pivotClass)->getTable() : null, $foreignKey, $relatedKey
        );
    }

    /**
     * Has one through relationship
     * 
     * @param class-string<Elegant> $farModelClass Child model
     * @param class-string<Elegant> $throughModelClass [Optional] Foreign key
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
     * @param class-string<Elegant> $farModelClass Child model
     * @param class-string<Elegant> $throughModelClass [Optional] Foreign key
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
        return new MorphTo($this, $name ?? debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function']);
    }

    /**
     * One-to-One morphic relationship
     * 
     * @param class-string<Elegant> $relatedClass
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
     * @param class-string<Elegant> $class Child model
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
     * @param class-string<Elegant> $relatedClass Related model class
     * @param string $name Morphic association name (e.g., 'taggable')
     * @param class-string<self>|null $pivotClass [Optional] Pivot class name 
     * @param string|null $morphKey [Optional] Foreign key in the pivot table pointing to the current model (default e.g., 'tag_id' if the current model is 'Tag')
     * @param string|null $foreignKey [Optional] Foreign key in the pivot table pointing to the parent model (default e.g., 'taggable_id' if the morphic association is 'taggable')
     * @return MorphToMany
     */
    protected function morphToMany(
        string $relatedClass,
        string $name, 
        ?string $pivotClass = null, 
        ?string $morphKey = null, 
        ?string $foreignKey = null
    ): MorphToMany
    {
        return new MorphToMany(
            $this,
            $relatedClass,
            $name, 
            $pivotClass ?? "\\App\\Models\\" . Str::classify($name), 
            $morphKey, 
            $foreignKey
        );
    }

    /**
     * Inverse of morphToMany relationship
     * 
     * @param class-string<Elegant> $parentClass Parent model class
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
}