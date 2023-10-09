<?php
namespace Clicalmani\Database\Factory\Indexes;

class IndexType 
{
    function __construct(private $data = '')
    {}

    function index()
    {
        $this->data .= ' INDEX';
        return $this;
    }

    function unique()
    {
        $this->data .= ' UNIQUE';
        return $this;
    }

    function fulltext()
    {
        $this->data .= ' FULLTEXT INDEX';
        return $this;
    }

    function foreignKey(string $key)
    {
        $this->data .= ' FOREIGN KEY (`' . $key . '`)';
        return $this;
    }

    function getData()
    {
        return $this->data;
    }
}
