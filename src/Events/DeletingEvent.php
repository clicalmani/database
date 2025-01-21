<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class DeletingEvent extends EventListener
{
    public const __NAME__ = 'deleting';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}