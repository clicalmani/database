<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Interfaces\JoinClauseInterface;
use Clicalmani\Foundation\Support\Facades\Str;

class MorphToMany extends Relationship
{
    protected Elegant $parent; 
    protected Elegant $related;

    private string $callerClass = '';

    public function __construct(
        protected string $relatedClass, 
        protected string $name, 
        protected ?string $pivotTable = null,
        protected ?string $foreignPivotKey = null, 
        protected ?string $parentPivotId = null, 
        protected ?string $parentPivotType = null, 
        protected ?string $parentType = null, 
        protected ?string $parentClass = null
    ) {
        $this->parent = new $parentClass;
        $this->related = new $relatedClass;
        $this->pivotTable = $pivotTable ?? Str::pluralize($name);
        $this->foreignPivotKey = $foreignPivotKey ?? Str::singularize($this->related->getTable()) . '_id';
        $this->parentPivotId = $parentPivotId ?? $name . '_id';
        $this->parentPivotType = $parentPivotType ?? $name . '_type';
        $this->parentType = $parentType ?? Str::singularize($this->parent->getTable());

        $this->callerClass = $this->getCallerClassFromNew();
    }

    protected function getParentClass(): string
    {
        return $this->parent::class;
    }

    public function get(?string $id = null): array
    {
        $query = ($this->callerClass === $this->parentClass) ? $this->related->getQuery(): $this->parent->getQuery();
        $query->where($this->getWhereCondition(), [$id, $this->parentType]);
        $query->join($this->pivotTable, fn(JoinClauseInterface $join) => 
            $join->inner()->on($this->getJoinCondition()));
        
        return ($this->callerClass === $this->parentClass) ? $this->related->fetch()->toArray(): $this->parent->fetch()->toArray();
    }

    private function getTablePrefix(): string
    {
        return $this->parent->getQuery()->getPrefix();
    }

    private function getWhereCondition()
    {
        $prefix = $this->parent->getQuery()->getPrefix();
        
        return ($this->callerClass === $this->parentClass) ? "{$prefix}{$this->pivotTable}.{$this->parentPivotId} = ? AND {$prefix}{$this->pivotTable}.{$this->parentPivotType} = ?":
            "{$this->foreignPivotKey} = ? AND {$this->parentPivotType} = ?";
    }

    private function getJoinCondition()
    {
        return ($this->callerClass === $this->parentClass) ? "{$this->getTablePrefix()}{$this->pivotTable}.$this->foreignPivotKey = {$this->related->getTableAlias()}.{$this->related->getKey()}": 
            "{$this->getTablePrefix()}{$this->pivotTable}.$this->parentPivotId = {$this->parent->getTableAlias()}.{$this->parent->getKey()}";
    }
}