<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Elegant;

class UpdatingEvent extends EventListener
{
    public const __NAME__ = 'updating';

    public function __construct(Elegant $model)
    {
        $this->target = $model;
    }
}