<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class DeletingEvent extends EventListener
{
    public const __NAME__ = 'deleting';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}