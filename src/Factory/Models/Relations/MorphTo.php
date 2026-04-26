<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;

class MorphTo extends Relationship
{
    protected Elegant $parent; 
    protected Elegant $model; // Model from which the relationship is defined (e.g., Tag)

    public function __construct(
        protected string $class, 
        protected ?string $pivotKey = null
    ) {
        $this->model = new $class;
        $this->pivotKey = $pivotKey ?? 'id';
    }

    protected function getParentClass(): string
    {
        return $this->parent::class;
    }

    public function get(?string $id = null): mixed
    {
        $this->model->getQuery()->where("{$this->pivotKey} = ?", [$id]);
        return $this->model->fetch()->first();
    }
}