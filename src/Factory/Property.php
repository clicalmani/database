<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Property
{
    public mixed $args;

    public function __construct(mixed ...$args)
    {
        $this->args = $args;
    }
}
