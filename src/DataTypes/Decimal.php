<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Decimal extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $this->decimal(@ $parameters['precision'], @ $parameters['scale']);

        if (TRUE === @ $parameters['unsigned']) $this->unsigned();

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
