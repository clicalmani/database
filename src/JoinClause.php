<?php
namespace Clicalmani\Database;

class joinClause
{
    /**
     * ON statement
     * 
     * @var string
     */
    public string $on = '';

    /**
     * Alias name
     * 
     * @var string
     */
    public string $alias;

    /**
     * Join type
     * 
     * @var string
     */
    public string $type;

    /**
     * Sub query
     * 
     * @var string
     */
    public string $sub_query;

    /**
     * Binding
     * 
     * @var array
     */
    public array $binding = [];

    public function on(string $on) : static
    {
        $this->on = "ON($on)";
        return $this;
    }

    /**
     * Using statement
     * 
     * @param string $using
     * @return static
     */
    public function using(string $using) : static
    {
        $this->on = "USING($using)";
        return $this;
    }

    public function as(string $alias) : static
    {
        $this->alias = $alias;
        return $this;
    }

    public function type(string $type) : static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Left join
     * 
     * @return static
     */
    public function left() : static
    {
        return $this->type('LEFT');
    }

    /**
     * Right join
     * 
     * @return static
     */
    public function right() : static
    {
        return $this->type('RIGHT');
    }

    /**
     * Inner join
     * 
     * @return static
     */
    public function inner() : static
    {
        return $this->type('INNER');
    }

    /**
     * Cross join
     * 
     * @return static
     */
    public function cross() : static
    {
        return $this->type('CROSS');
    }

    /**
     * Sub query
     * 
     * @param string $query
     * @param ?array $binding
     * @return static
     */
    public function sub(string $query, ?array $binding = []) : static
    {
        $this->sub_query = $query;
        $this->binding = $binding;
        return $this;
    }
}
