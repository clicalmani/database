<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class CreatedEvent extends EventListener
{
    public const __NAME__ = 'created';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}