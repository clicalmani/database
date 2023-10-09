<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Numeric data type
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
trait Numeric
{
    /**
     * Int data type
     * 
     * @return static
     */
    public function int() : static
    {
        $this->data .= ' INT(10)';
        return $this;
    }

    /**
     * Unsigned int data type
     * 
     * @return static
     */
    public function intUnsigned() : static
    {
        $this->data .= ' INT(10) UNSIGNED';
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
     * @return static
     */
    public function bigInt() : static
    {
        $this->data .= ' BIGINT';
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
