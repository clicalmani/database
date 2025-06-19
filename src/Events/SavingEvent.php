<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class SavingEvent extends EventListener
{
    public const __NAME__ = 'saving';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}