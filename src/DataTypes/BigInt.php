<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;
use Override;

class BigInt extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->bigInt();

        if (TRUE === @ $options['unsigned']) $this->unsigned();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        if (TRUE === @ $options['autoIncrement']) $this->autoIncrement();

        parent::sharedOptions($options);
    }

    #[Override]
    public function cast(mixed $value): mixed
    {
        return (int) $value;
    }
}
