<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Fixed extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->decimal($options['scale'] ?? DataType::DECIMAL_SCALE, $options['precision'] ?? DataType::DECIMAL_PRECISION);

        if (TRUE === @ $options['unsigned']) $this->unsigned();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        if (TRUE === @ $options['zerofill']) $this->zeroFill();

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return (float) $value;
    }
}
