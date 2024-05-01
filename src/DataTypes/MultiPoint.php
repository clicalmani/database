<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class MultiPoint extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $this->multiPoint();

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
