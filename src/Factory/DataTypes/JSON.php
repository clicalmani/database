<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Trait JSON
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait JSON
{
    /**
     * JSON data type
     * 
     * @return static
     */
    public function json() : static
    {
        $this->data .= ' JSON';
        return $this;
    }
}
