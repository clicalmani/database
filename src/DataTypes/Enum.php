<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Enum extends DataType
{
    public function __construct(mixed ...$options)
    {
        $values = @ $options['values'] ?? [];

        $this->enum( ...$values );
        
        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return (string) $value;
    }
}
