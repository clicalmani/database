<?php
namespace Clicalmani\Database\Factory\DataTypes;

trait Numeric
{
    function int()
    {
        $this->data .= ' INTEGER';
        return $this;
    }

    function intUnsigned()
    {
        $this->data .= ' INTEGER UNSIGNED';
        return $this;
    }

    function mediumInt()
    {
        $this->data .= ' MEDIUMINT';
        return $this;
    }

    function bigInt()
    {
        $this->data .= ' BIGINT';
        return $this;
    }

    function smallInt()
    {
        $this->data .= ' SMALLINT';
        return $this;
    }

    function tinyInt()
    {
        $this->data .= ' TINYINT';
        return $this;
    }

    function decimal($precision = 0, $scale = 2)
    {
        $this->data .= ' DECIMAL(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    function numeric($precision = 0, $scale = 2)
    {
        $this->data .= ' NUMERIC(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    function fixed($precision = 0, $scale = 2)
    {
        $this->data .= ' DECIMAL(' . $precision . ', ' . $scale . ')';
        return $this;
    }

    function zeroFill()
    {
        $this->data .= ' ZEROFILL';
        return $this;
    }

    function unsigned()
    {
        $this->data .= ' UNSIGNED';
        return $this;
    }

    function autoIncrement()
    {
        $this->data .= ' AUTO_INCREMENT';
        return $this;
    }
}
