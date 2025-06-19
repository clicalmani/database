<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class SavedEvent extends EventListener
{
    public const __NAME__ = 'saved';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}