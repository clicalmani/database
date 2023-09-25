<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\Factory\DataTypes\DataType;

class Column extends DataType 
{
    function __construct(private $name) {}

    function render()
    {
        return $this->name . $this->getData();
    }
}
