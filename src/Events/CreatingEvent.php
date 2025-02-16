<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class CreatingEvent extends EventListener
{
    public const __NAME__ = 'creating';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}