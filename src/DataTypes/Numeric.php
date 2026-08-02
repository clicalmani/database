<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Numeric extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->numeric($options['scale'] ?? DataType::DECIMAL_SCALE, $options['precision'] ?? DataType::DECIMAL_PRECISION);

        if (TRUE === @ $options['unsigned']) $this->unsigned();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return is_int($value) ? (int) $value : (float) $value;
    }
}
