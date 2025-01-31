<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure table engine.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Engine
{
    public string $engine;

    public function __construct(string $engine)
    {
        $this->engine = $engine;
    }
}