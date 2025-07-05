<?php
namespace Clicalmani\Database\Traits;

trait SoftDelete
{
    /**
     * Include soft deleted records in the query results.
     * 
     * @return static
     */
    public function withTrashed() : static
    {
        return tap($this, fn(self $instance) => $instance->query->set('recycle', 0));
    }

    /**
     * Include only soft deleted records in the query results.
     * 
     * @return static
     */
    public function onlyTrashed() : static
    {
        return tap($this, fn(self $instance) => $instance->query->set('recycle', 2));
    }

    /**
     * Restore a soft deleted record.
     * 
     * @return bool
     */
    public function restore() : bool
    {
       $this->emit('restoring');
       $success = $this->update(['deleted_at' => null]);
       $this->emit('restored');
       return $success;
    }
}