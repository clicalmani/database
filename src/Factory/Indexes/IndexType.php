<?php
namespace Clicalmani\Database\Factory\Indexes;

/**
 * Class IndexType
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class IndexType 
{
    private string $data = '';

    public function __construct(string $data = '')
    {
        $this->data = $data;
    }

    /**
     * Type index
     * 
     * @return static
     */
    public function index() : static
    {
        $this->data .= ' INDEX';
        return $this;
    }

    /**
     * Type unique
     * 
     * @return static
     */
    public function unique() : static
    {
        $this->data .= ' UNIQUE';
        return $this;
    }

    /**
     * Type fulltext
     * 
     * @return static
     */
    public function fulltext() : static
    {
        $this->data .= ' FULLTEXT INDEX';
        return $this;
    }

    /**
     * Index foreign key
     * 
     * @param string $key
     * @return static
     */
    public function foreignKey(string $key) : static
    {
        $this->data .= ' FOREIGN KEY (`' . $key . '`)';
        return $this;
    }

    /**
     * Get data
     * 
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }
}
