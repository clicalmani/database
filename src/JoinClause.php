<?php
namespace Clicalmani\Database;

class JoinClause implements Interfaces\JoinClauseInterface
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

    public function on(string $on) : self
    {
        $this->on = "ON($on)";
        return $this;
    }

    public function using(string $using) : self
    {
        $this->on = "USING($using)";
        return $this;
    }

    public function as(string $alias) : self
    {
        $this->alias = $alias;
        return $this;
    }

    public function type(string $type) : self
    {
        $this->type = $type;
        return $this;
    }

    public function left() : self
    {
        return $this->type('LEFT');
    }

    public function right() : self
    {
        return $this->type('RIGHT');
    }

    public function inner() : self
    {
        return $this->type('INNER');
    }

    public function outer() : self
    {
        return $this->type('OUTER');
    }

    public function cross() : self
    {
        return $this->type('CROSS');
    }

    public function sub(string $query, ?array $binding = []) : self
    {
        $this->sub_query = $query;
        $this->binding = $binding;
        return $this;
    }
}
