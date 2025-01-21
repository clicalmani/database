<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class DeletedEvent extends EventListener
{
    public const __NAME__ = 'deleted';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}