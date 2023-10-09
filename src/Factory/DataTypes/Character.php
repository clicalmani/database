<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Character data type
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
trait Character
{
    /**
     * Char data type
     * 
     * @param int $length
     * @return static
     */
    public function char(int $length = 10) : static
    {
        $this->data .= ' CHAR';
        $this->length($length);
        return $this;
    }

    /**
     * Varchar data type
     * 
     * @param int $length
     * @return static
     */
    public function varchar($length = 45) : static
    {
        $this->data .= ' VARCHAR';
        $this->length($length);
        return $this;
    }

    /**
     * Text data type 
     * 
     * @return static
     */
    public function text() : static
    {
        $this->data .= ' TEXT';
        return $this;
    }

    /**
     * Tiny text data type
     * 
     * @return static
     */
    public function tinyText() : static
    {
        $this->data .= ' TINYTEXT';
        return $this;
    }

    /**
     * Medium text data type
     * 
     * @return static
     */
    public function mediumText() : static
    {
        $this->data .= ' MEDIUMTEXT';
        return $this;
    }

    /**
     * Long text data type
     * 
     * @return static
     */
    public function longText() : static
    {
        $this->data .= ' LONGTEXT';
        return $this;
    }

    /**
     * Tiny blob data type
     * 
     * @return static
     */
    public function tinyBlob() : static
    {
        $this->data .= ' TINYBLOB';
        return $this;
    }

    /**
     * Medium blob data type
     * 
     * @return static
     */
    public function mediumBlob() : static
    {
        $this->data .= ' MEDIUMBLOB';
        return $this;
    }

    /**
     * Long blob data type
     * 
     * @return static
     */
    public function longBlob() : static
    {
        $this->data .= ' LONGBLOB';
        return $this;
    }

    /**
     * Binary data type
     * 
     * @return static
     */
    public function binary() : static
    {
        $this->data .= ' BINARY';
        return $this;
    }

    /**
     * Charbyte data type
     * 
     * @return static
     */
    public function charByte() : static
    {
        return $this->binary();
    }

    /**
     * Varbinary data type
     * 
     * @return static
     */
    public function varbinary() : static
    {
        $this->data .= ' VARBINARY';
        return $this;
    }

    /**
     * Blob data type
     * 
     * @return static
     */
    public function blob() : static
    {
        $this->data .= ' BLOB';
        return $this;
    }

    /**
     * Enum data type
     * 
     * @param string ...$values 
     * @return static
     */
    public function enum(string ...$values ) : static
    {
        $this->data .= ' ENUM(' . $this->join($values) . ')';
        return $this;
    }

    /**
     * Set data type
     * 
     * @param string ...$values 
     * @return static
     */
    public function set(string ...$values ) : static
    {
        $this->data .= ' SET(' . $this->join($values) . ')';
        return $this;
    }

    /**
     * Type length
     * 
     * @param int $len Length
     * @return static
     */
    public function length(int $len) : static
    {
        $this->data .= '(' . $len . ')';
        return $this;
    }

    /**
     * Character set
     * 
     * @param string $charset Character set
     * @return static
     */
    public function characterSet(string $charset = 'latin1') : static
    {
        $this->data .= ' CHARACTER SET ' . $charset;
        return $this;
    }

    /**
     * Alias of characterSet
     * 
     * @see static::characterSet()
     * @param string $charset
     * @return static
     */
    public function charset(string $charset = 'latin1') : static
    {
        $this->characterSet($charset);
        return $this;
    }

    /**
     * Character collation
     * 
     * @param string $collation
     * @return static
     */
    public function collation(string $collation = 'latin1_general_cs') : static
    {
        $this->data .= ' COLLATION ' . $collation;
        return $this;
    }
}
