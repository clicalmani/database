<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Integer extends DataType
{
    public function __construct(mixed ...$options)
    {
        $length = $options['length'] ?? DataType::INTEGER_LENGTH;

        if (FALSE === @ $options['unsigned']) $this->int($length);
        else $this->intUnsigned($length);

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        if (TRUE === @ $options['autoIncrement']) $this->autoIncrement();

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return (int) $value;
    }
}
