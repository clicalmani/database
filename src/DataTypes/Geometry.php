<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Geometry extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->geometry();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }
}
