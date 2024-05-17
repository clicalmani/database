<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Validate
{
    public mixed $validator;

    public function __construct(string $validator)
    {
        $this->validator = $validator;
    }
}
