<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class UpdatedEvent extends EventListener
{
    public const __NAME__ = 'updated';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}