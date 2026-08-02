<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class MediumText extends DataType
{
    public function __construct(mixed ...$options)
    {
        $this->mediumText();

        if ($charset = @ $options['charset']) $this->charset($charset);

        if ($collate = @ $options['collate']) $this->collation($collate);

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);
    }
}
