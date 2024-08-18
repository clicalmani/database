<?php
namespace Clicalmani\Database\Factory\Models;

class Join implements JoinInterface
{
    /**
     * DBQuery
     * 
     * @var \Clicalmani\Database\Factory\Models\Model
     */
    private $query;

    /**
     * Join type
     * 
     * @var string
     */
    private $type = 'left';

    /**
     * Alias name
     * 
     * @var string
     */
    private $alias = '';

    /**
     * Join condition
     * 
     * @var string
     */
    private $condition = '';

    /**
     * Query fields
     * 
     * @var string
     */
    private $fields = '*';

    public function getQuery() : Model
    {
        return $this->query;
    }

    public function setQuery(Model $query) : static
    {
        $this->query = $query;
        return $this;
    }

    public function getCondition() : string
    {
        return $this->condition;
    }

    public function setCondition(string $condition) : static
    {
        $this->condition = $condition;
        return $this;
    }

    public function getFields() : string
    {
        return $this->fields;
    }

    public function setFields(string $fields) : static
    {
        $this->fields = $fields;
        return $this;
    }

    public function getAlias() : string
    {
        return $this->alias;
    }

    public function setAlias(string $alias) : static
    {
        $this->alias = $alias;
        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : static
    {
        $this->type = $type;
        return $this;
    }
}
