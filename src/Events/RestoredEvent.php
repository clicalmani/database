<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class RestoredEvent extends EventListener
{
    public const __NAME__ = 'restored';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}