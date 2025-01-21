<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class SavingEvent extends EventListener
{
    public const __NAME__ = 'saving';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}