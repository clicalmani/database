<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Class DataType
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class DataType
{
    use Numeric,
        Character,
        Spatial,
        JSON,
        Date;

    /**
     * Value
     * 
     * @var mixed
     */
    protected $value;

    /**
     * Data
     * 
     * @var string
     */
    protected string $data = '';

    public function __construct(string $data = '') {
        $this->data = $data;
    }

    /**
     * Set null data type
     * 
     * @param ?bool $null Default to true
     * @return static
     */
    public function nullable(?bool $null = true) : static
    {
        $this->data .= $null ? ' NULL': ' NOT NULL';
        return $this;
    }

    /**
     * Set default value
     * 
     * @param ?string $value Default value
     * @return static
     */
    public function default(?string $value = NULL) : static
    {
        $this->data .= ' DEFAULT ' . ((NULL !== $value) ? "'$value'": 'NULL');
        return $this;
    }

    /**
     * Shorthand for default when default value is null.
     * 
     * @return static
     */
    public function defaultNull() : static
    {
        $this->data .= ' DEFAULT NULL';
        return $this;
    }

    /**
     * Unique index
     * 
     * @return static
     */
    public function unique() : static
    {
        $this->data .= ' UNIQUE';
        return $this;
    }

    /**
     * Primary key
     * 
     * @return static
     */
    public function primary() : static
    {
        $this->data .= ' PRIMARY KEY';
        return $this;
    }

    /**
     * Comment a data
     * 
     * @param ?string $comment Data comment
     * @return static
     */
    public function comment(?string $comment = '') : static
    {
        $this->data .= ' COMMENT "' . $comment . '"';
        return $this;
    }

    private function join(array $arr) : string
    {
        $value = '';

        foreach ($arr as $index => $val) {
            if ($index < count($arr) - 1) $value .= "'$val', ";
            else $value .= "'$val'";
        }

        return $value;
    }

    /**
     * Returns data
     * 
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }

    /**
     * PHP magic __call
     * 
     * @param string $method Method to call
     * @param mixed $params Arguments
     * @return void
     */
    public function __call(string $method, mixed $params) : void
    {
        if (method_exists($this, $method)) $this->{$method}(...$params);
        else throw new \Clicalmani\Database\Exceptions\DataTypeException("The method $method is not associated to any data type.");
    }

    public function __set($name, $value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
