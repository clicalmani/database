<?php
namespace Clicalmani\Database\Factory\Models\Events;

interface EventListener 
{
    /**
     * Notify observer
     * 
     * @param mixed $event_data
     * @return void
     */
    public function notify(mixed $event_data) : void;
}
