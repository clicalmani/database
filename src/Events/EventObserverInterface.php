<?php
namespace Clicalmani\Database\Events;

interface EventObserverInterface
{
    /**
     * Observe model events
     * 
     * @param \Clicalmani\Database\Factory\Models\Elegant $model
     * @return void
     */
    public function observe(\Clicalmani\Database\Factory\Models\Elegant $model): void;
}