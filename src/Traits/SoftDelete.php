<?php
namespace Clicalmani\Database\Traits;

trait SoftDelete
{
    /**
     * Include soft deleted records in the query results.
     * 
     * @return static
     */
    public static function withTrashed() : static
    {
        return tap(static::getInstance(), fn($instance) => $instance->getQuery()->where(
            str_replace('deleted_at IS NULL', '', $instance->getQuery()->getParam('where'))
        ));
    }

    /**
     * Include only soft deleted records in the query results.
     * 
     * @return static
     */
    public static function onlyTrashed() : static
    {
        return tap(static::getInstance(), fn($instance) => $instance->getQuery()->where(
            str_replace('deleted_at IS NULL', 'deleted_at IS NOT NULL', $instance->getQuery()->getParam('where'))
        ));
    }

    /**
     * Restore a soft deleted record.
     * 
     * @return bool
     */
    public function restore() : bool
    {
       return  $this->update(['deleted_at' => null]);
    }
}