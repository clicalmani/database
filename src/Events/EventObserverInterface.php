<?php
namespace Clicalmani\Database\Events;

interface EventObserverInterface
{
    /**
     * Observe model events
     * 
     * @param \Clicalmani\Database\Factory\Models\Model $model
     * @return void
     */
    public function observe(\Clicalmani\Database\Factory\Models\Model $model): void;
}