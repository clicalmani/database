<?php
namespace Clicalmani\Database\Factory\DataTypes;

class DataType
{
    use Numeric,
        Character,
        Spatial,
        JSON,
        Date;

    function __construct(protected $data = '')
    {}

    function nullable($bool = true)
    {
        $this->data .= $bool ? ' NULL': ' NOT NULL';
        return $this;
    }

    function default($value = '')
    {
        $this->data .= ' DEFAULT "' . $value . '"';
        return $this;
    }

    function unique()
    {
        $this->data .= ' UNIQUE';
        return $this;
    }

    function primary()
    {
        $this->data .= ' PRIMARY KEY';
        return $this;
    }

    function comment($comment = '')
    {
        $this->data .= ' COMMENT "' . $comment . '"';
        return $this;
    }

    private function join(array $arr) : string
    {
        $value = '';

        foreach ($arr as $index => $val) {
            if ($index < count($arr) - 1) $value .= '"' . $val . '", ';
            else $value .= '"' . $val . '"';
        }

        return $value;
    }

    function getData()
    {
        return $this->data;
    }

    function __call($method, $params)
    {
        if (method_exists($this, $method)) $this->{$method}(...$params);
        else throw new \Clicalmani\Exceptions\DataTypeException("The method $method is not associated to any data type.");
    }
}
