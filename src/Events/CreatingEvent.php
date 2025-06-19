<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class CreatingEvent extends EventListener
{
    public const __NAME__ = 'creating';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}