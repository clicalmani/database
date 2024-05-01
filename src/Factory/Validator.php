<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Validator
{
    public mixed $validator;

    public function __construct(mixed ...$validator)
    {
        $this->validator = $validator;
    }
}
