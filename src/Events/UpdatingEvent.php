<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class UpdatingEvent extends EventListener
{
    public const __NAME__ = 'updating';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}