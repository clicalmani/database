<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Column extends DataType 
{
    function __construct(private $name) {}

    function render()
    {
        return $this->name . $this->getData();
    }
}
