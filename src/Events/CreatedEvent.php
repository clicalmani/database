<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class CreatedEvent extends EventListener
{
    public const __NAME__ = 'created';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}