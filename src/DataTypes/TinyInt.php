<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class TinyInt extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $this->tinyInt();

        if (TRUE === @ $parameters['unsigned']) $this->unsigned();

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if (isset($parameters['default'])) $this->default((int)$parameters['default']);

        if (TRUE === @ $parameters['autoIncrement']) $this->autoIncrement();

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
