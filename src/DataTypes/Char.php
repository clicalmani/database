<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Char extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $length = @ $parameters['length'];

        $this->char($length);

        if ($charset = @ $parameters['charset']) $this->charset($charset);

        if ($collate = @ $parameters['collate']) $this->collation($collate);

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
