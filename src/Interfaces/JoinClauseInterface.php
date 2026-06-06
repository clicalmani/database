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
     * Join condition
      * 
      * @param string $condition
      * @param array $bindings
      * @return self
     */
    public function where(string $condition, ?array $bindings = []) : self;

    /**
     * Join condition with OR
      * 
      * @param string $condition
      * @param array $bindings
      * @return self
     */
    public function orWhere(string $condition, ?array $bindings = []) : self;
}