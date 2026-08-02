<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Date extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->date();
        
        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return is_string($value) ? new \DateTime($value, new \DateTimeZone(config('app.timezone', 'UTC'))): $value;
    }
}
