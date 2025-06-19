<?php
namespace Clicalmani\Database\Interfaces;

interface JoinClauseInterface
{
    public function on(string $on) : self;

    /**
     * Using statement
     * 
     * @param string $using
     * @return self
     */
    public function using(string $using) : self;

    public function as(string $alias) : self;

    public function type(string $type) : self;

    /**
     * Left join
     * 
     * @return self
     */
    public function left() : self;

    /**
     * Right join
     * 
     * @return self
     */
    public function right() : self;

    /**
     * Inner join
     * 
     * @return self
     */
    public function inner() : self;

    /**
     * Inner join
     * 
     * @return self
     */
    public function outer() : self;

    /**
     * Cross join
     * 
     * @return self
     */
    public function cross() : self;

    /**
     * Sub query
     * 
     * @param string $query
     * @param array $binding
     * @return self
     */
    public function sub(string $query, array $binding = []) : self;
}