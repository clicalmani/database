<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class DeletedEvent extends EventListener
{
    public const __NAME__ = 'deleted';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}