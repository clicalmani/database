<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class SRID extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $this->srid(@$parameters['srid']);

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
