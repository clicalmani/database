<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class RestoringEvent extends EventListener
{
    public const __NAME__ = 'restoring';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}