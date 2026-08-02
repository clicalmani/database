<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;
use Override;

class Blob extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->blob();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }
}
