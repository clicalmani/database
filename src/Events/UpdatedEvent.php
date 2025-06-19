<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class UpdatedEvent extends EventListener
{
    public const __NAME__ = 'updated';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}