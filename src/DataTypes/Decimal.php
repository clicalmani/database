<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Decimal extends DataType
{
    public function __construct(mixed ...$options)
    {
        $scale = $options['scale'] ?? DataType::DECIMAL_SCALE;
        $precision = $options['precision'] ?? DataType::DECIMAL_PRECISION;

        $this->decimal($scale, @ $precision);

        if (TRUE === @ $options['unsigned']) $this->unsigned();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return (float) $value;
    }
}
