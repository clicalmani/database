<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLTriggers
{
    /**
     * Before create trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeCreate(callable $callback) : void
    {
        $this->registerEvent('before_create', $callback);
    }

    /**
     * After create trigger
     * 
     * @param callable $callback Event handler
     * @return static
     */
    protected function afterCreate(callable $callback) : void
    {
        $this->registerEvent('after_create', $callback);
    }

    /**
     * Before update trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeUpdate(callable $callback) : void
    {
        $this->registerEvent('before_update', $callback);
    }

    /**
     * After update trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function afterUpdate(callable $callback) : void
    {
        $this->registerEvent('after_update', $callback);
    }

    /**
     * Before delete trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeDelete(callable $callback) : void
    {
        $this->registerEvent('before_delete', $callback);
    }

    /**
     * After delete trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function afterDelete(callable $callback) : void
    {
        $this->registerEvent('after_delete', $callback);
    }

    protected function boot() : void
    {
        /**
         * TODO
         */
    }
}