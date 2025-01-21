<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class SavedEvent extends EventListener
{
    public const __NAME__ = 'saved';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}