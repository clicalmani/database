<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
class PrimaryKey
{
    /**
     * Key(s)
     * 
     * @var NULL|string|string[]
     */
    public NULL|string|array $keys = NULL;

    public function __construct(NULL|string|array $keys = NULL)
    {
        $this->keys = $keys;
    }
}
