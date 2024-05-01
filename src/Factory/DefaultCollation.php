<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DefaultCollation
{
    public string $charset;

    public string $collate;

    public function __construct(string $charset, string $collate)
    {
        $this->charset = $charset;
        $this->collate = $collate;
    }
}