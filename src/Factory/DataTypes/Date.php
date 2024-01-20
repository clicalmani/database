<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Trait Date
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait Date
{
    /**
     * Date data type
     * 
     * @return static
     */
    public function date() : static
    {
        $this->data .= ' DATE';
        return $this;
    }

    /**
     * Datetime data type
     * 
     * @return static 
     */
    public function dateTime() : static
    {
        $this->data .= ' DATETIME';
        return $this;
    }

    /**
     * Time data type
     * 
     * @return static
     */
    public function time() : static
    {
        $this->data .= ' TIME';
        return $this;
    }

    /**
     * Epoch times data type
     * 
     * @return static
     */
    public function timestamp() : static
    {
        $this->data .= ' TIMESTAMP';
        return $this;
    }
}
