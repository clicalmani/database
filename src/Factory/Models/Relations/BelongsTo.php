<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;

class BelongsTo extends Relationship
{
    protected Elegant $parent;
    protected Elegant $related;

    private string $callerClass = '';

    public function __construct(
        protected string $relatedClass, 
        protected ?string $parentClass = null,
        protected ?string $foreignKey = null, 
        protected ?string $originalKey = null
    )
    {
        $this->parent = new $parentClass;
        $this->related = new $relatedClass;
        $this->callerClass = $this->getCallerClassFromNew();
    }

    public function get(?string $id = null): mixed
    {
        return $this->parent->leftJoin($this->relatedClass, $this->foreignKey, $this->originalKey)
                    ->whereAnd("{$this->related->getKey(true)} = ?", [$id])
                    ->fetch($this->parentClass)
                    ->first();
    }

    public function getParentClass(): string
    {
        throw new \Exception('Not implemented');
    }
}