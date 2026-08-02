<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Char extends DataType
{
    public function __construct(mixed ...$options)
    {
        $length = $options['length'] ?? DataType::CHARACTER_LENGTH;

        $this->char($length);

        if ($charset = @ $options['charset']) $this->charset($charset);

        if ($collate = @ $options['collate']) $this->collation($collate);

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }
}
