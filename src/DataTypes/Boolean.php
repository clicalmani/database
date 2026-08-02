<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Boolean extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->tinyInt();

        if (isset($options['length'])) $this->length($options['length']);
        else $this->length(DataType::TINYINT_LENGTH);

        if (isset($options['unsigned'])) $this->unsigned();

        if (isset($options['nullable'])) $this->nullable();
        else $this->nullable(false);

        if (isset($options['autoIncrement'])) $this->autoIncrement();

        parent::sharedOptions($options);
    }

    public function cast(mixed $value): mixed
    {
        return (bool) $value;
    }
}