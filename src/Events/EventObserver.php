<?php
namespace Clicalmani\Database\Events;

abstract class EventObserver implements EventObserverInterface
{
    public function observe(\Clicalmani\Database\Factory\Models\Model $model): void
    {
        $model->registerObserver($this);
    }
}