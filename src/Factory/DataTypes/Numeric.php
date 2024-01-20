<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Trait Numeric
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait Numeric
{
    /**
     * Integer data type
     * 
     * @param int $length Default 10
     * @return static
     */
    public function integer(int $length = 10) : static
    {
        $this->data .= " INT($length)";
        return $this;
    }

    /**
     * Alias of integer()
     * 
     * @return static
     */
    public function int(int $length = 10) : static
    {
        return $this->integer($length);
    }

    /**
     * Unsigned int data type
     * 
     * @param int $length
     * @return static
     */
    public function intUnsigned(int $length = 10) : static
    {
        $this->integer($length);
        $this->data .= " UNSIGNED";
        return $this;
    }

    /**
     * Medium int data type
     * 
     * @return static
     */
    public function mediumInt() : static
    {
        $this->data .= ' MEDIUMINT';
        return $this;
    }

    /**
     * Big int data type
     * 
     * @param int $length
     * @return static
     */
    public function bigInt(int $length = 20) : static
    {
        $this->data .= " BIGINT($length)";
        return $this;
    }

    /**
     * Small int data type
     * 
     * @return static
     */
    public function smallInt() : static
    {
        $this->data .= ' SMALLINT';
        return $this;
    }

    /**
     * Tiny int data type
     * 
     * @return static
     */
    public function tinyInt() : static
    {
        $this->data .= ' TINYINT';
        return $this;
    }

    /**
     * Decimal data type
     * 
     * @param int $precision
     * @param int $scale
     * @return static
     */
    public function decimal(int $precision = 0, int $scale = 2) : static
    {
        $this->data .= ' DECIMAL(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    /**
     * Mumeric data type
     * 
     * @param int $precision
     * @param int $scale
     * @return static
     */
    public function numeric(int $precision = 0, int $scale = 2) : static
    {
        $this->data .= ' NUMERIC(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    /**
     * Fixed data type
     * 
     * @param int $precision
     * @param int $scale
     * @return static
     */
    public function fixed(int $precision = 0, int $scale = 2) : static
    {
        $this->data .= ' DECIMAL(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    /**
     * Zero fill
     * 
     * @return static
     */
    public function zeroFill() : static
    {
        $this->data .= ' ZEROFILL';
        return $this;
    }

    /**
     * Unsigned
     * 
     * @return static
     */
    public function unsigned() : static
    {
        $this->data .= ' UNSIGNED';
        return $this;
    }

    /**
     * Auto increment
     * 
     * @return static
     */
    public function autoIncrement() : static
    {
        $this->data .= ' AUTO_INCREMENT';
        return $this;
    }
}
