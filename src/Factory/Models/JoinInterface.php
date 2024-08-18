<?php
namespace Clicalmani\Database\Factory\Models;

interface JoinInterface
{
    /**
     * Get join query
     * 
     * @return \Clicalmani\Database\Factory\Models\Model
     */
    public function getQuery() : Model;

    /**
     * Set join query
     * 
     * @param \Clicalmani\Database\Factory\Models\Model $query
     * @return static
     */
    public function setQuery(Model $query) : static;

    /**
     * Get join condition
     * 
     * @return string
     */
    public function getCondition() : string;

    /**
     * Set join condition
     * 
     * @param string $condition
     * @return static
     */
    public function setCondition(string $condition) : static;

    /**
     * Get join fields
     * 
     * @return string
     */
    public function getFields() : string;

    /**
     * Set join fields
     * 
     * @param string $fields
     * @return static
     */
    public function setFields(string $fields) : static;

    /**
     * Get join alias
     * 
     * @return string
     */
    public function getAlias() : string;

    /**
     * Set join alias
     * 
     * @param string $alias
     * @return static
     */
    public function setAlias(string $alias) : static;

    /**
     * Get join type
     * 
     * @return string
     */
    public function getType() : string;

    /**
     * Set join type
     * 
     * @param string $type
     * @return static
     */
    public function setType(string $type) : static;
}
