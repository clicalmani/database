<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class RestoredEvent extends EventListener
{
    public const __NAME__ = 'restored';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}