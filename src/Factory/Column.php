<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Factory\DataTypes\DataType;

/**
 * Table column creation
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class Column extends DataType 
{
    public function __construct(private $name) {}

    /**
     * Render column data
     * 
     * @return string Column data
     */
    public function render()
    {
        return "`$this->name`" . $this->getData();
    }
}
