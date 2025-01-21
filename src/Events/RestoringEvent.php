<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

class RestoringEvent extends EventListener
{
    public const __NAME__ = 'restoring';

    public function __construct(Model $model)
    {
        $this->target = $model;
    }
}