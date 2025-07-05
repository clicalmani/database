<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class VarChar extends DataType
{
    public function __construct(mixed ...$parameters)
    {
        $length = @ $parameters['length'];

        $this->varchar($length);

        if (isset($parameters['binary']) AND $parameters['binary']) $this->data .= ' BINARY';

        if ($charset = @ $parameters['charset']) $this->charset($charset);

        if ($collate = @ $parameters['collate']) $this->collation($collate);

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($default_value = @ $parameters['default']) $this->default($default_value);

        if ($comment = @ $parameters['comment']) $this->comment($comment);
    }
}
