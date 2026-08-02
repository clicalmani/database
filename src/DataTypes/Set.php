<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Set extends DataType
{
    public function __construct(mixed ...$options)
    {
        $values = @ $options['values'] ?? [];

        $this->set( ...$values );
        
        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }
}
