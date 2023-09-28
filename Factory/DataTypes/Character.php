<?php
namespace Clicalmani\Database\Factory\DataTypes;

trait Character
{
    function char($len = null)
    {
        $this->data .= ' CHAR';
        if ($len) $this->length($len);
        else $this->length(45);
        return $this;
    }

    function varchar($len = null)
    {
        $this->data .= ' VARCHAR';
        if ($len) $this->length($len);
        else $this->length(45);
        return $this;
    }

    function text()
    {
        $this->data .= ' TEXT';
        return $this;
    }

    function tinyText()
    {
        $this->data .= ' TINYTEXT';
        return $this;
    }

    function mediumText()
    {
        $this->data .= ' MEDIUMTEXT';
        return $this;
    }

    function longText()
    {
        $this->data .= ' LONGTEXT';
        return $this;
    }

    function tinyBlob()
    {
        $this->data .= ' TINYBLOB';
        return $this;
    }

    function mediumBlob()
    {
        $this->data .= ' MEDIUMBLOB';
        return $this;
    }

    function longBlob()
    {
        $this->data .= ' LONGBLOB';
        return $this;
    }

    function binary()
    {
        $this->data .= ' BINARY';
        return $this;
    }

    function charByte()
    {
        return $this->binary();
    }

    function varbinary()
    {
        $this->data .= ' VARBINARY';
        return $this;
    }

    function blob()
    {
        $this->data .= ' BLOB';
        return $this;
    }

    function enum($elements = [])
    {
        $this->data .= ' ENUM(' . $this->join($elements) . ')';
        return $this;
    }

    function set($elements = [])
    {
        $this->data .= ' SET(' . $this->join($elements) . ')';
        return $this;
    }

    function length($len)
    {
        $this->data .= '(' . $len . ')';
        return $this;
    }

    function characterSet($attribute = 'latin1')
    {
        $this->data .= ' CHARACTER SET ' . $attribute;
        return $this;
    }

    function charset($attribute = 'latin1')
    {
        $this->characterSet($attribute);
        return $this;
    }

    function collation($collation = 'latin1_general_cs')
    {
        $this->data .= ' COLLATION ' . $collation;
        return $this;
    }
}
