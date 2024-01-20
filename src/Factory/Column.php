<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Factory\DataTypes\DataType;

/**
 * Class Column
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Column extends DataType 
{
    /**
     * Column name
     * 
     * @var ?string
     */
    private string $name = '';

    public function __construct(string $name) {
        $this->name = $name;
    }

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
