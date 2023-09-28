<?php
namespace Clicalmani\Database\Factory\Indexes;

class IndexType 
{
    function __construct(private $data = '')
    {}

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

    function foreignKey()
    {
        $this->data .= ' FOREIGN KEY';
        return $this;
    }

    function getData()
    {
        return $this->data;
    }
}
