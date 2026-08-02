<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Timestamp extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->timestamp();
        
        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        $this->data .= ' DEFAULT CURRENT_TIMESTAMP';

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return is_string($value) ? new \DateTime($value, new \DateTimeZone(config('app.timezone', 'UTC'))): $value;
    }
}
