<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Double extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->decimal($options['scale'] ?? DataType::DECIMAL_SCALE, $options['precision'] ?? DataType::DECIMAL_PRECISION);

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
