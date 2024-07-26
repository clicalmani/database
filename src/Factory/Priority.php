<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure routine priority.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Priority
{
    public mixed $priority;

    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }
}
