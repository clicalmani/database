<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Foundation\Events\EventListener;

class QueryTimeTracker extends EventListener
{
    public function listen(string $event, mixed $handler): void
    {
        app()->setTimeTracker($event, $handler);
    }
}