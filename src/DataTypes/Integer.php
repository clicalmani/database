<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Integer extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $length = @ $parameters['length'];

        if (FALSE === @ $parameters['unsigned']) $this->int($length);
        else $this->intUnsigned($length);

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if (TRUE === @ $parameters['autoIncrement']) $this->autoIncrement();

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
