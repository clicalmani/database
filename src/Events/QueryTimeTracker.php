<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Foundation\Events\EventListener;

class QueryTimeTracker extends EventListener
{
    public function listen(string $event, mixed $handler): void
    {
        $db_config = app()->config->database();
        $db_config['listeners'][$event][] = $handler;
        app()->database = $db_config;
    }
}