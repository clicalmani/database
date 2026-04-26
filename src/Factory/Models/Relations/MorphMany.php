<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Str;

class MorphMany extends Relationship
{
    protected Elegant $parent; 
    protected Elegant $related;

    private string $callerClass = '';

    public function __construct(
        protected string $class, 
        protected string $name,
        protected ?string $pivotId = null, 
        protected ?string $pivotType = null, 
        protected ?string $parentType = null
    ) {
        $this->callerClass = $this->getCallerClassFromNew();

        $this->related = new $class;
        $this->pivotId = $pivotId ?? $name . '_id';
        $this->pivotType = $pivotType ?? $name . '_type';
        $this->parentType = $parentType ?? Str::singularize(
            Str::tableize(
                class_basename($this->getParentClass())
            )
        );
    }

    protected function getParentClass(): string
    {
        return $this->callerClass;
    }

    public function get(?string $id = null): CollectionInterface
    {
        $this->related->getQuery()->where("{$this->pivotId} = ? AND {$this->pivotType} = ?", [$id, $this->parentType]);
        return $this->related->fetch();
    }
}