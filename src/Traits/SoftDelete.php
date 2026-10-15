<?php
namespace Clicalmani\Database\Traits;

trait SoftDelete
{
    protected ?string $deletedAtColumn = null;

    public function usesSoftDeletes(): bool
    {
        return true;
    }

    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn
            ? $this->deletedAtColumn
            : 'deleted_at';
    }

    public function trashed(): bool
    {
        return $this->{$this->getDeletedAtColumn()} !== null;
    }

    public function notTrashed(): bool
    {
        return !$this->trashed();
    }

    public function markAsTrashed(): static
    {
        $this->{$this->getDeletedAtColumn()} = date('Y-m-d H:i:s');
        return $this;
    }

    public function markAsRestored(): static
    {
        $this->{$this->getDeletedAtColumn()} = null;
        return $this;
    }
}